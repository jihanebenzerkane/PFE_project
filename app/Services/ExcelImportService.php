<?php
namespace App\Services;
use App\Models\Etudiant;
use App\Repositories\EtudiantDAO;
use App\Repositories\ProjetDAO;
use App\Repositories\EnseignantDAO;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;

class ExcelImportService {
 protected EtudiantDAO $etudiantDAO;
 protected ProjetDAO $projetDAO;
 protected EnseignantDAO $enseignantDAO;

 public function __construct(EtudiantDAO $etudiantDAO, ProjetDAO $projetDAO, EnseignantDAO $enseignantDAO) { 
  $this->etudiantDAO = $etudiantDAO;
  $this->projetDAO = $projetDAO;
  $this->enseignantDAO = $enseignantDAO;
 }

 public function import(UploadedFile $file, string $filiere = 'TDIA'): int {
  $rows = Excel::toArray([], $file)[0]; 
  $count = 0;
  foreach($rows as $index => $row){
   if ($index === 0) continue;
   $row = array_pad($row, 5, null);
   [$cne, $nom, $prenom, $emailPerso, $emailAcad] = $row;
   
   if (empty($nom) || empty($prenom)) continue;

   $etudiant = null;
   if (!empty($cne)) {
       $etudiant = $this->etudiantDAO->findByCne($cne);
   }

   if(!$etudiant){
    $etudiant = $this->etudiantDAO->create([
        'cne' => $cne,
        'nom' => $nom,
        'prenom' => $prenom,
        'filiere' => $filiere,
        'email_personnel' => trim($emailPerso ?? ''),
        'email_academique' => trim($emailAcad ?? ''),
    ]);
    $count++;
   }
  }
  return $count;
 }
 public function importEncadrants(UploadedFile $file): int {
  $rows  = Excel::toArray([], $file)[0];
  $count = 0;
  foreach ($rows as $index => $row) {
      if ($index < 2) continue;
      [$nom, $prenom, $discipline] = $row;
      if (empty($nom) || empty($prenom)) continue;
      $existing = $this->enseignantDAO->findByNomPrenom($nom, $prenom);
      if (!$existing) {
          $this->enseignantDAO->create(['nom' => trim($nom),'prenom' => trim($prenom), 'discipline' => trim($discipline), ]);        
          $count++;
      }
  }
  return $count;
 }
}