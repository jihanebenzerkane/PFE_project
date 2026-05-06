<?php

namespace App\Services;

use App\Models\Creneau;
use App\Models\Soutenance;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Projet;
use App\Models\Jury;
use App\Models\Salle;
use App\Repositories\CreneauRepository;
use App\Repositories\SoutenanceRepository;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    public function __construct(
        protected CreneauRepository $creneauRepo,
        protected SoutenanceRepository $soutenanceRepo,
    ) {
    }

    /**
     * STEP 1 — Assigner un encadrant à chaque étudiant qui n'en a pas.
     */
    public function assignStudentsToEncadrants(): void
    {
        $enseignants = Enseignant::all()->shuffle();
        if ($enseignants->isEmpty())
            return;

        $total = $enseignants->count();
        $index = 0;

        $etudiants = Etudiant::all();
        $projets = Projet::all();

        $assignedEtudiantIds = $projets
            ->flatMap(fn($p) => [$p->etudiant_id, $p->etudiant2_id])
            ->filter()
            ->unique()
            ->values();

        foreach ($etudiants as $etudiant) {
            if (!$assignedEtudiantIds->contains($etudiant->id)) {
                Projet::create([
                    'titre' => 'Projet PFE - ' . $etudiant->nom . ' ' . $etudiant->prenom,
                    'etudiant_id' => $etudiant->id,
                    'encadrant_id' => $enseignants[$index % $total]->id,
                ]);
                $index++;
            }
        }

        $coveredAsEtudiant2 = $projets->pluck('etudiant2_id')->filter()->unique()->values()->toArray();
        $projetsSansEncadrant = Projet::whereNull('encadrant_id')
            ->whereNotIn('etudiant_id', $coveredAsEtudiant2)
            ->get();

        foreach ($projetsSansEncadrant as $projet) {
            $projet->update(['encadrant_id' => $enseignants[$index % $total]->id]);
            $index++;
        }
    }

    /**
     * STEP 2 — Calculer automatiquement les jours nécessaires
     * à partir d'une date de début entrée par l'utilisateur.
     *
     * Créneaux avec pause 1h intégrée :
     *   09h (soutenance 1h) → pause 10h → 11h (soutenance 1h) → pause déjeuner → 14h → pause 15h → 16h
     *   Soit 4 créneaux par jour : 09h, 11h, 14h, 16h
     *
     * Nombre de salles = dynamique depuis la base de données
     * Pas de soutenances le weekend
     */
    public function planifierCreneaux(string $dateDebut): void
    {
        // 4 créneaux par jour avec pause 1h entre chaque
        $heures = [
            ['debut' => '09:00', 'fin' => '10:00'],
            ['debut' => '11:00', 'fin' => '12:00'],
            ['debut' => '14:00', 'fin' => '15:00'],
            ['debut' => '16:00', 'fin' => '17:00'],
        ];

        $nbCreneauxParJour = count($heures); // 4

        // Nombre de salles réel depuis la base de données
        $nbSallesParCreneau = Salle::count();
        if ($nbSallesParCreneau === 0)
            $nbSallesParCreneau = 5; // valeur par défaut

        // Soutenances max par jour
        $nbSoutenancesParJour = $nbCreneauxParJour * $nbSallesParCreneau;

        // Compter le nombre de projets à soutenir
        $coveredAsEtudiant2 = Projet::whereNotNull('etudiant2_id')
            ->pluck('etudiant2_id')
            ->unique()
            ->values()
            ->toArray();

        $nbProjets = Projet::whereNotNull('encadrant_id')
            ->whereNotIn('etudiant_id', $coveredAsEtudiant2)
            ->count();

        if ($nbProjets === 0)
            return;

        // Calculer le nombre de jours nécessaires
        // ceil() arrondit vers le haut : 21 projets → 2 jours
        $nbJoursNecessaires = (int) ceil($nbProjets / $nbSoutenancesParJour);

        // Générer les dates en sautant les weekends
        $dates = [];
        $current = new \DateTime($dateDebut);

        while (count($dates) < $nbJoursNecessaires) {
            // 0 = dimanche, 6 = samedi
            $jourSemaine = (int) $current->format('w');

            if ($jourSemaine !== 0 && $jourSemaine !== 6) {
                $dates[] = $current->format('Y-m-d');
            }

            $current->modify('+1 day');
        }

        // Créer les créneaux pour chaque jour calculé
        foreach ($dates as $date) {
            foreach ($heures as $h) {
                $exists = Creneau::where('date', $date)
                    ->where('heure_debut', $h['debut'])
                    ->exists();

                if (!$exists) {
                    Creneau::create([
                        'date' => $date,
                        'heure_debut' => $h['debut'],
                        'heure_fin' => $h['fin'],
                        'capacite' => $nbSallesParCreneau,
                    ]);
                }
            }
        }
    }

    /**
     * STEP 3 — Affecter chaque projet à un créneau + salle.
     * Pause obligatoire de 1h après chaque soutenance pour l'encadrant.
     */
    public function runAssignment(): void
    {
        $creneaux = $this->creneauRepo->findAll();

        $coveredAsEtudiant2 = Projet::whereNotNull('etudiant2_id')
            ->pluck('etudiant2_id')
            ->unique()
            ->values()
            ->toArray();

        $projets = Projet::whereNotNull('encadrant_id')
            ->whereDoesntHave('soutenance')
            ->whereNotIn('etudiant_id', $coveredAsEtudiant2)
            ->with('etudiant')
            ->get();

        // Grouper par filière pour la diversité
        $filiereMap = ['gi' => [], 'tdia' => [], 'id' => [], 'other' => []];

        foreach ($projets as $projet) {
            $f = strtoupper($projet->etudiant?->filiere ?? '');
            if (str_contains($f, 'TDIA') || str_contains($f, 'TRANSFORM')) {
                $filiereMap['tdia'][] = $projet;
            } elseif (str_contains($f, 'ING') && str_contains($f, 'DONN')) {
                $filiereMap['id'][] = $projet;
            } elseif (str_contains($f, 'INFORMATIQUE') || $f === 'GI') {
                $filiereMap['gi'][] = $projet;
            } else {
                $filiereMap['other'][] = $projet;
            }
        }

        // Interleaver les filières pour la diversité
        $queues = array_values(array_filter($filiereMap, fn($q) => count($q) > 0));
        if (empty($queues))
            return;

        $maxLen = max(array_map('count', $queues));
        $ordered = [];
        for ($i = 0; $i < $maxLen; $i++) {
            foreach ($queues as $queue) {
                if (isset($queue[$i]))
                    $ordered[] = $queue[$i];
            }
        }

        $perDayFiliereCount = [];
        $totalProfs = Enseignant::count();
        $maxPerSlot = max(1, (int) floor($totalProfs / 3) - 1);

        foreach ($ordered as $projet) {
            $filiere = strtoupper($projet->etudiant?->filiere ?? '');
            $fShort = $this->normalizeFiliere($filiere);

            $result = $this->pickCreneauAndSalle(
                $projet->encadrant_id,
                $creneaux,
                $fShort,
                $perDayFiliereCount,
                $maxPerSlot
            );
            if (!$result)
                continue;

            [$creneau, $salle] = $result;

            Soutenance::create([
                'projet_id' => $projet->id,
                'encadrant_id' => $projet->encadrant_id,
                'creneau_id' => $creneau->id,
                'salle_id' => $salle->id,
                'salle' => $salle->nom,
                'langue' => $projet->langue_soutenance ?? 'Français',
            ]);

            $day = $creneau->date->format('Y-m-d');
            $perDayFiliereCount[$day][$fShort] = ($perDayFiliereCount[$day][$fShort] ?? 0) + 1;
        }
    }

    /**
     * STEP 4 — Construire les jurys.
     *
     * Pause obligatoire de 1h POUR TOUS les enseignants
     * (encadrant + rapporteurs) :
     *
     *   Soutenance à 09h → bloqué à 09h et 10h → libre à 11h ✅
     *   Soutenance à 11h → bloqué à 11h et 12h → libre à 14h ✅
     *   Soutenance à 14h → bloqué à 14h et 15h → libre à 16h ✅
     *   Soutenance à 16h → bloqué à 16h et 17h → fin ✅
     *
     * Entre 12h et 14h : pas de soutenances → pause automatique.
     * Jury = 1 Président (encadrant) + 2 Rapporteurs.
     */
    public function buildJuries(): void
    {
        $soutenances = Soutenance::whereNull('jury_id')
            ->with('projet.encadrant', 'creneau')
            ->join('creneaux', 'soutenances.creneau_id', '=', 'creneaux.id')
            ->orderBy('creneaux.date')
            ->orderBy('creneaux.heure_debut')
            ->select('soutenances.*')
            ->get();

        if ($soutenances->isEmpty())
            return;

        $allCreneaux = Creneau::all()->keyBy('id');
        $allProfessors = Enseignant::orderBy('nom')->orderBy('prenom')->get();

        // Compteur de jurys par enseignant pour équilibrer la charge
        $juryCount = $allProfessors
            ->pluck('id')
            ->flip()
            ->map(fn() => 0)
            ->toArray();

        // Charger les comptes existants depuis jury_enseignant
        $existingCounts = DB::table('jury_enseignant')
            ->select('enseignant_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('enseignant_id')
            ->pluck('cnt', 'enseignant_id')
            ->toArray();

        foreach ($existingCounts as $profId => $cnt) {
            $juryCount[$profId] = (int) $cnt;
        }

        // Tracker les créneaux occupés en mémoire
        // $slotOccupied[creneau_id][enseignant_id] = true
        $slotOccupied = [];

        // Pré-remplir avec les encadrants des soutenances existantes
        foreach (Soutenance::whereNotNull('creneau_id')
            ->whereNotNull('encadrant_id')
            ->get() as $s) {
            $slotOccupied[$s->creneau_id][$s->encadrant_id] = true;
        }

        // Pré-remplir avec les rapporteurs existants
        $existingJuryMembers = DB::table('jury_enseignant')
            ->join('juries', 'jury_enseignant.jury_id', '=', 'juries.id')
            ->join('soutenances', 'juries.id', '=', 'soutenances.jury_id')
            ->whereNotNull('soutenances.creneau_id')
            ->select('soutenances.creneau_id', 'jury_enseignant.enseignant_id')
            ->get();

        foreach ($existingJuryMembers as $member) {
            $slotOccupied[$member->creneau_id][$member->enseignant_id] = true;
        }

        foreach ($soutenances as $soutenance) {
            $encadrantId = $soutenance->encadrant_id;
            $currentCreneau = $soutenance->creneau;
            if (!$currentCreneau)
                continue;

            $currentDate = $currentCreneau->date->format('Y-m-d');
            $currentStart = strtotime($currentCreneau->heure_debut);

            /**
             * Trouver les créneaux bloqués pour la pause de 1h :
             *   diff == 0     → même créneau (évidemment bloqué)
             *   diff == 3600  → créneau suivant (pendant la pause obligatoire)
             */
            $creneauxBloquesIds = [];
            foreach ($allCreneaux as $c) {
                if ($c->date->format('Y-m-d') !== $currentDate)
                    continue;
                $diff = strtotime($c->heure_debut) - $currentStart;
                if ($diff === 0 || $diff === 3600) {
                    $creneauxBloquesIds[] = $c->id;
                }
            }

            // Construire la liste des enseignants occupés
            $enseignantsBusy = [];

            // L'encadrant est toujours occupé
            if ($encadrantId) {
                $enseignantsBusy[$encadrantId] = true;
            }

            // Ajouter les enseignants dans les créneaux bloqués
            foreach ($creneauxBloquesIds as $cBlockId) {
                foreach ($slotOccupied[$cBlockId] ?? [] as $profId => $_) {
                    $enseignantsBusy[$profId] = true;
                }
            }

            // Trier les profs par charge (moins de jurys = priorité)
            $sortedProfs = $allProfessors
                ->sortBy(fn($p) => $juryCount[$p->id] ?? 0)
                ->values();

            // Niveau 1 : pause stricte 1h respectée
            $membres = $this->pickRapporteurs($sortedProfs, $enseignantsBusy, 2);

            // Niveau 2 : si pas assez → seulement même créneau exact bloqué
            if (count($membres) < 2) {
                $busyExact = $encadrantId ? [$encadrantId => true] : [];
                foreach ($slotOccupied[$currentCreneau->id] ?? [] as $profId => $_) {
                    $busyExact[$profId] = true;
                }
                $membres = $this->pickRapporteurs($sortedProfs, $busyExact, 2);
            }

            // Niveau 3 : dernier recours → seulement exclure l'encadrant
            if (count($membres) < 2) {
                $hardBusy = $encadrantId ? [$encadrantId => true] : [];
                $membres = $this->pickRapporteurs($sortedProfs, $hardBusy, 2);
            }

            // Créer le jury dans la table "juries"
            $jury = Jury::create([]);

            // Encadrant = Président
            if ($encadrantId) {
                $jury->enseignants()->attach($encadrantId, ['role' => 'President']);
                $slotOccupied[$currentCreneau->id][$encadrantId] = true;
            }

            // Rapporteurs
            foreach ($membres as $membre) {
                $jury->enseignants()->attach($membre->id, ['role' => 'Rapporteur']);
                $slotOccupied[$currentCreneau->id][$membre->id] = true;
                $juryCount[$membre->id] = ($juryCount[$membre->id] ?? 0) + 1;
            }

            // Mettre à jour jury_id dans soutenances
            $soutenance->update(['jury_id' => $jury->id]);
        }
    }

    // ─── Helpers privés ───────────────────────────────────────────────────

    /**
     * Sélectionner $needed rapporteurs qui ne sont pas dans $busyMap.
     */
    private function pickRapporteurs($sortedProfs, array $busyMap, int $needed): array
    {
        $picked = [];
        $localBusy = $busyMap;

        foreach ($sortedProfs as $prof) {
            if (count($picked) >= $needed)
                break;
            if (!isset($localBusy[$prof->id])) {
                $picked[] = $prof;
                $localBusy[$prof->id] = true;
            }
        }

        return $picked;
    }

    /**
     * Normaliser la filière en code court.
     */
    private function normalizeFiliere(string $f): string
    {
        $f = strtoupper($f);
        if (str_contains($f, 'TDIA') || str_contains($f, 'TRANSFORM'))
            return 'TDIA';
        if (str_contains($f, 'ING') && str_contains($f, 'DONN'))
            return 'ID';
        if (str_contains($f, 'INFORMATIQUE') || $f === 'GI')
            return 'GI';
        return 'AUTRE';
    }

    /**
     * Trouver le meilleur (créneau, salle) pour un encadrant.
     *
     * Pause obligatoire 1h pour l'encadrant :
     *   diff <= 3600 entre deux soutenances = BLOQUÉ
     */
    private function pickCreneauAndSalle(
        int $encadrantId,
        $creneaux,
        string $filiere,
        array $perDayFiliereCount,
        int $maxPerSlot = 999
    ): ?array {
        // Créneaux où l'encadrant a déjà une soutenance
        $busyCreneaux = Soutenance::where('encadrant_id', $encadrantId)
            ->with('creneau')
            ->get()
            ->map(fn($s) => $s->creneau)
            ->filter();

        // Compter les soutenances de l'encadrant par jour
        $perDayCount = [];
        foreach ($busyCreneaux as $bc) {
            $d = $bc->date->format('Y-m-d');
            $perDayCount[$d] = ($perDayCount[$d] ?? 0) + 1;
        }

        // Cache du nombre de soutenances par créneau
        $slotCount = Soutenance::selectRaw('creneau_id, COUNT(*) as cnt')
            ->groupBy('creneau_id')
            ->pluck('cnt', 'creneau_id')
            ->toArray();

        // Trier les créneaux selon les priorités
        $sorted = $creneaux->sortBy(function ($c) use ($perDayCount, $perDayFiliereCount, $filiere) {
            $d = $c->date->format('Y-m-d');
            $hasFiliereDay = isset($perDayFiliereCount[$d][$filiere]) ? 1 : 0;
            return [
                $hasFiliereDay,        // jours sans cette filière en priorité
                $perDayCount[$d] ?? 0, // moins de charge encadrant en priorité
                $d,                    // date la plus proche en priorité
                $c->heure_debut,       // heure la plus tôt en priorité
            ];
        })->values();

        foreach ($sorted as $creneau) {
            // Vérifier le cap par créneau
            $alreadyInSlot = $slotCount[$creneau->id] ?? 0;
            if ($alreadyInSlot >= $maxPerSlot)
                continue;

            // Vérifier qu'il y a une salle libre
            $salle = $this->pickSalle($creneau);
            if (!$salle)
                continue;

            // Vérification pause obligatoire 1h pour l'encadrant
            $conflict = false;
            $creneauDate = $creneau->date->format('Y-m-d');
            $creneauStart = strtotime($creneau->heure_debut);

            foreach ($busyCreneaux as $busyCr) {
                if ($busyCr->date->format('Y-m-d') !== $creneauDate)
                    continue;
                $diff = abs($creneauStart - strtotime($busyCr->heure_debut));
                // Bloqué si diff <= 1h
                if ($diff <= 3600) {
                    $conflict = true;
                    break;
                }
            }

            if ($conflict)
                continue;

            // Mettre à jour le cache
            $slotCount[$creneau->id] = $alreadyInSlot + 1;

            return [$creneau, $salle];
        }

        return null;
    }

    /**
     * Trouver une salle libre dans ce créneau.
     * Utilise la colonne "salle" (string) de la table soutenances.
     */
    private function pickSalle(Creneau $creneau): ?Salle
    {
        $sallesUtilisees = Soutenance::where('creneau_id', $creneau->id)
            ->pluck('salle')
            ->toArray();

        return Salle::whereNotIn('nom', $sallesUtilisees)->first();
    }
}