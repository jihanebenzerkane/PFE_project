@extends('layouts.app')

@section('title', 'Tableau de Bord')

@push('styles')
<style>
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 24px; }
    .stat-card {
        background: white; border-radius: 14px; padding: 22px 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        display: flex; align-items: center; gap: 18px;
    }
    .stat-icon {
        width: 52px; height: 52px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
        flex-shrink: 0;
    }
    .stat-icon.blue   { background: #EFF6FF; }
    .stat-icon.purple { background: #F5F3FF; }
    .stat-icon.green  { background: #ECFDF5; }
    .stat-value { font-size: 2rem; font-weight: 700; color: #0F172A; line-height: 1; }
    .stat-label { font-size: 0.82rem; color: #64748B; margin-top: 4px; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
    .chart-wrap { position: relative; height: 220px; }

    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: 0.78rem; font-weight: 600; color: #64748B; padding: 10px 12px; border-bottom: 2px solid #F1F5F9; text-transform: uppercase; letter-spacing: 0.5px; }
    td { padding: 11px 12px; font-size: 0.875rem; border-bottom: 1px solid #F8FAFC; }
    tr:last-child td { border-bottom: none; }
    .badge {
        padding: 3px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600;
        display: inline-block;
    }
    .badge-tdia   { background: #F5F3FF; color: #6D28D9; }
    .badge-gi     { background: #EFF6FF; color: #1D4ED8; }
    .badge-id     { background: #ECFDF5; color: #065F46; }
    .badge-other  { background: #F1F5F9; color: #475569; }
</style>
@endpush

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">🎓</div>
        <div>
            <div class="stat-value">{{ $stats['total_etudiants'] }}</div>
            <div class="stat-label">Étudiants importés</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">👨‍🏫</div>
        <div>
            <div class="stat-value">{{ $stats['total_enseignants'] }}</div>
            <div class="stat-label">Enseignants / Encadrants</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">📅</div>
        <div>
            <div class="stat-value">{{ $stats['total_soutenances'] }}</div>
            <div class="stat-label">Soutenances planifiées</div>
        </div>
    </div>
</div>

<div class="two-col">
    {{-- Soutenances par filière --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">📊 Répartition par Filière</div>
        </div>
        <div class="chart-wrap">
            <canvas id="filiereChart"></canvas>
        </div>
    </div>

    {{-- Encadrants table --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">👥 Étudiants par Encadrant</div>
        </div>
        <div style="max-height: 220px; overflow-y: auto;">
            <table>
                <thead><tr><th>Encadrant</th><th>Étudiants</th></tr></thead>
                <tbody>
                    @forelse($parEncadrant as $prof)
                        <tr>
                            <td>{{ $prof->nom }} {{ $prof->prenom }}</td>
                            <td><span class="badge badge-other">{{ $prof->projets_count }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="2" style="color:#94A3B8; text-align:center;">Aucune affectation</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">⚖️ Participations Jury par Enseignant</div>
    </div>
    <div class="chart-wrap" style="height: 300px; padding: 20px;">
        @if($parJury->isEmpty())
            <div style="color:#94A3B8; text-align:center; padding-top: 100px;">Aucun jury généré</div>
        @else
            <canvas id="juryChart"></canvas>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('filiereChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($parFiliere->keys()) !!},
        datasets: [{
            label: 'Étudiants',
            data: {!! json_encode($parFiliere->values()) !!},
            backgroundColor: ['#818CF8','#34D399','#F472B6','#60A5FA','#FBBF24'],
            borderRadius: 8, borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#F1F5F9' } },
            x: { grid: { display: false } }
        }
    }
});

@if($parJury->isNotEmpty())
    const juryCtx = document.getElementById('juryChart');
    const profLabels = {!! json_encode($parJury->map(fn($p) => $p->nom . ' ' . substr($p->prenom, 0, 1) . '.')->values()) !!};
    const profData = {!! json_encode($parJury->pluck('jurys_count')->values()) !!};

    new Chart(juryCtx, {
        type: 'bar',
        data: {
            labels: profLabels,
            datasets: [{
                label: 'Participations Jury',
                data: profData,
                backgroundColor: '#34D399',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#F1F5F9' } },
                x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45, font: { size: 10 } }, grid: { display: false } }
            }
        }
    });
@endif
</script>
@endpush
