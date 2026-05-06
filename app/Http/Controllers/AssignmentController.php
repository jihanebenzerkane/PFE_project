<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AssignmentService;
use App\Services\PdfExportService;
use App\Models\Soutenance;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Projet;
use App\Models\AffectationSnapshot;
use App\Models\PlanningSnapshot;

class AssignmentController extends Controller
{
    public function __construct(
        protected AssignmentService  $assignmentService,
        protected PdfExportService   $pdfExportService,
    ) {}

    // ─── DASHBOARD ────────────────────────────────────────────────────────

    public function dashboard()
    {
        $stats = [
            'total_etudiants'   => Etudiant::count(),
            'total_enseignants' => Enseignant::count(),
            'total_soutenances' => Soutenance::count(),
        ];

        // Soutenances par filière (aggregated to short names)
        $rawFiliere = Etudiant::selectRaw('filiere, COUNT(*) as total')->groupBy('filiere')->get();
        $parFiliereData = [];
        foreach ($rawFiliere as $item) {
            $f = mb_strtoupper($item->filiere ?? '', 'UTF-8');
            $fShort = 'Autre';
            if (str_contains($f, 'TDIA') || str_contains($f, 'TRANSFORM') || str_contains($f, 'ARTIFIC')) {
                $fShort = 'TDIA';
            } elseif (str_contains($f, 'GI') || str_contains($f, 'GENIE') || str_contains($f, 'GÉNIE')) {
                $fShort = 'GI';
            } elseif (str_contains($f, 'ID') || str_contains($f, 'INGENIERIE') || str_contains($f, 'DONNÉES') || str_contains($f, 'DONNEES')) {
                $fShort = 'ID';
            }
            $parFiliereData[$fShort] = ($parFiliereData[$fShort] ?? 0) + $item->total;
        }
        $parFiliere = collect($parFiliereData);

        // Étudiants encadrés par professeur
        $parEncadrant = Enseignant::withCount('projets')
            ->having('projets_count', '>', 0)
            ->get();

        // Soutenances où le prof est dans le jury
        $parJury = Enseignant::withCount('jurys')
            ->having('jurys_count', '>', 0)
            ->get();

        return view('dashboard.index', compact('stats', 'parFiliere', 'parEncadrant', 'parJury'));
    }

    // ─── AFFECTATION ──────────────────────────────────────────────────────

    public function showAffectation()
    {
        $projets     = $this->canonicalProjects();
        $enseignants = Enseignant::all();
        $etudiants   = Etudiant::all(); // ← add this
        $snapshots   = AffectationSnapshot::latest()->get();
        $hasSnapshot = AffectationSnapshot::exists();

        return view('affectation.index', compact('projets', 'enseignants', 'etudiants', 'snapshots', 'hasSnapshot'));
    }

    public function runAffectation()
    {
        // Re-run only the encadrant assignment. Imported binomes live in one
        // Projet row, so deleting projects would split them into orphan solos.
        Projet::query()->update(['encadrant_id' => null]);
        AffectationSnapshot::query()->delete();

        $this->assignmentService->assignStudentsToEncadrants();

        $projets = $this->canonicalProjects();
        $data = $projets->map(function ($p) {
            $e1 = $p->etudiant;
            $e2 = $p->etudiant2;
            $bg = $this->pdfExportService->applyFiliereColor($e1->filiere ?? '');

            return [
                'etu_nom'        => $e1?->nom,
                'etu_prenom'     => $e1?->prenom,
                'etudiant'       => $e1 ? ($e1->nom . ' ' . $e1->prenom) : '',
                'etu2_nom'       => $e2?->nom,
                'etu2_prenom'    => $e2?->prenom,
                'etudiant2'      => $e2 ? ($e2->nom . ' ' . $e2->prenom) : '',
                'filiere'        => $e1?->filiere,
                'bg'             => $bg,
                'encadrant'      => $p->encadrant
                    ? ($p->encadrant->nom . ' ' . $p->encadrant->prenom)
                    : 'Non assigné',
                'enc_nom'        => $p->encadrant?->nom   ?? '',
                'enc_prenom'     => $p->encadrant?->prenom ?? '',
            ];
        })->values()->toArray();

        $etudiantsCount = $projets->sum(fn($p) => 1 + ($p->etudiant2_id ? 1 : 0));

        AffectationSnapshot::create([
            'label'           => 'Affectation du ' . now()->format('d/m/Y à H:i'),
            'data'            => $data,
            'etudiants_count' => $etudiantsCount,
        ]);

        return redirect()->route('affectation.index')
            ->with('success', $etudiantsCount . ' étudiants affectés avec succès.');
    }

