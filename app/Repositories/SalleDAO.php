<?php

namespace App\Repositories;

use App\Models\Salle;
use Illuminate\Database\Eloquent\Collection;

class SalleDAO
{
    public function findAll(): Collection
    {
        return Salle::all();
    }

    public function findAvailable(int $creneauId): Collection
    {
        return Salle::whereDoesntHave('soutenances', function ($query) use ($creneauId) {
            $query->where('creneau_id', $creneauId);
        })->get();
    }

    public function checkCapacity(int $id): bool
    {
        $salle = Salle::find($id);

        if (!$salle) {
            return false;
        }

        return $salle->capacite > 0;
    }

    public function save(Salle $salle): void
    {
        $salle->save();
    }

    public function delete(int $id): void
    {
        $salle = Salle::findOrFail($id);
        $salle->delete();
    }
}