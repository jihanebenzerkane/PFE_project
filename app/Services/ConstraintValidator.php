<?php

namespace App\Services;

use App\Models\Creneau;
use App\Models\Soutenance;
use App\Models\Enseignant;
use App\Models\Jury;

class ConstraintValidator
{
    // Valide toutes les règles en même temps
    public function validateAll(
        int $ensId,
        int $creneauId,
        int $salleId,
        Jury $jury
    ): bool {
        return $this->validateCapacity($salleId, $creneauId)
            && $this->validateNoConflict($ensId, $creneauId)
            && $this->validateQuotas($ensId)
            && $this->validateJuryComposition($jury)
            && $this->validateDailyBalance($ensId, $creneauId);
    }

    // Règle 1 : la salle est-elle libre dans ce créneau ?
    public function validateCapacity(int $salleId, int $creneauId): bool
    {
        $dejaOccupee = Soutenance::where('salle_id', $salleId)
            ->where('creneau_id', $creneauId)
            ->exists();

        return !$dejaOccupee;
    }

    // Règle 2 : l'enseignant est-il déjà occupé dans ce créneau ?
    public function validateNoConflict(int $ensId, int $creneauId): bool
    {
        // Déjà encadrant dans ce créneau ?
        $dejaEncadrant = Soutenance::where('creneau_id', $creneauId)
            ->where('encadrant_id', $ensId)
            ->exists();

        // Déjà dans un jury dans ce créneau ?
        $dejaJury = Soutenance::where('creneau_id', $creneauId)
            ->whereHas('jury.enseignants', function ($q) use ($ensId) {
                $q->where('enseignant_id', $ensId);
            })
            ->exists();

        return !$dejaEncadrant && !$dejaJury;
    }

    // Règle 3 : l'enseignant n'a pas dépassé son quota ?
    public function validateQuotas(int $ensId): bool
    {
        $enseignant = Enseignant::find($ensId);

        // Combien de jurys il a déjà fait ?
        $nbJurys = Jury::whereHas('enseignants', function ($q) use ($ensId) {
            $q->where('enseignant_id', $ensId);
        })->count();

        return $nbJurys < $enseignant->quota_max;
    }

    // Règle 4 : le jury est bien formé ?
    public function validateJuryComposition(Jury $jury): bool
    {
        $membres = $jury->enseignants;

        // Le jury doit avoir exactement 2 membres
        if ($membres->count() !== 2) {
            return false;
        }

        // Récupérer la soutenance liée à ce jury
        $soutenance = $jury->soutenance;

        // L'encadrant ne peut pas être membre du jury
        foreach ($membres as $membre) {
            if ($membre->id === $soutenance->encadrant_id) {
                return false;
            }
        }

        return true;
    }

    // Règle 5 : l'enseignant n'intervient pas trop ce jour-là ?
    public function validateDailyBalance(int $ensId, int $creneauId): bool
    {
        $creneau = Creneau::find($creneauId);
        $date    = $creneau->date;

        // Fois où il est encadrant ce jour
        $nbEncadrant = Soutenance::where('encadrant_id', $ensId)
            ->whereHas('creneau', fn($q) => $q->where('date', $date))
            ->count();

        // Fois où il est dans un jury ce jour
        $nbJury = Soutenance::whereHas('jury.enseignants', function ($q) use ($ensId) {
                $q->where('enseignant_id', $ensId);
            })
            ->whereHas('creneau', fn($q) => $q->where('date', $date))
            ->count();

        $total = $nbEncadrant + $nbJury;

        return $total < 4;
    }
}