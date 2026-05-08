<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use App\Models\Creneau;
use App\Models\Etudiant;
use App\Models\Projet;
use App\Models\Soutenance;
use Illuminate\Support\Facades\Storage;

class ConformiteController extends Controller
{
    public function index()
    {
        // 1. Try to load from persistent JSON file (written at last algorithm run)
        if (Storage::exists('conformite_diagnostic.json')) {
            $diagnostic = json_decode(Storage::get('conformite_diagnostic.json'), true);
        } else {
            // 2. Fallback: build live from current DB state
            $diagnostic = $this->buildLiveDiagnostic();
        }

        return view('conformite.index', compact('diagnostic'));
    }

    private function buildLiveDiagnostic(): array
    {
        $totalEtudiants = Etudiant::count();
        // A binome shares one Projet and one Soutenance, so conformity counts
        // scheduled student ids from both etudiant_id and etudiant2_id.
        $scheduledIds   = $this->scheduledStudentIds();
        $affectes       = count($scheduledIds);
        $nonAffectes    = max(0, $totalEtudiants - $affectes);
        $pct            = $totalEtudiants > 0 ? round(($affectes / $totalEtudiants) * 100) : 0;
        $nbSalles       = Salle::count();
        $nbDates        = Creneau::distinct('date')->count('date');
        $capaciteMax    = $nbDates * 7 * $nbSalles;

        $etudiantsNonAffectes = Etudiant::whereNotIn('id', $scheduledIds)->get();

        return [
            'pct'                 => $pct,
            'total'               => $totalEtudiants,
            'affectes'            => $affectes,
            'non_affectes'        => $nonAffectes,
            'nb_salles'           => $nbSalles,
            'nb_dates'            => $nbDates,
            'capacite_max'        => $capaciteMax,
            'manque_capacite'     => max(0, $totalEtudiants - $capaciteMax),
            'etudiants_manquants' => $etudiantsNonAffectes->map(function ($e) {
                $projet = $this->projectForStudent($e);

                return [
                    'nom'       => $e->nom,
                    'prenom'    => $e->prenom,
                    'filiere'   => $e->filiere,
                    'encadrant' => $projet?->encadrant
                        ? ($projet->encadrant->nom . ' ' . $projet->encadrant->prenom)
                        : 'Non assigné',
                ];
            })->toArray(),
        ];
    }

    private function scheduledStudentIds(): array
    {
        return Projet::whereHas('soutenance')
            ->get()
            ->flatMap(fn($p) => array_filter([$p->etudiant_id, $p->etudiant2_id]))
            ->unique()
            ->values()
            ->toArray();
    }

    private function projectForStudent(Etudiant $etudiant): ?Projet
    {
        return Projet::with('encadrant')->where('etudiant2_id', $etudiant->id)->first()
            ?? Projet::with('encadrant')->where('etudiant_id', $etudiant->id)->first();
    }
}
