<?php

namespace App\Services;

use App\Models\Creneau;
use App\Models\Soutenance;
use App\Models\Enseignant;
use App\Models\Jury;
use Carbon\Carbon;

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
            && $this->validateDailyBalance($ensId, $creneauId)
            && $this->validateBreakTime($ensId, $creneauId);
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

    // Règle 6 : pause obligatoire d'1h après chaque soutenance pour un même enseignant.
    //
    // Chaque soutenance dure 1 heure. Les soutenances ont lieu uniquement :
    //   - Le matin   : 09:00 → 12:00  (créneaux : 9h-10h, 10h-11h, 11h-12h)
    //   - L'après-M  : 14:00 → 18:00  (créneaux : 14h-15h, 15h-16h, 16h-17h, 17h-18h)
    //
    // Règle : entre deux créneaux où un enseignant intervient,
    //         le gap doit être ≥ 60 minutes.
    //
    // Exemples :
    //   Occupé 9h-10h  → Proposé 10h-11h : gap =  0 min → ❌ REFUSÉ
    //   Occupé 9h-10h  → Proposé 11h-12h : gap = 60 min → ✅ OK
    //   Occupé 11h-12h → Proposé 14h-15h : gap = 120 min → ✅ OK (pause naturelle 12h-14h)
    public function validateBreakTime(int $ensId, int $creneauId): bool
    {
        $creneau      = Creneau::find($creneauId);
        $date         = $creneau->date->toDateString();
        $debutPropose = Carbon::parse($date . ' ' . $creneau->heure_debut->format('H:i'));
        $finProposee  = Carbon::parse($date . ' ' . $creneau->heure_fin->format('H:i'));

        // Récupère tous les créneaux du même jour où l'enseignant est déjà impliqué
        // (comme encadrant OU comme juré), sauf le créneau en cours d'évaluation.
        $creneauxOccupes = Creneau::where('date', $date)
            ->where('id', '!=', $creneauId)
            ->where(function ($query) use ($ensId) {
                $query
                    ->whereHas('soutenances', fn($q) => $q->where('encadrant_id', $ensId))
                    ->orWhereHas('soutenances', fn($q) =>
                        $q->whereHas('jury.enseignants', fn($q2) =>
                            $q2->where('enseignant_id', $ensId)
                        )
                    );
            })
            ->get(['heure_debut', 'heure_fin']);

        foreach ($creneauxOccupes as $occupe) {
            $debutOccupe = Carbon::parse($date . ' ' . $occupe->heure_debut->format('H:i'));
            $finOccupe   = Carbon::parse($date . ' ' . $occupe->heure_fin->format('H:i'));

            // Le créneau occupé est AVANT le créneau proposé
            // → gap = debutPropose - finOccupe, doit être >= 60 min
            if ($finOccupe->lte($debutPropose) && $finOccupe->diffInMinutes($debutPropose) < 60) {
                return false;
            }

            // Le créneau proposé est AVANT le créneau occupé
            // → gap = debutOccupe - finProposee, doit être >= 60 min
            if ($finProposee->lte($debutOccupe) && $finProposee->diffInMinutes($debutOccupe) < 60) {
                return false;
            }
        }

        return true;
    }
}