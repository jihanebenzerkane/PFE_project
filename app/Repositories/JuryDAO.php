<?php

namespace App\Repositories;

use App\Models\Jury;
use App\Enums\JuryRole;
use Illuminate\Database\Eloquent\Collection;

class JuryDAO
{
    public function getAll(): Collection
    {
        return Jury::with('enseignants')->get();
    }

    public function findById(int $id): Jury
    {
        return Jury::with('enseignants')->findOrFail($id);
    }

    public function save(Jury $jury): void
    {
        $jury->save();
    }

    public function findBySlot(int $creneauId): Collection
    {
        return Jury::whereHas('soutenance', function ($query) use ($creneauId) {
            $query->where('creneau_id', $creneauId);
        })->get();
    }

    public function addMember(int $juryId, int $enseignantId, JuryRole $role): void
    {
        $jury = Jury::findOrFail($juryId);

        $jury->enseignants()->attach($enseignantId, [
            'role' => $role->value
        ]);
    }

    public function getMembers(int $juryId): Collection
    {
        $jury = Jury::findOrFail($juryId);

        return $jury->enseignants()->withPivot('role')->get();
    }

    public function validateComposition(int $juryId): bool
    {
        $members = $this->getMembers($juryId);

        if ($members->count() < 3) {
            return false;
        }

        $presidentCount = $members->filter(function ($member) {
            return $member->pivot->role === 'president';
        })->count();

        if ($presidentCount !== 1) {
            return false;
        }

        $hasExaminateur = $members->contains(function ($member) {
            return $member->pivot->role === 'examinateur';
        });

        if (!$hasExaminateur) {
            return false;
        }

        $hasRapporteur = $members->contains(function ($member) {
            return $member->pivot->role === 'rapporteur';
        });

        if (!$hasRapporteur) {
            return false;
        }

        return true;
    }
}