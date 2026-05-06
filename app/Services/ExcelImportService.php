<?php

namespace App\Services;

use App\Models\Etudiant;
use App\Repositories\EtudiantRepository;
use App\Repositories\ProjetRepository;
use App\Repositories\EnseignantRepository;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;

class ExcelImportService
{
    protected EtudiantRepository $etudiantRepository;
    protected ProjetRepository $projetRepository;
    protected EnseignantRepository $enseignantRepository;

    public function __construct(EtudiantRepository $etudiantRepository, ProjetRepository $projetRepository, EnseignantRepository $enseignantRepository)
    {
        $this->etudiantRepository = $etudiantRepository;
        $this->projetRepository = $projetRepository;
        $this->enseignantRepository = $enseignantRepository;
    }

    public function importMaster(UploadedFile $file): array
    {
        $allSheets = Excel::toArray([], $file);
        $results = ['etudiants' => 0, 'enseignants' => 0, 'salles' => 0];

        // 1. Detect Filiere from filename
        $filename = strtolower($file->getClientOriginalName());
        $filiere = 'TDIA'; // Default
        if (str_contains($filename, 'gl')) $filiere = 'GL';
        if (str_contains($filename, 'bda')) $filiere = 'BDA';
        if (str_contains($filename, 'id')) $filiere = 'ID';

        // Sheet 0: Etudiants
        if (isset($allSheets[0])) {
            foreach ($allSheets[0] as $index => $row) {
                if ($index === 0) continue;
                $row = array_pad($row, 5, null);
                [$cne, $nom, $prenom, $emailPerso, $emailAcad] = $row;
                if (empty($nom) || empty($prenom)) continue;

                if (!$this->etudiantRepository->findByCne($cne)) {
                    $this->etudiantRepository->create([
                        'cne' => $cne, 'nom' => $nom, 'prenom' => $prenom, 'filiere' => $filiere,
                        'email_personnel' => trim($emailPerso ?? ''), 'email_academique' => trim($emailAcad ?? ''),
                    ]);
                    $results['etudiants']++;
                }
            }
        }

        // Sheet 1: Enseignants
        if (isset($allSheets[1])) {
            foreach ($allSheets[1] as $index => $row) {
                if ($index < 2) continue; // Skip header
                [$nom, $prenom, $discipline] = array_pad($row, 3, null);
                if (empty($nom) || empty($prenom)) continue;
                
                if (!$this->enseignantRepository->findByNomPrenom($nom, $prenom)) {
                    $this->enseignantRepository->create([
                        'nom' => trim($nom), 'prenom' => trim($prenom), 'specialite' => trim($discipline ?? ''),
                    ]);
                    $results['enseignants']++;
                }
            }
        }

        // Sheet 2: Salles
        if (isset($allSheets[2])) {
            foreach ($allSheets[2] as $index => $row) {
                if ($index === 0) continue;
                [$nom, $cap] = array_pad($row, 2, null);
                if (empty($nom)) continue;

                \App\Models\Salle::firstOrCreate(
                    ['nom' => trim($nom)],
                    ['capacite' => (int)($cap ?? 30)]
                );
                $results['salles']++;
            }
        }

        return $results;
    }

    public function import(UploadedFile $file, string $filiere = 'TDIA'): int
    {
        $rows  = Excel::toArray([], $file)[0];
        $count = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;
            $row = array_pad($row, 5, null);
            [$cne, $nom, $prenom, $emailPerso, $emailAcad] = $row;

            if (empty(trim((string)$nom)) || empty(trim((string)$prenom))) continue;

            // Simple create — table is wiped before each batch import
            \App\Models\Etudiant::create([
                'cne'              => $cne ?: null,
                'nom'              => trim($nom),
                'prenom'           => trim($prenom),
                'filiere'          => $filiere,
                'email_personnel'  => trim($emailPerso ?? ''),
                'email_academique' => trim($emailAcad  ?? ''),
            ]);
            $count++;
        }

        return $count;
    }

    public function importEncadrants(UploadedFile $file): int
    {
        $rows  = Excel::toArray([], $file)[0];
        $count = 0;
        foreach ($rows as $index => $row) {
            if ($index < 2) continue;
            [$nom, $prenom, $discipline] = $row;
            if (empty($nom) || empty($prenom)) continue;
            $existing = $this->enseignantRepository->findByNomPrenom($nom, $prenom);
            if (!$existing) {
                $this->enseignantRepository->create([
                    'nom'       => trim($nom),
                    'prenom'    => trim($prenom),
                    'specialite' => trim($discipline ?? ''), // DB column is 'specialite'
                ]);
                $count++;
            }
        }
        return $count;
    }
}
