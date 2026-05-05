<?php

namespace App\Repositories;

use App\Models\Soutenance;
use Illuminate\Support\Collection;

class SoutenanceDAO
{
    // Toutes les soutenances avec leurs relations
    public function findAll(): Collection
    {
        return Soutenance::with(['projet.etudiant', 'projet.encadrant', 'jury', 'creneau'])->get();
    }

    public function findById($id): Soutenance
    {
        return Soutenance::findOrFail($id);
    }

    // Soutenances d'un enseignant (comme encadrant via Projet)
    public function findByEnseignant(int $enseignantId): Collection
    {
        return Soutenance::whereHas('projet', function ($q) use ($enseignantId) {
            $q->where('encadrant_id', $enseignantId);
        })->get();
    }

    // Sauvegarder une soutenance
    public function save(Soutenance $soutenance): void
    {
        $soutenance->save();
    }
}
