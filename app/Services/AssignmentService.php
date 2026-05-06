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
        protected CreneauRepository    $creneauRepo,
        protected SoutenanceRepository $soutenanceRepo,
    ) {}

    /**
     * STEP 1 — Assign an encadrant to every student who doesn't have one.
     * Creates a Projet record if the student doesn't have one yet.
     * Distributes encadrants in a round-robin fashion.
     */
    public function assignStudentsToEncadrants(): void
    {
        $enseignants = Enseignant::all()->shuffle();
        if ($enseignants->isEmpty()) return;

        $total = $enseignants->count();
        $index = 0;

        // 1. Ensure every student is attached to a project (fallback if manually added without one)
        $etudiants = Etudiant::all();
        $projets = Projet::all();

        // A binome is stored as ONE Projet row. The second student must be
        // treated as already assigned, otherwise the fallback below would
        // create an orphan solo Projet for that same student.
        $assignedEtudiantIds = $projets
            ->flatMap(fn($p) => [$p->etudiant_id, $p->etudiant2_id])
            ->filter()
            ->unique()
            ->values();

        foreach ($etudiants as $etudiant) {
            if (!$assignedEtudiantIds->contains($etudiant->id)) {
                Projet::create([
                    'titre'        => 'Projet PFE - ' . $etudiant->nom . ' ' . $etudiant->prenom,
                    'etudiant_id'  => $etudiant->id,
                    'encadrant_id' => $enseignants[$index % $total]->id,
                ]);
                $index++;
            }
        }

        // 2. Assign encadrants to all projects that don't have one yet
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
     * STEP 2 — Create 1-hour time slots for each selected date.
     * Morning  : 09:00 → 12:00 (3 slots)
     * Afternoon: 14:00 → 18:00 (4 slots)
     */
    public function planifierCreneaux(array $dates): void
    {
        $heures = [
            ['debut' => '09:00', 'fin' => '10:00'],
            ['debut' => '10:00', 'fin' => '11:00'],
            ['debut' => '11:00', 'fin' => '12:00'],
            ['debut' => '14:00', 'fin' => '15:00'],
            ['debut' => '15:00', 'fin' => '16:00'],
            ['debut' => '16:00', 'fin' => '17:00'],
            ['debut' => '17:00', 'fin' => '18:00'],
        ];

        foreach ($dates as $date) {
            foreach ($heures as $h) {
                $exists = Creneau::where('date', $date)
                    ->where('heure_debut', $h['debut'])
                    ->exists();

                if (!$exists) {
                    Creneau::create([
                        'date'        => $date,
                        'heure_debut' => $h['debut'],
                        'heure_fin'   => $h['fin'],
                        'capacite'    => 5,
                    ]);
                }
            }
        }
    }

    /**
     * STEP 3 — Assign each project to a Creneau + Salle.
     *
     * Constraints (in priority order):
     * 1. Free salle must exist in the slot
     * 2. Encadrant must respect 1-hour pause (no adjacent slot ±3600s)
     * 3. Equal day distribution for the encadrant (fewer encadrant soutenances first)
     * 4. Per-day filière diversity — prefer days that don't yet have this filière
     *
     * Projects are interleaved by filière (GI→TDIA→ID→…) for diversity.
     */
    public function runAssignment(): void
    {
        $creneaux = $this->creneauRepo->findAll();

        // Legacy orphan guard: if a student appears as etudiant2 in a binome
        // project, any separate Projet where they are etudiant_id must not be
        // scheduled. The binome shares one Projet, one Soutenance and one Jury.
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

        // Group by filière
        $filiereMap = ['gi' => [], 'tdia' => [], 'id' => [], 'other' => []];

        foreach ($projets as $projet) {
            $f = strtoupper($projet->etudiant?->filiere ?? '');
            if (str_contains($f, 'TDIA') || str_contains($f, 'TRANSFORM') || str_contains($f, 'ARTIFIC')) {
                $filiereMap['tdia'][] = $projet;
            } elseif ((str_contains($f, 'ING') && str_contains($f, 'DONN')) || str_contains($f, ' ID') || $f === 'ID') {
                $filiereMap['id'][] = $projet;
            } elseif ((str_contains($f, 'G') && str_contains($f, 'NIE')) || str_contains($f, 'INFORMATIQUE') || $f === 'GI') {
                $filiereMap['gi'][] = $projet;
            } else {
                $filiereMap['other'][] = $projet;
            }
        }

        // Interleave: GI[0], TDIA[0], ID[0], GI[1], TDIA[1], ID[1], …
        $queues  = array_values(array_filter($filiereMap, fn($q) => count($q) > 0));
        if (empty($queues)) return;

        $maxLen  = max(array_map('count', $queues));
        $ordered = [];
        for ($i = 0; $i < $maxLen; $i++) {
            foreach ($queues as $queue) {
                if (isset($queue[$i])) $ordered[] = $queue[$i];
            }
        }

        // Track per-day filière counts in memory so pickCreneauAndSalle can read it
        // Format: ['2026-06-22' => ['TDIA' => 2, 'GI' => 1, ...]]
        $perDayFiliereCount = [];

        // Cap simultaneous soutenances per slot to ensure the rapporteur pool
        // is never exhausted. Each soutenance needs 3 unique professors
        // (1 president + 2 rapporteurs), so floor(totalProfs / 3) - 1 gives
        // a safe ceiling that always leaves room for 2 rapporteurs.
        $totalProfs  = Enseignant::count();
        $maxPerSlot  = max(1, (int) floor($totalProfs / 3) - 1);

        foreach ($ordered as $projet) {
            $filiere = strtoupper($projet->etudiant?->filiere ?? '');
            $fShort  = $this->normalizeFiliere($filiere);

            $result = $this->pickCreneauAndSalle(
                $projet->encadrant_id,
                $creneaux,
                $fShort,
                $perDayFiliereCount,
                $maxPerSlot
            );
            if (!$result) continue;

            [$creneau, $salle] = $result;

            Soutenance::create([
                'projet_id'  => $projet->id,
                'creneau_id' => $creneau->id,
                'salle'      => $salle->nom,
            ]);

            // Update in-memory filière-per-day tracker
            $day = $creneau->date->format('Y-m-d');
            $perDayFiliereCount[$day][$fShort] = ($perDayFiliereCount[$day][$fShort] ?? 0) + 1;
        }
    }

    /**
     * STEP 4 — Build a jury for every soutenance.
     *
     * Jury = 1 President (the Encadrant) + 2 Rapporteurs.
     *
     * Rapporteur selection uses a LOAD-BALANCED queue:
     *   - Professors with fewer jury participations are picked first.
     *   - The queue is re-sorted by load after each jury is built.
     *
     * Three-level constraint cascade (never skip a soutenance):
     *   Level 1 (strict)   — 1-hour gap + no same exact slot
     *   Level 2 (relaxed)  — no same exact slot only
     *   Level 3 (absolute) — truly last resort: pick anyone not in this exact slot
     *
     * A jury is ALWAYS created; if fewer than 2 rapporteurs are found the
     * soutenance is still saved so it appears in the results (flagged in the view).
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

        if ($soutenances->isEmpty()) return;

        // Pre-load creneaux into memory
        $allCreneaux = Creneau::all()->keyBy('id');

        // Load-balanced queue — re-sorted after every jury assignment.
        // $juryCount tracks how many juries each professor participates in (as rapporteur).
        $allProfessors = Enseignant::orderBy('nom')->orderBy('prenom')->get();
        $juryCount = $allProfessors->pluck('id')->flip()->map(fn() => 0)->toArray();

        // Seed juryCount from existing jury_enseignant entries (in case of partial re-run)
        $existingCounts = DB::table('jury_enseignant')
            ->select('enseignant_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('enseignant_id')
            ->pluck('cnt', 'enseignant_id')
            ->toArray();
        foreach ($existingCounts as $profId => $cnt) {
            $juryCount[$profId] = (int) $cnt;
        }

        // Track slot occupation in memory
        $slotOccupiedExact = [];
        $slotOccupiedAdjacent = [];
        foreach (Soutenance::with('projet', 'creneau')->whereNotNull('creneau_id')->get() as $s) {
            $cId = $s->creneau_id;
            $eId = $s->projet?->encadrant_id;
            if ($eId && $cId) {
                $slotOccupiedExact[$cId][$eId] = true;

                // Pre-fill adjacent for existing encadrants
                $currentStart = strtotime($s->creneau->heure_debut);
                $currentDate = $s->creneau->date->format('Y-m-d');
                foreach ($allCreneaux as $c) {
                    if ($c->date->format('Y-m-d') === $currentDate) {
                        $diff = abs(strtotime($c->heure_debut) - $currentStart);
                        if ($diff <= 3600) {
                            $slotOccupiedAdjacent[$c->id][$eId] = true;
                        }
                    }
                }
            }
        }

        foreach ($soutenances as $soutenance) {
            $encadrantId    = $soutenance->projet?->encadrant_id;
            $currentCreneau = $soutenance->creneau;
            if (!$currentCreneau) continue;

            $currentDate  = $currentCreneau->date->format('Y-m-d');
            $currentStart = strtotime($currentCreneau->heure_debut);

            // Build conflict windows
            $adjacentCreneauIds = []; // same slot + ±1 hour
            $exactCreneauIds    = []; // same slot only
            foreach ($allCreneaux as $c) {
                if ($c->date->format('Y-m-d') !== $currentDate) continue;
                $diff = abs(strtotime($c->heure_debut) - $currentStart);
                if ($diff === 0) {
                    $exactCreneauIds[]    = $c->id;
                    $adjacentCreneauIds[] = $c->id;
                } elseif ($diff <= 3600) {
                    $adjacentCreneauIds[] = $c->id;
                }
            }

            // Build busy maps
            $busyAdjacent = $encadrantId ? [$encadrantId => true] : [];
            $busyExact    = $encadrantId ? [$encadrantId => true] : [];

            // Adjacent busy: includes anyone in exact slot OR adjacent slots
            foreach ($adjacentCreneauIds as $cId) {
                foreach ($slotOccupiedAdjacent[$cId] ?? [] as $profId => $_) {
                    $busyAdjacent[$profId] = true;
                }
            }
            // Exact busy: includes ONLY people busy in the exact same slot
            foreach ($exactCreneauIds as $cId) {
                foreach ($slotOccupiedExact[$cId] ?? [] as $profId => $_) {
                    $busyExact[$profId] = true;
                }
            }

            // Sort professors by jury load (fewest first) for equal distribution
            $sortedProfs = $allProfessors->sortBy(fn($p) => $juryCount[$p->id] ?? 0)->values();

            // ── Level 1: strict (1-hour gap + no same slot) ──────────────────
            $membres  = $this->pickRapporteurs($sortedProfs, $busyAdjacent, 2);

            // ── Level 2: relaxed (no same exact slot only) ───────────────────
            if (count($membres) < 2) {
                \Illuminate\Support\Facades\Log::info("Level 1 failed for P{$soutenance->projet_id} at {$currentCreneau->heure_debut}. BusyAdjacent count: " . count($busyAdjacent));
                $membres = $this->pickRapporteurs($sortedProfs, $busyExact, 2);
            }

            // ── Level 3: absolute last resort (only exclude same-slot profs) ──
            if (count($membres) < 2) {
                $hardBusy = $encadrantId ? [$encadrantId => true] : [];
                foreach ($exactCreneauIds as $cId) {
                    foreach ($slotOccupiedExact[$cId] ?? [] as $profId => $_) {
                        $hardBusy[$profId] = true;
                    }
                }
                $membres = $this->pickRapporteurs($sortedProfs, $hardBusy, 2);
            }

            // ── Always create the jury — never silently skip ──────────────────
            $jury = Jury::create([]);

            // Encadrant = President
            if ($encadrantId) {
                $jury->enseignants()->attach($encadrantId, ['role' => 'President']);
                foreach ($exactCreneauIds as $cId) {
                    $slotOccupiedExact[$cId][$encadrantId] = true;
                }
                foreach ($adjacentCreneauIds as $cId) {
                    $slotOccupiedAdjacent[$cId][$encadrantId] = true;
                }
            }

            // Rapporteurs
            foreach ($membres as $membre) {
                $jury->enseignants()->attach($membre->id, ['role' => 'Rapporteur']);
                foreach ($exactCreneauIds as $cId) {
                    $slotOccupiedExact[$cId][$membre->id] = true;
                }
                foreach ($adjacentCreneauIds as $cId) {
                    $slotOccupiedAdjacent[$cId][$membre->id] = true;
                }
                // Increment load counter for equal distribution tracking
                $juryCount[$membre->id] = ($juryCount[$membre->id] ?? 0) + 1;
            }

            $soutenance->update(['jury_id' => $jury->id]);
        }
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /**
     * Pick up to $needed professors from $sortedProfs who are not in $busyMap.
     * Returns a plain array of Enseignant instances.
     */
    private function pickRapporteurs($sortedProfs, array $busyMap, int $needed): array
    {
        $picked  = [];
        $localBusy = $busyMap; // copy so we can track within-jury duplicates

        foreach ($sortedProfs as $prof) {
            if (count($picked) >= $needed) break;
            if (!isset($localBusy[$prof->id])) {
                $picked[]              = $prof;
                $localBusy[$prof->id]  = true; // don't pick same person twice
            }
        }

        return $picked;
    }

    /**
     * Normalize a raw filière string to a short code: GI | TDIA | ID | AUTRE
     */
    private function normalizeFiliere(string $f): string
    {
        $f = strtoupper($f);
        if (str_contains($f, 'TDIA') || str_contains($f, 'TRANSFORM') || str_contains($f, 'ARTIFIC')) {
            return 'TDIA';
        }
        if ((str_contains($f, 'ING') && str_contains($f, 'DONN')) || str_contains($f, ' ID') || $f === 'ID') {
            return 'ID';
        }
        if ((str_contains($f, 'G') && str_contains($f, 'NIE')) || str_contains($f, 'INFORMATIQUE') || $f === 'GI') {
            return 'GI';
        }
        return 'AUTRE';
    }

    /**
     * Find the best (creneau, salle) pair for an encadrant.
     *
     * Sort priority:
     *  1. Days that DON'T yet have this filière (filière diversity per day)
     *  2. Days where the encadrant has FEWER soutenances (encadrant load balance)
     *  3. Earlier date
     *  4. Earlier time slot
     *
     * Hard constraints:
     *  - Free salle must exist
     *  - Encadrant must have 1-hour pause (no adjacent slot ±3600s)
     */
    private function pickCreneauAndSalle(
        int    $encadrantId,
        $creneaux,
        string $filiere,
        array  $perDayFiliereCount,
        int    $maxPerSlot = 999
    ): ?array {
        $busyCreneauxIds = Soutenance::whereHas('projet', fn($q) => $q->where('encadrant_id', $encadrantId))
            ->pluck('creneau_id')->toArray();

        $busyCreneaux = $creneaux->whereIn('id', $busyCreneauxIds);

        // Count how many soutenances this encadrant already has per date
        $perDayCount = [];
        foreach ($busyCreneaux as $bc) {
            $d = $bc->date->format('Y-m-d');
            $perDayCount[$d] = ($perDayCount[$d] ?? 0) + 1;
        }

        // Cache soutenance-per-slot counts to avoid N+1 queries
        $slotCount = Soutenance::selectRaw('creneau_id, COUNT(*) as cnt')
            ->groupBy('creneau_id')
            ->pluck('cnt', 'creneau_id')
            ->toArray();

        // Sort creneaux:
        //   1st — days missing this filière come first (0 = missing, 1 = present → ascending)
        //   2nd — fewest encadrant load first
        //   3rd — earlier date first
        //   4th — earlier time first
        $sorted = $creneaux->sortBy(function ($c) use ($perDayCount, $perDayFiliereCount, $filiere) {
            $d             = $c->date->format('Y-m-d');
            $hasFiliereDay = isset($perDayFiliereCount[$d][$filiere]) ? 1 : 0;
            return [
                $hasFiliereDay,              // 0 = day missing this filière → preferred
                $perDayCount[$d] ?? 0,       // fewest encadrant load
                $d,                          // earlier date
                $c->heure_debut,             // earlier time
            ];
        })->values();

        foreach ($sorted as $creneau) {
            // Enforce per-slot cap: skip if this slot already has maxPerSlot soutenances.
            // This guarantees the rapporteur pool can always fill every jury.
            $alreadyInSlot = $slotCount[$creneau->id] ?? 0;
            if ($alreadyInSlot >= $maxPerSlot) continue;

            // Skip if no salle is available
            $salle = $this->pickSalle($creneau);
            if (!$salle) continue;

            // Check 1-hour pause for the encadrant
            $conflict = false;
            foreach ($busyCreneaux as $busyCr) {
                if ($busyCr->date->format('Y-m-d') === $creneau->date->format('Y-m-d')) {
                    $diff = abs(strtotime($creneau->heure_debut) - strtotime($busyCr->heure_debut));
                    if ($diff <= 3600) {
                        $conflict = true;
                        break;
                    }
                }
            }

            if ($conflict) continue;

            // Update in-memory slot count so the next projet sees the updated state
            $slotCount[$creneau->id] = $alreadyInSlot + 1;

            return [$creneau, $salle];
        }

        return null;
    }

    private function pickSalle(Creneau $creneau): ?Salle
    {
        $sallesUtilisees = Soutenance::where('creneau_id', $creneau->id)
            ->pluck('salle')
            ->toArray();

        return Salle::whereNotIn('nom', $sallesUtilisees)->first();
    }
}
