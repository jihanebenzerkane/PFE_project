<?php

namespace App\Repositories;

use App\Models\Creneau;
use App\Models\Soutenance;
use Illuminate\Support\Collection;

class CreneauRepository
{
    public function findAll(): Collection
    {
        return Creneau::orderBy('date')->orderBy('heure_debut')->get();
    }

    public function isSlotFree(int $creneauId): bool
    {
        $creneau  = Creneau::find($creneauId);
        $nbActuel = Soutenance::where('creneau_id', $creneauId)->count();

        return $nbActuel < $creneau->capacite;
    }

    public function getCurrentLocale(int $creneauId): int
    {
        return Soutenance::where('creneau_id', $creneauId)->count();
    }

    public function save(Creneau $creneau): void
    {
        $creneau->save();
    }
}
