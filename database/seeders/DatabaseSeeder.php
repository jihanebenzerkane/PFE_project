<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Enseignants
        $encadrantId = DB::table('enseignants')->insertGetId([
            'nom' => 'Cherradi',
            'prenom' => 'Mohamed',
            'specialite' => 'Informatique',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rapporteurId = DB::table('enseignants')->insertGetId([
            'nom' => 'Idrissi',
            'prenom' => 'Ali',
            'specialite' => 'Reseaux',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create Etudiant
        $etudiantId = DB::table('etudiants')->insertGetId([
            'nom' => 'Alami',
            'prenom' => 'Ahmed',
            'filiere' => 'Génie Informatique',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Create Projet
        $projetId = DB::table('projets')->insertGetId([
            'titre' => 'Application Web de Gestion des PVs',
            'etudiant_id' => $etudiantId,
            'encadrant_id' => $encadrantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Create Creneau
        $creneauId = DB::table('creneaux')->insertGetId([
            'date' => '2026-06-15',
            'heure_debut' => '10:00:00',
            'heure_fin' => '11:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Create Jury
        $juryId = DB::table('juries')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6. Connect Jury and Enseignant (Pivot)
        DB::table('jury_enseignant')->insert([
            'jury_id' => $juryId,
            'enseignant_id' => $rapporteurId,
            'role' => 'Rapporteur',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 7. Create Soutenance
        DB::table('soutenances')->insert([
            'projet_id' => $projetId,
            'creneau_id' => $creneauId,
            'jury_id' => $juryId,
            'salle' => 'Salle de Conférence A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
