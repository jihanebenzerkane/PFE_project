<?php

namespace App\Services;

use App\Models\Creneau;
use App\Models\Soutenance;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Jury;
use App\Models\Projet;
use Carbon\Carbon;

class AssignmentService
{
    public function __construct(
        protected ConstraintValidator $validator
    ) {}

    // ✅ Méthode principale — lance tout le processus d'affectation
    public function runAssignment(): void
    {
        // Récupérer tous les créneaux disponibles
        $creneaux = Creneau::all();

        // L'entité liée à une soutenance est le PROJET (et non l'étudiant directement)
        $projets = Projet::whereDoesntHave('soutenance')->get();

        foreach ($projets as $projet) {
            // Chercher un créneau libre pour ce projet
            $creneau = $this->pickCreneau($projet, $creneaux);

            if (!$creneau) continue;

            // Chercher une salle libre dans ce créneau
            $salle = $this->pickSalle($creneau);

            // Créer et sauvegarder la soutenance avec la bonne structure de BDD
            $soutenance = Soutenance::create([
                'projet_id'  => $projet->id,
                'creneau_id' => $creneau->id,
                'salle'      => $salle,
            ]);
        }
    }

    // ✅ Assigner les étudiants (projets) à leurs encadrants avec équilibrage de charge
    public function assignStudentsToEncadrants(): void
    {
        // 1. Récupérer les projets sans encadrant
        $unassignedProjets = Projet::whereNull('encadrant_id')->get();

        if ($unassignedProjets->isEmpty()) {
            return; // Tout le monde a déjà un encadrant !
        }

        // 2. Récupérer tous les enseignants avec leur nombre actuel de projets
        $enseignants = Enseignant::withCount('projets')->get();
        
        if ($enseignants->isEmpty()) {
            return; // Impossible d'assigner sans professeurs
        }

        foreach ($unassignedProjets as $projet) {
            // 3. Trier les enseignants par ceux qui ont le MOINS de projets (Load Balancing Automatique)
            $enseignants = $enseignants->sortBy('projets_count');

            // Prendre le professeur le moins chargé
            $bestEnseignant = $enseignants->first();

            // 4. L'assigner au projet
            $projet->encadrant_id = $bestEnseignant->id;
            $projet->save();

            // 5. Mettre à jour le compteur localement pour la boucle suivante
            $bestEnseignant->projets_count += 1;
        }

        // 6. Validation de sécurité finale
        $this->validator->validateEncadrementAverage();
    }

    // ✅ Construire les jurys pour chaque soutenance
    public function buildJuries(): void
    {
        // Récupérer toutes les soutenances sans jury
        $soutenances = Soutenance::whereNull('jury_id')->get();

        foreach ($soutenances as $soutenance) {
            // Trouver un enseignant disponible pour être jury (Rapporteur)
            $rapporteur = $this->pickCandidate($soutenance);

            if (!$rapporteur) continue;

            // 1. Créer le Jury dans la base de données
            $jury = Jury::create();

            // 2. Lier le Rapporteur au Jury via la table Pivot (jury_enseignant)
            $jury->enseignants()->attach($rapporteur->id, ['role' => 'Rapporteur']);

            // 3. Lier le Jury à la Soutenance
            $soutenance->jury_id = $jury->id;
            $soutenance->save();
        }
    }

    // ✅ Créer les créneaux automatiquement
    public function planifierCreneaux(): void
    {
        $dates  = ['2026-06-22', '2026-06-23', '2026-06-24'];
        $heures = ['09:00:00', '11:00:00', '14:00:00', '16:00:00'];

        foreach ($dates as $date) {
            foreach ($heures as $heure) {
                // Utilisation de la colonne 'heure' au lieu de 'heure_debut'
                $existe = Creneau::where('date', $date)
                    ->where('heure', $heure)
                    ->exists();

                if (!$existe) {
                    Creneau::create([
                        'date'  => $date,
                        'heure' => $heure,
                    ]);
                }
            }
        }
    }

    // ✅ Choisir le meilleur créneau pour un projet (méthode privée)
    private function pickCreneau(Projet $projet, $creneaux): ?Creneau
    {
        foreach ($creneaux as $creneau) {
            // L'encadrant est-il libre dans ce créneau ? (Validation Double-Booking)
            if (!$this->validator->validateNoConflict($projet->encadrant_id, $creneau->id)) {
                continue;
            }

            // La pause minimale de 1 heure est-elle respectée pour cet encadrant ?
            if (!$this->validator->validatePauseMinimale($projet->encadrant_id)) {
                continue;
            }

            return $creneau;
        }

        return null;
    }

    // ✅ Choisir un candidat jury pour cette soutenance (méthode privée)
    private function pickCandidate(Soutenance $soutenance): ?Enseignant
    {
        $encadrantId = $soutenance->projet->encadrant_id;

        // Chercher un enseignant disponible qui n'est pas l'encadrant
        // is_responsable_pfe a été supprimé de la BDD, on filtre juste l'encadrant
        return Enseignant::where('id', '!=', $encadrantId)
            ->get()
            ->first(function ($enseignant) use ($soutenance) {
                // Vérifier qu'il n'est pas déjà occupé sur ce créneau exact !
                return $this->validator->validateNoConflict($enseignant->id, $soutenance->creneau_id);
            });
    }

    // ✅ Choisir une salle libre dans ce créneau (méthode privée)
    private function pickSalle(Creneau $creneau): string
    {
        $sallesPrises = Soutenance::where('creneau_id', $creneau->id)
            ->whereNotNull('salle')
            ->pluck('salle')
            ->toArray();

        // Les salles sont stockées en format String dans la table soutenances
        $sallesDisponibles = ['S4A', 'S5A', 'S16A', 'S17A' , 'Amphi A'];

        foreach ($sallesDisponibles as $salle) {
            if (!in_array($salle, $sallesPrises)) {
                return $salle;
            }
        }

        return 'Salle ' . rand(10, 99); // Fallback si tout est plein
    }
}
