<?php

namespace App\Services;

use App\Models\Enseignant;
use App\Models\Projet;
use App\Models\Soutenance;
use Illuminate\Support\Facades\DB;

class VerificationService
{
    /**
     * Vérifier les contraintes d'affectation
     */
    public function checkAffectations(): array
    {
        $enseignants = Enseignant::withCount(['projets as etudiants_count' => function ($query) {
            $query->join('etudiants', 'etudiants.id', '=', 'projets.etudiant_id');
        }])->get();

        $totalEtudiantsAffectes = $enseignants->sum('etudiants_count');
        $totalEnseignants = $enseignants->count();
        $moyenne = $totalEnseignants > 0 ? round($totalEtudiantsAffectes / $totalEnseignants, 2) : 0;

        $anomalies = [];

        foreach ($enseignants as $enseignant) {
            $count = $enseignant->etudiants_count;
            if ($count > 0 && ($count < 3 || $count > 4)) {
                $anomalies[] = [
                    'type' => 'Écart d\'encadrement',
                    'message' => "Le professeur {$enseignant->nom} {$enseignant->prenom} encadre {$count} étudiants (La norme est entre 3 et 4)."
                ];
            }
        }

        return [
            'moyenne' => $moyenne,
            'anomalies' => $anomalies
        ];
    }

    /**
     * Vérifier les contraintes du planning
     */
    public function checkPlannings(): array
    {
        $soutenances = Soutenance::with(['creneau', 'projet.encadrant', 'jury.enseignants'])->get();
        
        $anomalies = [];
        $sallesParCreneau = [];
        $profsParCreneau = [];
        $profsPlanning = []; // Pour vérifier l'heure de repos [prof_id => [creneaux...]]

        foreach ($soutenances as $soutenance) {
            $creneau = $soutenance->creneau;
            if (!$creneau) continue;

            $creneauId = $creneau->id;
            $salle = $soutenance->salle;

            // 1. Chevauchement de salles
            if ($salle) {
                if (isset($sallesParCreneau[$creneauId][$salle])) {
                    $anomalies[] = [
                        'type' => 'Chevauchement de salle',
                        'message' => "La salle '{$salle}' est utilisée plusieurs fois le {$creneau->date->format('d/m/Y')} à {$creneau->heure_debut->format('H:i')}."
                    ];
                }
                $sallesParCreneau[$creneauId][$salle] = true;
            }

            // Récupérer tous les profs pour cette soutenance
            $profIds = [];
            if ($soutenance->projet && $soutenance->projet->encadrant_id) {
                $profIds[] = $soutenance->projet->encadrant_id;
            }
            if ($soutenance->jury) {
                foreach ($soutenance->jury->enseignants as $membre) {
                    $profIds[] = $membre->id;
                }
            }
            // Retirer les doublons au sein de la même soutenance (ex: l'encadrant est aussi président)
            $profIds = array_unique($profIds);

            // 2. Double assignation horaire
            foreach ($profIds as $profId) {
                if (isset($profsParCreneau[$creneauId][$profId])) {
                    $prof = Enseignant::find($profId);
                    $anomalies[] = [
                        'type' => 'Double assignation',
                        'message' => "Le professeur {$prof->nom} {$prof->prenom} est assigné à plusieurs soutenances en même temps le {$creneau->date->format('d/m/Y')} à {$creneau->heure_debut->format('H:i')}."
                    ];
                }
                $profsParCreneau[$creneauId][$profId] = true;

                // Ajouter au planning du prof pour l'heure de repos
                $profsPlanning[$profId][] = $creneau;
            }
        }

        // 3. Heure de repos (min 1 heure entre 2 soutenances le même jour)
        foreach ($profsPlanning as $profId => $creneauxProf) {
            // Trier les créneaux par date et heure de début
            usort($creneauxProf, function($a, $b) {
                if ($a->date == $b->date) {
                    return strtotime($a->heure_debut) - strtotime($b->heure_debut);
                }
                return strtotime($a->date) - strtotime($b->date);
            });

            for ($i = 0; $i < count($creneauxProf) - 1; $i++) {
                $c1 = $creneauxProf[$i];
                $c2 = $creneauxProf[$i+1];

                if ($c1->date == $c2->date && $c1->id !== $c2->id) {
                    $diffSeconds = abs(strtotime($c2->heure_debut) - strtotime($c1->heure_debut));
                    // S'ils sont à moins de 2 heures d'écart (soit en même temps, soit back-to-back sans 1h de pause)
                    if ($diffSeconds > 0 && $diffSeconds < 7200) {
                        $prof = Enseignant::find($profId);
                        $anomalies[] = [
                            'type' => 'Heure de repos non respectée',
                            'message' => "Le professeur {$prof->nom} {$prof->prenom} enchaîne des soutenances le {$c1->date->format('d/m/Y')} sans une heure de repos (entre {$c1->heure_debut->format('H:i')} et {$c2->heure_debut->format('H:i')})."
                        ];
                    }
                }
            }
        }

        return $anomalies;
    }
}
