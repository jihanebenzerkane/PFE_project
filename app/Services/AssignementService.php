<?php

namespace App\Services;

use App\Models\Creneau;
use App\Models\Soutenance;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Repositories\CreneauRepository;
use App\Repositories\SoutenanceRepository;

class AssignmentService
{
    // Les dépendances — tout ce dont ce service a besoin
    public function __construct(
        protected CreneauRepository     $creneauRepo,
        protected SoutenanceRepository  $soutenanceRepo,
        protected ConstraintValidator   $validator,
    ) {}

    // ✅ Méthode principale — lance tout le processus d'affectation
    public function runAssignment(): void
    {
        // Récupérer tous les créneaux disponibles
        $creneaux = $this->creneauRepo->findAll();

        // Récupérer tous les étudiants sans soutenance
        $etudiants = Etudiant::whereDoesntHave('soutenance')->get();

        foreach ($etudiants as $etudiant) {
            // Chercher un créneau libre pour cet étudiant
            $creneau = $this->pickCreneau($etudiant, $creneaux);

            if (!$creneau) {
                // Pas de créneau disponible → on passe au suivant
                continue;
            }

            // Chercher une salle libre dans ce créneau
            $salle = $this->pickSalle($creneau);

            if (!$salle) {
                continue;
            }

            // Créer et sauvegarder la soutenance
            $soutenance = new Soutenance([
                'etudiant_id'  => $etudiant->id,
                'encadrant_id' => $etudiant->projet->encadrant_id,
                'creneau_id'   => $creneau->id,
                'salle_id'     => $salle->id,
                'langue'       => $etudiant->projet->langue_soutenance,
            ]);

            $this->soutenanceRepo->save($soutenance);
        }
    }

    // ✅ Assigner les étudiants à leurs encadrants
    public function assignStudentsToEncadrants(): void
    {
        $etudiants = Etudiant::all();

        foreach ($etudiants as $etudiant) {
            // Vérifier que l'encadrant n'a pas trop d'étudiants
            $encadrant = $etudiant->projet->encadrant;

            if (!$this->validator->validateQuotas($encadrant->id)) {
                // Quota dépassé → chercher un autre encadrant
                continue;
            }
        }
    }

    // ✅ Construire les jurys pour chaque soutenance
    public function buildJuries(): void
    {
        // Récupérer toutes les soutenances sans jury
        $soutenances = Soutenance::whereNull('jury_id')->get();

        foreach ($soutenances as $soutenance) {
            // Trouver un candidat jury disponible
            $jury = $this->pickCandidate($soutenance->etudiant);

            if (!$jury) {
                continue;
            }

            // Affecter le jury à la soutenance
            $soutenance->jury_id = $jury->id;
            $this->soutenanceRepo->save($soutenance);
        }
    }

    // ✅ Créer les créneaux automatiquement
    public function planifierCreneaux(): void
    {
        $dates  = ['2025-06-23', '2025-06-24', '2025-06-25'];
        $heures = ['09:00', '11:00', '14:00', '16:00'];

        foreach ($dates as $date) {
            foreach ($heures as $heure) {
                // Vérifier si ce créneau existe déjà
                $existe = Creneau::where('date', $date)
                    ->where('heure_debut', $heure)
                    ->exists();

                if (!$existe) {
                    $creneau = new Creneau([
                        'date'        => $date,
                        'heure_debut' => $heure,
                        'heure_fin'   => date('H:i', strtotime($heure . ' +1 hour')),
                        'capacite'    => 5,
                    ]);
                    $this->creneauRepo->save($creneau);
                }
            }
        }
    }

    // ✅ Choisir le meilleur créneau pour un étudiant (méthode privée)
    private function pickCreneau(Etudiant $etudiant, $creneaux): ?Creneau
    {
        foreach ($creneaux as $creneau) {
            // Est-ce que ce créneau a encore de la place ?
            if (!$this->creneauRepo->isSlotFree($creneau->id)) {
                continue;
            }

            // Est-ce que l'encadrant est libre dans ce créneau ?
            if (!$this->validator->validateNoConflict(
                $etudiant->projet->encadrant_id,
                $creneau->id
            )) {
                continue;
            }

            // Est-ce que l'équilibre journalier est respecté ?
            if (!$this->validator->validateDailyBalance(
                $etudiant->projet->encadrant_id,
                $creneau->id
            )) {
                continue;
            }

            // Ce créneau convient !
            return $creneau;
        }

        // Aucun créneau trouvé
        return null;
    }

    // ✅ Choisir un candidat jury pour cet étudiant (méthode privée)
    private function pickCandidate(Etudiant $etudiant): ?Enseignant
    {
        $encadrantId = $etudiant->projet->encadrant_id;

        // Chercher un enseignant disponible qui n'est pas l'encadrant
        return Enseignant::where('id', '!=', $encadrantId)
            ->where('is_responsable_pfe', false)
            ->get()
            ->first(function ($enseignant) use ($etudiant) {
                // Vérifier son quota
                return $this->validator->validateQuotas($enseignant->id);
            });
    }

    // ✅ Choisir une salle libre dans ce créneau (méthode privée)
    private function pickSalle(Creneau $creneau)
    {
        // Récupérer les salles déjà prises dans ce créneau
        $sallesPrises = Soutenance::where('creneau_id', $creneau->id)
            ->pluck('salle_id')
            ->toArray();

        // Retourner la première salle libre
        return \App\Models\Salle::whereNotIn('id', $sallesPrises)->first();
    }
}