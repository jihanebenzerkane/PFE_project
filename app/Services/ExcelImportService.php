<?php
namespace App\Services;

use App\Repositories\EtudiantRepository;
use App\Repositories\EnseignantRepository;
use App\Repositories\ProjetRepository;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;

class ExcelImportService
{
    protected EtudiantRepository $etudiantRepository;
    protected ProjetRepository $projetRepository;
    protected EnseignantRepository $enseignantRepository;

    public function __construct(
        EtudiantRepository $etudiantRepository,
        ProjetRepository $projetRepository,
        EnseignantRepository $enseignantRepository
    ) {
        $this->etudiantRepository   = $etudiantRepository;
        $this->projetRepository     = $projetRepository;
        $this->enseignantRepository = $enseignantRepository;
    }
    public function import(
        UploadedFile $projectFile,
        array $emailLookup,
        string $filiere
    ): array {
        $rows = Excel::toArray(new \stdClass(), $projectFile)[0] ?? [];
        $studentCount = 0;
        $projetCount  = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;

            $row = array_pad($row, 8, null);
            [$cne, $nom, $prenom, $cne2, $nom2, $prenom2, $sujet, $langue] = $row;

            $cne    = $this->clean($cne);
            $nom    = $this->clean($nom);
            $prenom = $this->clean($prenom);
            if (empty($nom) || empty($prenom)) continue;

            
            $emails = $emailLookup[$cne] ?? [
                'email_personnel'  => '',
                'email_academique' => '',
            ];

            
            $etudiant = $this->etudiantRepository->updateOrCreate(
                ['cne' => $cne ?: $nom.'_'.$prenom], 
                   [ 'nom'              => $nom,
                    'prenom'           => $prenom,
                    'filiere'          => $filiere,
                    'email_personnel'  => $emails['email_personnel'] ?: '',
                    'email_academique' => $emails['email_academique'] ?: '',
                ]
            );

            $etudiant2Id = null;
            $cne2 = $this->clean($cne2);
            if ($this->isBinome($cne2) || !empty($nom2)) {
                $emails2 = $emailLookup[$cne2] ?? [
                    'email_personnel'  => '',
                    'email_academique' => '',
                ];
                $etudiant2 = $this->etudiantRepository->updateOrCreate(
                    ['cne' => $cne2 ?: $nom2.'_'.$prenom2],
                    [
                        'nom'              => $this->clean($nom2),
                        'prenom'           => $this->clean($prenom2),
                        'filiere'          => $filiere,
                        'email_personnel'  => $emails2['email_personnel'] ?: '',
                        'email_academique' => $emails2['email_academique'] ?: '',
                    ]
                );
                $etudiant2Id = $etudiant2->id;
            }





            $this->projetRepository->create([
                'cne'               => $cne ?: null,
                'etudiant_id'       => $etudiant->id,
                'etudiant2_id'      => $etudiant2Id,
                'sujet'             => $this->clean($sujet),
                'titre'             => $this->clean($sujet),
                'langue_soutenance' => $this->clean($langue) ?: 'Français',
            ]);

            $studentCount += $etudiant2Id ? 2 : 1;
            $projetCount  += 1;
        }

        return ['students' => $studentCount, 'projects' => $projetCount];
    }

    // teachers
    public function importEnseignants(UploadedFile $file): array
    {
        $rows          = Excel::toArray(new \stdClass(), $file)[0] ?? [];
        $enseignantCount = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;
            
            $row = array_pad($row, 3, null);
            [$nom, $prenom, $discipline] = $row;

            $nom    = $this->clean($nom);
            $prenom = $this->clean($prenom);
            
            if (empty($nom) || strtolower($nom) === 'nom') continue;
            if ($this->enseignantRepository->findByNomPrenom($nom, $prenom)) continue;

            $this->enseignantRepository->create([
                'nom'       => $nom,
                'prenom'    => $prenom,
                'specialite' => $this->clean($discipline),
            ]);
            $enseignantCount += 1;
        }

        return ['enseignant' => $enseignantCount];
    }

    // salles
    public function importSalles(UploadedFile $file): array
    {
        $rows      = Excel::toArray(new \stdClass(), $file)[0] ?? [];
        $salleCount = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;
            $row = array_pad($row, 2, null);
            [$nom, $capacite] = $row;

            $nom = $this->clean($nom);
            if (empty($nom)) continue;

           
            \App\Models\Salle::firstOrCreate(
                ['nom'      => $nom],
                ['capacite' => (int) ($capacite ?? 30)]
            );
            $salleCount += 1;
        }

        return ['salles' => $salleCount];
    }

    public function buildGlobalEmailLookup(array $emailFiles): array
    {
        $lookup = [];

        foreach ($emailFiles as $file) {
            $rows = Excel::toArray(new \stdClass(), $file)[0] ?? [];
            foreach ($rows as $index => $row) {
                if ($index === 0) continue;
                $row = array_pad($row, 5, null);
                [$cne, $nom, $prenom, $emailPerso, $emailAcad] = $row;

                $cne = $this->clean($cne);
                if (empty($cne)) continue;

                $lookup[$cne] = [
                    'email_personnel'  => $this->clean($emailPerso),
                    'email_academique' => $this->clean($emailAcad),
                ];
            }
        }

        return $lookup;
    }

    public function importMaster(UploadedFile $file, string $filiere = 'Inconnue'): array
    {
        $allSheets = Excel::toArray(new \stdClass(), $file);
        $results   = ['students' => 0, 'enseignants' => 0, 'salles' => 0];

        if (isset($allSheets[0])) {
            foreach ($allSheets[0] as $index => $row) {
                if ($index === 0) continue;
                $row = array_pad($row, 8, null);
                [$cne, $nom, $prenom, $cne2, $nom2, $prenom2, $sujet, $langue] = $row;

                $cne = $this->clean($cne);
                if (empty($nom)) continue;

                $etudiant = $this->etudiantRepository->updateOrCreate(
                    ['cne' => $cne ?: $this->clean($nom).'_'.$this->clean($prenom)],
                    ['nom' => $this->clean($nom), 'prenom' => $this->clean($prenom), 'filiere' => $filiere]
                );

                $etudiant2Id = null;
                if ($this->isBinome($cne2) || !empty($nom2)) {
                    $et2 = $this->etudiantRepository->updateOrCreate(
                        ['cne' => $this->clean($cne2) ?: $this->clean($nom2).'_'.$this->clean($prenom2)],
                        ['nom' => $this->clean($nom2), 'prenom' => $this->clean($prenom2), 'filiere' => $filiere]
                    );
                    $etudiant2Id = $et2->id;
                }

                $this->projetRepository->create([
                    'cne' => $cne, 'etudiant_id' => $etudiant->id, 'etudiant2_id' => $etudiant2Id,
                    'sujet' => $this->clean($sujet), 'titre' => $this->clean($sujet), 'langue_soutenance' => $this->clean($langue) ?: 'Français'
                ]);
                $results['students'] += $etudiant2Id ? 2 : 1;
            }
        }

        return $results;
    }

    // helpers
    private function clean(mixed $value): string
    {
        return trim((string) $value);
    }

    private function isBinome(mixed $cne2): bool
    {
        return $this->clean($cne2) !== '';
    }
}
