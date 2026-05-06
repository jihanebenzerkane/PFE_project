<?php

namespace App\Services;

use App\Repositories\SalleRepository;
use App\Repositories\JuryRepository;
use App\Models\Soutenance;
use App\Models\Enseignant;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfExportService
{
    public function __construct(
        private SalleRepository $salleRepository,
        private JuryRepository  $juryRepository,
    ) {}

    public function generatePlanning(): \Symfony\Component\HttpFoundation\Response
    {
        $soutenances = Soutenance::with([
            'projet.etudiant',
            'projet.encadrant',
            'jury.enseignants',
            'creneau',
        ])->get();

        $pdf = Pdf::loadView('pdf.planning', [
            'soutenances' => $soutenances,
        ]);

        return $pdf->download('planning.pdf');
    }

    public function generateSupervision(): \Symfony\Component\HttpFoundation\Response
    {
        $enseignants = Enseignant::with('projets.etudiant')->get();

        $pdf = Pdf::loadView('pdf.supervision', [
            'enseignants' => $enseignants,
        ]);

        return $pdf->download('supervision.pdf');
    }

    /**
     * Return the background hex color for a given filière string.
     * Checks specific substrings in priority order to avoid substring collisions
     * (e.g. “Ingénierie” contains “génie” — ID must be checked before GI).
     */
    public static function applyFiliereColor(string $filiere): string
    {
        // Exact match against known stored filière values (most reliable)
        $exact = [
            'Transformation Digitale & Intelligence Artificielle' => '#C6EFCE',
            'Ingénierie des Données'                              => '#F4B183',
            'Génie Informatique'                                  => '#BDD7EE',
        ];
        if (isset($exact[$filiere])) {
            return $exact[$filiere];
        }

        // Fallback: strpos byte-level search for partial matches (short codes, typos)
        // Check ID before GI — "Ingénierie" contains "génie" as a substring
        if (strpos($filiere, 'Transformation') !== false || strpos($filiere, 'TDIA') !== false) {
            return '#C6EFCE';
        }
        if (strpos($filiere, 'Ing') !== false || strpos($filiere, 'Donn') !== false) {
            return '#F4B183';
        }
        if (strpos($filiere, 'nie') !== false || strpos($filiere, 'Informatique') !== false) {
            return '#BDD7EE';
        }

        return '#ffffff';
    }

    /**
     * Return the majority-vote background color for an encadrant identified by $id.
     * Inspects all students supervised by this professor and picks the most common filière color.
     */
    public function applyEncadrantColor(int $id): string
    {
        $enseignant = Enseignant::with('projets.etudiant')->find($id);
        if (!$enseignant) return '#ffffff';

        $colors = $enseignant->projets
            ->pluck('etudiant.filiere')
            ->filter()
            ->map(fn($f) => self::applyFiliereColor($f));

        if ($colors->isEmpty()) return '#ffffff';

        return $colors->countBy()->sortDesc()->keys()->first();
    }

    /**
     * Generate a guaranteed unique background color for a given professor's name.
     */
    public static function getProfessorColor(string $name): string
    {
        static $mapping = null;
        
        if ($mapping === null) {
            $palette = [
                '#FFB3BA', '#FFDFBA', '#FFFFBA', '#BAFFC9', '#BAE1FF',
                '#FFC1E3', '#E2A9F3', '#C5A3FF', '#A3C2FF', '#A3E4D7',
                '#D5F5E3', '#FCF3CF', '#FAD7A1', '#F5B041', '#EB984E',
                '#E74C3C', '#F1948A', '#D7BDE2', '#AF7AC5', '#7FB3D5',
                '#5DADE2', '#48C9B0', '#45B39D', '#58D68D', '#52BE80',
                '#F4D03F', '#F5B041', '#DC7633', '#CACFD2', '#AAB7B8',
                '#99A3A4', '#5D6D7E', '#85C1E9', '#BB8FCE', '#F5B7B1',
                '#A2D9CE', '#AED6F1', '#D2B4DE', '#EDBB99', '#F9E79F',
                '#1ABC9C', '#2ECC71', '#3498DB', '#9B59B6', '#34495E',
                '#F1C40F', '#E67E22', '#E74C3C', '#95A5A6', '#16A085'
            ];
            
            $profs = \App\Models\Enseignant::select('nom', 'prenom')->get()
                ->map(fn($e) => trim(strtoupper($e->nom . ' ' . $e->prenom)))
                ->unique()
                ->values();
                
            $mapping = [];
            foreach ($profs as $i => $p) {
                $mapping[$p] = $palette[$i % count($palette)];
            }
        }

        $cleanName = trim(strtoupper(str_replace(['Dr. ', 'Pr. ', 'DR. ', 'PR. '], '', $name)));
        return $mapping[$cleanName] ?? '#ffffff';
    }
}