    public function affectationHistory()
    {
        $snapshots = AffectationSnapshot::latest()->get();
        return view('affectation.history', compact('snapshots'));
    }

    // ─── PLANNING ─────────────────────────────────────────────────────────

    public function runAlgorithm(Request $request)
    {
        try {
            // Clean up old planning data (including creneaux from previous date selections)
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            \Illuminate\Support\Facades\DB::table('jury_enseignant')->truncate();
            \App\Models\Jury::truncate();
            \App\Models\Soutenance::truncate();
            \App\Models\Creneau::truncate();
            \App\Models\PlanningSnapshot::truncate();
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Validate and get user-chosen dates
            $dates = $request->input('dates', []);
            if (empty($dates)) {
                return redirect()->route('affectation.index')
                    ->with('error', 'Veuillez sélectionner au moins une date pour les soutenances.');
            }
            // Ensure dates are sorted
            sort($dates);

            // Automatically assign encadrants if they haven't been assigned yet
            $this->assignmentService->assignStudentsToEncadrants();

            $this->assignmentService->planifierCreneaux($dates);
            $this->assignmentService->runAssignment();
            $this->assignmentService->buildJuries();

            // Build and save snapshot
            $soutenances = Soutenance::with([
                'projet.etudiant',
                'projet.etudiant2',
                'projet.encadrant',
                'jury.enseignants',
                'creneau',
            ])->get();

            $data = $soutenances->map(function ($s) {
                $jury = $s->jury?->enseignants ?? collect();
                $president   = $jury->where('pivot.role', 'President')->first();
                $rapporteurs = $jury->where('pivot.role', 'Rapporteur');

                return [
                    'id'               => $s->id,
                    'etudiant_nom'     => $s->projet?->etudiant?->nom,
                    'etudiant_prenom'  => $s->projet?->etudiant?->prenom,
                    'etudiant2_nom'    => $s->projet?->etudiant2?->nom,
                    'etudiant2_prenom' => $s->projet?->etudiant2?->prenom,
                    'titre'            => $s->projet?->sujet ?? $s->projet?->titre,
                    'filiere'          => $s->projet?->etudiant?->filiere,
                    'encadrant'    => $s->projet?->encadrant
                        ? ('Dr. ' . $s->projet->encadrant->nom . ' ' . $s->projet->encadrant->prenom)
                        : 'N/A',
                    'president'    => $president
                        ? ('Dr. ' . $president->nom . ' ' . $president->prenom)
                        : 'N/A',
                    'examinateurs' => $rapporteurs->map(fn($r) => 'Dr. ' . $r->nom . ' ' . $r->prenom)->values()->toArray(),
                    'date'         => $s->creneau?->date?->format('d/m/Y'),
                    'date_sort'    => $s->creneau?->date?->format('Y-m-d'),
                    'heure_debut'  => $s->creneau?->heure_debut?->format('H:i'),
                    'heure_fin'    => $s->creneau?->heure_fin?->format('H:i'),
                    'salle'        => $s->salle,
                ];
            })->sortBy([
                ['date_sort', 'asc'],
                ['heure_debut', 'asc'],
            ])->values()->toArray();

            // Total scheduled students, not soutenances. A binome has one
            // Soutenance but both etudiant_id and etudiant2_id are scheduled.
            $totalEtudiants = Etudiant::count();
            $scheduledIds   = $this->scheduledStudentIds();
            $affectes       = count($scheduledIds);
            $nonAffectes    = max(0, $totalEtudiants - $affectes);
            $pct = $totalEtudiants > 0 ? round(($affectes / $totalEtudiants) * 100) : 0;

            PlanningSnapshot::create([
                'label'             => 'Planning du ' . now()->format('d/m/Y à H:i'),
                'data'              => $data,
                'soutenances_count' => $soutenances->count(),
            ]);

            if ($pct < 100) {
                // Build diagnostic report
                $nbSalles         = \App\Models\Salle::count();
                $nbDates          = count($dates);
                $nbCreneauxParJour = 7; // 09-12 + 14-18
                $capaciteMax      = $nbDates * $nbCreneauxParJour * $nbSalles;

                // Conformite must inspect both project student columns because
                // etudiant2 shares the same Projet/Soutenance/Jury as etudiant_id.
                $etudiantsNonAffectes = Etudiant::whereNotIn('id', $scheduledIds)->get();

                $diagnostic = [
                    'pct'               => $pct,
                    'total'             => $totalEtudiants,
                    'affectes'          => $affectes,
                    'non_affectes'      => $nonAffectes,
                    'nb_salles'         => $nbSalles,
                    'nb_dates'          => $nbDates,
                    'capacite_max'      => $capaciteMax,
                    'manque_capacite'   => max(0, $totalEtudiants - $capaciteMax),
                    'etudiants_manquants' => $etudiantsNonAffectes->map(function ($e) {
                        $projet = $this->projectForStudent($e);

                        return [
                            'nom'     => $e->nom,
                            'prenom'  => $e->prenom,
                            'filiere' => $e->filiere,
                            'encadrant' => $projet?->encadrant
                                ? ($projet->encadrant->nom . ' ' . $projet->encadrant->prenom)
                                : 'Non assigné',
                        ];
                    })->toArray(),
                ];

                session(['conformite_diagnostic' => $diagnostic]);
                \Illuminate\Support\Facades\Storage::put('conformite_diagnostic.json', json_encode($diagnostic));

                return redirect()->route('planning.results')
                    ->with('warning', "⚠️ Seulement {$pct}% des étudiants ont pu être planifiés ({$affectes}/{$totalEtudiants}). Consultez le <a href=\"" . route('conformite.index') . "\" class=\"alert-link fw-bold\">Contrôle de Conformité</a> pour plus de détails.");
            }

            // 100% success — clear any previous conformité diagnostic
            \Illuminate\Support\Facades\Storage::delete('conformite_diagnostic.json');

            return redirect()->route('planning.results')
                ->with('success', "✓ {$pct}% des étudiants affectés. Aucun conflit d'horaire détecté.");
        } catch (\Exception $e) {
            return redirect()->route('affectation.index')
                ->with('error', 'Erreur lors de la génération : ' . $e->getMessage());
        }
    }


