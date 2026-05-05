<?php

namespace App\Repositories;

use App\Models\Soutenance;
use Illuminate\Support\Collection;

class SoutenanceRepository
{
    // Toutes les soutenances avec leurs relations
    public function findAll(): Collection
    {
        return Soutenance::with([
            'etudiant',
            'encadrant',
            'jury',
            'salle',
            'creneau'
        ])->get();
    }

    // Soutenances d'un étudiant précis
    public function findByEtudiant(int $etudiantId): Collection
    {
        return Soutenance::where('etudiant_id', $etudiantId)->get();
    }

    // Soutenances où cet enseignant est encadrant
    public function findByEnseignant(int $enseignantId): Collection
    {
        return Soutenance::where('encadrant_id', $enseignantId)->get();
    }

    // Est-ce que cet étudiant a déjà une soutenance ?
    public function findByEtudiantExists(int $etudiantId): bool
    {
        return Soutenance::where('etudiant_id', $etudiantId)->exists();
    }

    // Sauvegarder une soutenance
    public function save(Soutenance $soutenance): void
    {
        $soutenance->save();
    }
}