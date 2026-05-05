<?php

namespace App\Repositories;

use App\Models\Creneau;
use App\Models\Soutenance;
use Illuminate\Support\Collection;

class CreneauRepository
{
    // Retourner tous les créneaux
    public function findAll(): Collection
    {
        return Creneau::all();
    }

    // Est-ce que ce créneau a encore de la place ?
    public function isSlotFree(int $creneauId, bool $checkJury = false): bool
    {
        $creneau  = Creneau::find($creneauId);
        $nbActuel = Soutenance::where('creneau_id', $creneauId)->count();

        return $nbActuel < $creneau->capacite;
    }

    // Combien de soutenances sont déjà dans ce créneau
    public function getCurrentLocale(int $creneauId): int
    {
        return Soutenance::where('creneau_id', $creneauId)->count();
    }

    // Sauvegarder un créneau
    public function save(Creneau $creneau): void
    {
        $creneau->save();
    }
}