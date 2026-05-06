<?php

namespace App\Repositories;

use App\Models\Soutenance;
use Illuminate\Support\Collection;

class SoutenanceRepository
{
    public function findAll(): Collection
    {
        return Soutenance::with(['projet.etudiant', 'projet.encadrant', 'jury', 'creneau'])->get();
    }

    public function findById($id): Soutenance
    {
        return Soutenance::findOrFail($id);
    }

    public function findByEnseignant(int $enseignantId): Collection
    {
        return Soutenance::whereHas('projet', function ($q) use ($enseignantId) {
            $q->where('encadrant_id', $enseignantId);
        })->get();
    }

    public function save(Soutenance $soutenance): void
    {
        $soutenance->save();
    }
}
