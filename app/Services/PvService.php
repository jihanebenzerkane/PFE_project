<?php

namespace App\Services;

use App\Repositories\SoutenanceDAO;
use App\Repositories\EnseignantDAO;
use App\Models\Soutenance;

class PvService
{

    protected SoutenanceDAO $soutenanceDAO;
    protected EnseignantDAO $enseignantDAO;

    public function __construct(SoutenanceDAO $soutenanceDAO, EnseignantDAO $enseignantDAO)
    {
        $this->soutenanceDAO = $soutenanceDAO;
        $this->enseignantDAO = $enseignantDAO;
    }


    public function generatePvForStudent(Soutenance $soutenance, $customFolder = 'app/public')
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('template_pv.docx'));

        $currentYear = date('Y');
        $annee_univ = ($currentYear - 1) . '-' . $currentYear;
        $template->setValue('annee_univ', $annee_univ);

        $nom = $soutenance->projet->etudiant->nom;
        $prenom = $soutenance->projet->etudiant->prenom;

        $template->setValue('nom_etudiant', $nom . ' ' . $prenom);

        $filiere = $soutenance->projet->etudiant->filiere;
        $box_id = '☐';
        $box_gi = '☐';
        $box_tdia = '☐';

        if ($filiere === 'Ingénierie des Données' || $filiere === 'ID') {
            $box_id = '☑';
        } elseif ($filiere === 'Génie Informatique' || $filiere === 'GI') {
            $box_gi = '☑';
        } elseif ($filiere === 'Transformation Digital & IA' || $filiere === 'TDIA') {
            $box_tdia = '☑';
        }

        $template->setValue('box_id', $box_id);
        $template->setValue('box_gi', $box_gi);
        $template->setValue('box_tdia', $box_tdia);

        $intitule_projet = $soutenance->projet->titre;
        $template->setValue('intitule_rapport', $intitule_projet);

        $encadrant = $soutenance->projet->encadrant;
        $template->setValue('nom_encadrant', $encadrant->nom . ' ' . $encadrant->prenom);

        $rapporteurs = $soutenance->jury->enseignants->where('pivot.role', '!=', 'President')->values();
        $count = $rapporteurs->count();
        $template->cloneRow('nom_jury', $count);
        foreach ($rapporteurs as $index => $prof) {
            $rowNumber = $index + 1;

            $template->setValue("nom_jury#{$rowNumber}", $prof->nom . ' ' . $prof->prenom);
            $template->setValue("jury_role#{$rowNumber}", $prof->pivot->role);
        }

        $date = $soutenance->creneau->date;
        $template->setValue('date_soutenance', $date);

        $fileName = "Fiche_Evaluation_PFE_{$nom}_{$prenom}.docx";

        $savePath = storage_path($customFolder . '/' . $fileName);
        $template->saveAs($savePath);

        return $savePath;
    }

    public function organizePvsByTeacher()
    {
        $profs = $this->enseignantDAO->findAll();

        foreach ($profs as $prof) {
            $nom = $prof->nom;
            $prenom = $prof->prenom;

            $allSoutenances = $prof->soutenances;

            if ($allSoutenances->isNotEmpty()) {
                $folderName = "Pr_{$nom}_{$prenom}";
                $teacherFolderPath = 'temp_pvs/' . $folderName;

                if (!file_exists(storage_path($teacherFolderPath))) {
                    mkdir(storage_path($teacherFolderPath), 0777, true);
                }

                foreach ($allSoutenances as $soutenance) {
                    $this->generatePvForStudent($soutenance, $teacherFolderPath);
                }
            }
        }

        // THE ZIP COMPRESSION

        $zipFileName = 'Archive_PVs_PFE_' . date('Y') . '.zip';
        $zipFilePath = storage_path('app/public/' . $zipFileName);

        $zip = new \ZipArchive();
        
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            
            $files = \Illuminate\Support\Facades\File::allFiles(storage_path('temp_pvs'));

            foreach ($files as $file) {
                $zip->addFile($file->getRealPath(), $file->getRelativePathname());
            }
            $zip->close();
        }

        \Illuminate\Support\Facades\File::deleteDirectory(storage_path('temp_pvs'));

        return $zipFilePath;
    }
}