    public function showResults()
    {
        $snapshot = PlanningSnapshot::latest()->first();

        if (!$snapshot) {
            return redirect()->route('affectation.index')
                ->with('info', 'Aucun planning généré. Lancez d\'abord l\'algorithme.');
        }

        $soutenances = collect($snapshot->data);
        $successMessage = session('success', '✓ Planning chargé depuis l\'historique.');
        $salles = \App\Models\Salle::all();
        $enseignants = \App\Models\Enseignant::all();

        return view('planning.results', compact('soutenances', 'snapshot', 'successMessage', 'salles', 'enseignants'));
    }

    public function planningHistory()
    {
        $snapshots = PlanningSnapshot::latest()->get();
        return view('planning.history', compact('snapshots'));
    }

    public function downloadSnapshot(string $type, int $id, string $format)
    {
        if ($type === 'affectation') {
            $snapshot = AffectationSnapshot::findOrFail($id);
        } else {
            $snapshot = PlanningSnapshot::findOrFail($id);
        }

        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView("pdf.{$type}_snapshot", [
                'snapshot' => $snapshot,
                'rows'     => collect($snapshot->data),
            ]);
            return $pdf->download("{$type}_{$id}.pdf");
        }

        if ($format === 'word') {
            return app(\App\Services\WordExportService::class)
                ->downloadSnapshot($snapshot, $type);
        }

        abort(404);
    }

    private function canonicalProjects()
    {
        $coveredAsEtudiant2 = Projet::whereNotNull('etudiant2_id')
            ->pluck('etudiant2_id')
            ->unique()
            ->values()
            ->toArray();

        return Projet::with(['etudiant', 'etudiant2', 'encadrant'])
            ->whereNotIn('etudiant_id', $coveredAsEtudiant2)
            ->get();
    }

    private function scheduledStudentIds(): array
    {
        return Projet::whereHas('soutenance')
            ->get()
            ->flatMap(fn($p) => array_filter([$p->etudiant_id, $p->etudiant2_id]))
            ->unique()
            ->values()
            ->toArray();
    }

    private function projectForStudent(Etudiant $etudiant): ?Projet
    {
        return Projet::with('encadrant')->where('etudiant2_id', $etudiant->id)->first()
            ?? Projet::with('encadrant')->where('etudiant_id', $etudiant->id)->first();
    }
}
