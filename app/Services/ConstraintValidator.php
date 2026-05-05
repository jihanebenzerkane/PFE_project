<?php

namespace App\Services;

use App\Models\Enseignant;
use App\Models\Soutenance;
use App\Models\Creneau;
use App\Models\Projet;
use App\Models\Jury;
use Carbon\Carbon;

class ConstraintValidator
{
    /**
     * Valider l'affectation globale (encadrants assignés aux projets).
     */
    public function validateAffectation(): bool
    {
        // Vérifier si des projets n'ont pas d'encadrant assigné
        $unassignedProjects = Projet::whereNull('encadrant_id')->count();
        
        return $unassignedProjects === 0;
    }

    /**
     * Valider que la charge d'encadrement est équilibrée (moyenne).
     */
    public function validateEncadrementAverage(): bool
    {
        $totalProjets = Projet::count();
        $totalEnseignants = Enseignant::count();
        
        if ($totalEnseignants === 0) return true;

        $average = ceil($totalProjets / $totalEnseignants);
        $maxAllowed = $average + 2; // Tolérance stricte de +2 projets maximum par rapport à la moyenne

        $overloaded = Enseignant::withCount('projets')
            ->having('projets_count', '>', $maxAllowed)
            ->exists();

        return !$overloaded;
    }

    /**
     * Valider le planning complet (toutes les soutenances ont un créneau et une salle).
     */
    public function validatePlanning(): bool
    {
        $unscheduled = Projet::whereDoesntHave('soutenance', function($query) {
            $query->whereNotNull('creneau_id')->whereNotNull('salle');
        })->count();

        return $unscheduled === 0;
    }

    /**
     * Vérifier qu'un enseignant n'est pas programmé deux fois en même temps.
     */
    public function validateNoConflict($ensId, $creneauId): bool
    {
        // Un enseignant ne peut pas avoir deux soutenances (comme encadrant ou jury) sur le même créneau
        $conflict = Soutenance::where('creneau_id', $creneauId)
            ->where(function ($query) use ($ensId) {
                // Est-il encadrant du projet ?
                $query->whereHas('projet', function ($q) use ($ensId) {
                    $q->where('encadrant_id', $ensId);
                })
                // Ou est-il membre du jury ?
                ->orWhereHas('jury.enseignants', function ($q) use ($ensId) {
                    $q->where('enseignants.id', $ensId);
                });
            })
            ->exists();

        return !$conflict;
    }

    /**
     * Vérifier la pause minimale de 1 heure pour un enseignant entre ses soutenances.
     */
    public function validatePauseMinimale($ensId): bool
    {
        $soutenances = Soutenance::whereHas('projet', function ($q) use ($ensId) {
                $q->where('encadrant_id', $ensId);
            })
            ->orWhereHas('jury.enseignants', function ($q) use ($ensId) {
                $q->where('enseignants.id', $ensId);
            })
            ->with('creneau')
            ->get()
            ->sortBy(function($s) {
                return $s->creneau->date . ' ' . $s->creneau->heure;
            });

        $previousTime = null;
        foreach ($soutenances as $soutenance) {
            if (!$soutenance->creneau) continue;
            
            $currentTime = Carbon::parse($soutenance->creneau->date . ' ' . $soutenance->creneau->heure);
            
            if ($previousTime) {
                $diffInMinutes = $previousTime->diffInMinutes($currentTime);
                // Si la différence est de moins de 120 minutes (60m soutenance + 60m pause)
                if ($diffInMinutes < 120) {
                    return false;
                }
            }
            $previousTime = $currentTime;
        }
        
        return true;
    }

    /**
     * Valider la capacité d'une salle.
     */
    public function validateCapacity($salle): bool
    {
        // Dans la version actuelle, on valide simplement que la salle n'est pas vide
        return !empty(trim($salle));
    }

    /**
     * Valider la composition d'un jury (L'encadrant ne doit pas être Rapporteur).
     */
    public function validateJuryComposition($jury): bool
    {
        if (!$jury || !$jury->soutenances()->first() || !$jury->soutenances()->first()->projet) {
            return false;
        }

        $encadrantId = $jury->soutenances()->first()->projet->encadrant_id;
        
        // Vérifier si l'encadrant est dans les membres de ce jury
        $isInJury = $jury->enseignants->contains('id', $encadrantId);

        return !$isInJury;
    }

    /**
     * Valider qu'il y a un nombre minimum d'informaticiens dans le jury.
     */
    public function validateInformaticsCount(): bool
    {
        $jurys = Jury::with('enseignants')->get();
        
        foreach ($jurys as $jury) {
            $hasInformatician = false;
            foreach ($jury->enseignants as $prof) {
                if ($prof->isInformatique()) {
                    $hasInformatician = true;
                    break;
                }
            }
            // S'il y a un jury sans aucun informaticien, la validation échoue
            if (!$hasInformatician && $jury->enseignants->count() > 0) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Valider la mixité des filières par jour.
     */
    public function validateDailyFiliereMix($date): bool
    {
        $filieresOnDate = Soutenance::whereHas('creneau', function($q) use ($date) {
                $q->where('date', $date);
            })
            ->join('projets', 'soutenances.projet_id', '=', 'projets.id')
            ->join('etudiants', 'projets.etudiant_id', '=', 'etudiants.id')
            ->distinct('etudiants.filiere')
            ->count('etudiants.filiere');

        $totalSoutenances = Soutenance::whereHas('creneau', function($q) use ($date) {
            $q->where('date', $date);
        })->count();

        // S'il y a plus de 3 soutenances ce jour-là, on exige au moins 2 filières différentes pour la mixité
        if ($totalSoutenances > 3 && $filieresOnDate < 2) {
            return false;
        }

        return true;
    }
}