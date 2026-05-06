<?php

namespace App\Http\Controllers;

use App\Models\AffectationSnapshot;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Projet;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    protected ExcelImportService $importService;
    public function __construct(ExcelImportService $importService)
    {
        $this->importService = $importService;
    }
    public function showForm()
    {
        return view('import');
    }
    public function importMaster(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5000'
        ]);

        $results = $this->importService->importMaster($request->file('file'));

        return redirect()->route('affectation.index')->with('success', 
            "Importation terminée : {$results['etudiants']} étudiants, {$results['enseignants']} enseignants, {$results['salles']} salles.");
    }

    public function importEtudiants(Request $request)
    {
        $request->validate([
            'file_etudiants'   => 'required',
            'file_etudiants.*' => 'file|mimes:xlsx,xls|max:5000',
        ]);

        // ── Wipe old student data before fresh import ──
        \App\Models\Etudiant::query()->delete();
        \App\Models\Projet::query()->delete();
        \App\Models\AffectationSnapshot::query()->delete();

        $totalCount = 0;
        $details    = [];

        foreach ($request->file('file_etudiants') as $file) {
            $filename = strtolower($file->getClientOriginalName());

            $filiere = $this->detectFiliere($filename, $file);

            $count       = $this->importService->import($file, $filiere);
            $totalCount += $count;
            $details[]   = "$count étudiants ($filiere)";
        }

        $detail = implode(', ', $details);

        return redirect()->route('import.form')
            ->with('success', "Fichiers importés avec succès — $totalCount étudiants au total. ($detail)");
    }


    public function importProfs(Request $request)
    {
        $request->validate([
            'file_profs' => 'required|file|mimes:xlsx,xls|max:2048'
        ]);

        // ── Wipe old professor data before fresh import ──
        \App\Models\Enseignant::query()->delete();

        $file = $request->file('file_profs');
        $count = $this->importService->importEncadrants($file);
        
        return redirect()->route('affectation.index')->with('success', "$count enseignants importés avec succès.");
    }
    private function detectFiliere(string $filename, $file): string
    {
        // 1. Specific multi-word keywords first (order matters — longer before shorter!)
        $map = [
            // Ingénierie des Données — must come BEFORE 'genie' to prevent false match
            'ingenierie' => 'Ingénierie des Données',
            'ingénierie' => 'Ingénierie des Données',
            'donnees'    => 'Ingénierie des Données',
            'données'    => 'Ingénierie des Données',
            // TDIA
            'tdia'           => 'Transformation Digitale & Intelligence Artificielle',
            'transformation' => 'Transformation Digitale & Intelligence Artificielle',
            'digitale'       => 'Transformation Digitale & Intelligence Artificielle',
            'artificielle'   => 'Transformation Digitale & Intelligence Artificielle',
            // Génie Informatique — after ingenierie so "ingenierie" doesn't hit "genie"
            'genie'  => 'Génie Informatique',
            'génie'  => 'Génie Informatique',
        ];

        foreach ($map as $keyword => $filiere) {
            if (str_contains($filename, $keyword)) return $filiere;
        }

        // 2. Short codes — word boundaries to avoid matching inside longer words
        if (preg_match('/\btdia\b/i', $filename))
            return 'Transformation Digitale & Intelligence Artificielle';
        // 'id' before 'gi' to avoid "ingenierie" matching 'gi'
        if (preg_match('/\bid\b/i', $filename))
            return 'Ingénierie des Données';
        if (preg_match('/\bgi\b/i', $filename))
            return 'Génie Informatique';

        // 3. Fallback: scan first few rows of the Excel file for keywords
        try {
            $rows     = \Maatwebsite\Excel\Facades\Excel::toArray([], $file)[0];
            $haystack = strtolower(implode(' ', array_map('strval', array_merge(...array_slice($rows, 0, 5)))));

            // Check ingenierie/données before genie to prevent substring match
            if (str_contains($haystack, 'ingénierie') || str_contains($haystack, 'ingenierie') || str_contains($haystack, 'données'))
                return 'Ingénierie des Données';
            if (str_contains($haystack, 'tdia') || str_contains($haystack, 'transformation'))
                return 'Transformation Digitale & Intelligence Artificielle';
            if (str_contains($haystack, 'génie') || str_contains($haystack, 'genie'))
                return 'Génie Informatique';
        } catch (\Throwable $e) {
            // silent fallback
        }

        return 'Inconnue';
    }
}

