@extends('layouts.app')

@section('title', 'Contrôle de Conformité')

@push('styles')
<style>
    .conformite-header { margin-bottom: 28px; }
    .conformite-header h3 { font-weight: 700; color: #0f172a; }

    /* Score gauge */
    .score-card {
        background: white; border-radius: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        padding: 28px 32px; margin-bottom: 20px;
        display: flex; align-items: center; gap: 32px;
    }
    .score-circle {
        width: 110px; height: 110px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: 1.8rem; font-weight: 800;
    }
    .score-ok    { background: #dcfce7; color: #16a34a; }
    .score-warn  { background: #fef9c3; color: #ca8a04; }
    .score-error { background: #fee2e2; color: #dc2626; }

    /* Anomaly cards */
    .anomaly-card {
        background: white; border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.07);
        padding: 20px 24px; margin-bottom: 14px;
        border-left: 4px solid #ef4444;
    }
    .anomaly-card.info { border-left-color: #f59e0b; }
    .anomaly-title { font-weight: 700; color: #0f172a; font-size: 0.95rem; margin-bottom: 4px; }
    .anomaly-desc  { color: #64748b; font-size: 0.85rem; line-height: 1.5; }

    /* Students table */
    .student-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .student-table thead th { padding: 10px 14px; background: #f8fafc; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; text-align: left; }
    .student-table tbody td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .student-table tbody tr:hover { background: #fafafa; }

    .badge-filiere { padding: 3px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; }
    .f-tdia { background: #dcfce7; color: #166534; }
    .f-gi   { background: #dbeafe; color: #1e40af; }
    .f-id   { background: #f3e8ff; color: #6b21a8; }

    .btn-back { color: #475569; background: #f1f5f9; border: none; border-radius: 8px; padding: 9px 18px; font-weight: 600; font-size: 0.875rem; }
    .btn-back:hover { background: #e2e8f0; }
</style>
@endpush

@section('content')

<div class="conformite-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h3>🔍 Contrôle de Conformité du Planning</h3>
        <div class="text-muted small">Analyse des anomalies et contraintes non satisfaites lors de la génération</div>
    </div>
    <a href="{{ route('planning.results') }}" class="btn btn-back">← Retour au Planning</a>
</div>

{{-- Score global --}}
<div class="score-card">
    <div class="score-circle {{ $diagnostic['pct'] >= 90 ? 'score-ok' : ($diagnostic['pct'] >= 60 ? 'score-warn' : 'score-error') }}">
        {{ $diagnostic['pct'] }}%
    </div>
    <div>
        <div style="font-size:1.1rem; font-weight:700; color:#0f172a;">
            {{ $diagnostic['affectes'] }} / {{ $diagnostic['total'] }} étudiants planifiés
        </div>
        <div class="text-muted small mt-1">
            {{ $diagnostic['non_affectes'] }} étudiant(s) n'ont pas pu être affectés à un créneau.
        </div>
        <div class="mt-2">
            @if($diagnostic['pct'] == 100)
                <span class="badge bg-success">✓ Planning complet — aucune anomalie</span>
            @elseif($diagnostic['pct'] >= 75)
                <span class="badge bg-warning text-dark">⚠ Planning partiel — corrections recommandées</span>
            @else
                <span class="badge bg-danger">✗ Planning incomplet — action requise</span>
            @endif
        </div>
    </div>
</div>

{{-- Anomalies détectées --}}
@if($diagnostic['non_affectes'] > 0)
<h5 class="fw-bold mb-3" style="color:#0f172a;">⚠ Anomalies Détectées</h5>

@if($diagnostic['nb_salles'] == 0)
    <div class="anomaly-card">
        <div class="anomaly-title">❌ Aucune salle configurée</div>
        <div class="anomaly-desc">
            Aucune salle de soutenance n'est enregistrée dans le système. Sans salle, aucun étudiant ne peut être planifié.
            <br><strong>Solution :</strong> Ajoutez des salles via le panneau "Salles" sur la page Planning.
        </div>
    </div>
@else
    @if($diagnostic['manque_capacite'] > 0)
        <div class="anomaly-card">
            <div class="anomaly-title">❌ Nombre de salles ou de jours insuffisant</div>
            <div class="anomaly-desc">
                Avec <strong>{{ $diagnostic['nb_salles'] }} salle(s)</strong> et <strong>{{ $diagnostic['nb_dates'] }} jour(s)</strong> sélectionné(s),
                la capacité maximale théorique est de <strong>{{ $diagnostic['capacite_max'] }} soutenances</strong>
                ({{ $diagnostic['nb_dates'] }} jours × 7 créneaux/jour × {{ $diagnostic['nb_salles'] }} salles).
                <br>
                Il manque <strong>{{ $diagnostic['manque_capacite'] }} créneau(x)</strong> pour planifier tous les étudiants.
                <br><br>
                <strong>Ce qui cause :</strong> {{ $diagnostic['non_affectes'] }} étudiant(s) sans soutenance planifiée.
                <br><br>
                <strong>Solutions possibles :</strong>
                <ul class="mt-1 mb-0">
                    <li>Ajouter <strong>{{ ceil($diagnostic['manque_capacite'] / (7 * $diagnostic['nb_salles'])) }} jour(s)</strong> de soutenance supplémentaire(s)</li>
                    <li>Ou ajouter <strong>{{ ceil($diagnostic['manque_capacite'] / (7 * $diagnostic['nb_dates'])) }} salle(s)</strong> supplémentaire(s)</li>
                </ul>
            </div>
        </div>
    @else
        <div class="anomaly-card info">
            <div class="anomaly-title">⚠ Conflits horaires des encadrants</div>
            <div class="anomaly-desc">
                La capacité totale est théoriquement suffisante (<strong>{{ $diagnostic['capacite_max'] }}</strong> places pour <strong>{{ $diagnostic['total'] }}</strong> étudiants),
                mais des <strong>contraintes de repos des encadrants</strong> (pause d'1 heure entre deux soutenances) ont empêché certains créneaux d'être utilisés.
                <br><br>
                <strong>Ce qui cause :</strong> {{ $diagnostic['non_affectes'] }} étudiant(s) sans soutenance planifiée car leur encadrant
                n'avait pas de créneau disponible respectant la pause obligatoire.
                <br><br>
                <strong>Solutions possibles :</strong>
                <ul class="mt-1 mb-0">
                    <li>Ajouter des jours de soutenance supplémentaires</li>
                    <li>Redistribuer les étudiants entre encadrants avant de relancer</li>
                </ul>
            </div>
        </div>
    @endif
@endif

{{-- Table of unscheduled students --}}
@if(!empty($diagnostic['etudiants_manquants']))
<div class="card mt-4" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header" style="background: #fef2f2; border-bottom: 1px solid #fecaca;">
        <div class="card-title" style="color: #dc2626;">
            ❌ Étudiants non planifiés ({{ count($diagnostic['etudiants_manquants']) }})
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table class="student-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Étudiant</th>
                    <th>Filière</th>
                    <th>Encadrant</th>
                    <th>Raison probable</th>
                </tr>
            </thead>
            <tbody>
                @foreach($diagnostic['etudiants_manquants'] as $i => $etudiant)
                    @php
                        $f = mb_strtoupper($etudiant['filiere'] ?? '', 'UTF-8');
                        $fShort = '—'; $fClass = '';
                        if (str_contains($f, 'TDIA') || str_contains($f, 'ARTIFIC')) { $fShort = 'TDIA'; $fClass = 'f-tdia'; }
                        elseif (str_contains($f, 'GI') || str_contains($f, 'GENIE')) { $fShort = 'GI'; $fClass = 'f-gi'; }
                        elseif (str_contains($f, 'ID') || str_contains($f, 'INGENIER')) { $fShort = 'ID'; $fClass = 'f-id'; }
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td class="fw-bold">{{ $etudiant['nom'] }} {{ $etudiant['prenom'] }}</td>
                        <td><span class="badge-filiere {{ $fClass }}">{{ $fShort }}</span></td>
                        <td>{{ $etudiant['encadrant'] }}</td>
                        <td class="text-danger">
                            @if($diagnostic['manque_capacite'] > 0)
                                Capacité insuffisante (salles/jours)
                            @else
                                Encadrant sans créneau libre (contrainte repos)
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@else
<div class="anomaly-card" style="border-left-color: #22c55e; background: #f0fdf4;">
    <div class="anomaly-title" style="color: #16a34a;">✓ Aucune anomalie détectée</div>
    <div class="anomaly-desc">Tous les {{ $diagnostic['total'] }} étudiants ont été planifiés avec succès.</div>
</div>
@endif

@endsection
