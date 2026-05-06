@extends('layouts.app')

@section('title', 'Affectation des Encadrants')

@push('scripts')
<style>
    #planningModal {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45);
        z-index:9999; align-items:center; justify-content:center;
    }
    .modal-box {
        background:#fff; border-radius:16px; padding:28px 32px; width:460px;
        max-width:95vw; box-shadow:0 20px 60px rgba(0,0,0,0.2);
    }
    .modal-box h3 { font-size:1.05rem; font-weight:700; color:#0F172A; margin:0 0 6px; }
    .modal-box p  { font-size:0.82rem; color:#64748B; margin:0 0 18px; }
    .modal-footer { display:flex; gap:10px; justify-content:flex-end; }
    .info-box {
        background:#EFF6FF; border:1px solid #BFDBFE; border-radius:10px;
        padding:12px 14px; margin-bottom:18px; font-size:0.82rem; color:#1D4ED8;
    }
    .info-box ul { margin:6px 0 0 14px; padding:0; }
    .info-box ul li { margin-bottom:3px; }
    .date-input-wrapper { display:flex; flex-direction:column; gap:6px; margin-bottom:20px; }
    .date-input-wrapper label { font-size:0.82rem; font-weight:600; color:#374151; }
    .date-input-wrapper input[type=date] {
        padding:10px 14px; border:1.5px solid #E2E8F0; border-radius:8px;
        font-size:0.875rem; color:#0F172A; width:100%;
    }
    .date-input-wrapper input[type=date]:focus {
        outline:none; border-color:#3B82F6;
        box-shadow:0 0 0 3px rgba(59,130,246,0.1);
    }
</style>

<div id="planningModal">
    <div class="modal-box">
        <h3>📅 Date de début des soutenances</h3>
        <p>Entrez uniquement la date de début. Le programme calculera automatiquement le nombre de jours nécessaires.</p>

        <div class="info-box">
            ℹ️ Le programme va automatiquement :
            <ul>
                <li>Calculer le nombre de jours nécessaires</li>
                <li>Créer 4 créneaux par jour : 09h, 11h, 14h, 16h</li>
                <li>Respecter la pause de 1h entre chaque soutenance</li>
                <li>Sauter les weekends automatiquement</li>
            </ul>
        </div>

        <form action="{{ route('planning.run') }}" method="POST" id="planningForm">
            @csrf
            <div class="date-input-wrapper">
                <label for="date_debut">📆 Date de début</label>
                <input
                    type="date"
                    id="date_debut"
                    name="date_debut"
                    required
                    min="{{ date('Y-m-d') }}"
                >
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-sm"
                    onclick="document.getElementById('planningModal').style.display='none'">
                    Annuler
                </button>
                <button type="submit" class="btn btn-success">▶ Générer</button>
            </div>
        </form>
    </div>
</div>
@endpush

@section('content')


{{-- ── TOP ACTION BAR ── --}}
<div class="action-topbar">
    <div>
        <div class="action-topbar-title">Affectation des Encadrants</div>
        <div class="action-topbar-sub">Distribue automatiquement les encadrants aux étudiants non affectés.</div>
    </div>
    <div class="action-topbar-btns">

        {{-- Always shown --}}
        <a href="{{ route('affectation.history') }}" class="btn btn-outline btn-sm">📋 Historique</a>

        {{-- Lancer / Relancer Affectation --}}
        <form action="{{ route('affectation.run') }}" method="POST" style="display:inline;">
            @csrf
            @if($hasSnapshot)
                <button type="submit" class="btn btn-primary">🔄 Relancer l'Affectation</button>
            @else
                <button type="submit" class="btn btn-primary" style="background:#4F46E5; font-size:1rem; padding:10px 22px;">
                    ▶ Lancer l'Affectation
                </button>
            @endif
        </form>

        <button type="button" class="btn btn-success" onclick="document.getElementById('planningModal').style.display='flex'">📅 Générer le Planning</button>

        {{-- Download buttons — only when an affectation snapshot exists --}}
        @if($hasSnapshot)
            <a href="{{ route('export.affectation') }}" class="btn btn-danger btn-sm">📄 PDF</a>
            <a href="{{ route('export.affectation.word') }}" class="btn btn-outline btn-sm">📝 Word</a>
        @endif

    </div>
</div>

<div class="page-layout">

    {{-- Left: Professor list --}}
    <div class="prof-panel">
        <h3>👨‍🏫 Enseignants</h3>
        @forelse($enseignants as $ens)
            <div class="prof-item">
                <div class="prof-avatar">{{ strtoupper(substr($ens->nom, 0, 1)) }}</div>
                <span>{{ $ens->nom }} {{ $ens->prenom }}</span>
            </div>
        @empty
            <p style="font-size:0.82rem; color:#94A3B8;">Aucun enseignant importé.</p>
        @endforelse
    </div>

    {{-- Right: Main content --}}
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">Liste des Étudiants — Affectation Encadrants</div>
                <span style="font-size:0.82rem; color:#64748B;">{{ $etudiants->count() }} étudiant(s)</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Étudiant</th>
                            <th>Filière</th>
                            <th>Encadrant Assigné</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projets as $i => $projet)
                            <tr>
                                <td style="color:#94A3B8; font-size:0.8rem;">#{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    @if($projet->etudiant)
                                        <div class="student-name">{{ $projet->etudiant->nom }} {{ $projet->etudiant->prenom }}</div>
                                        <div class="student-sub">{{ $projet->etudiant->cne }}</div>
                                    @endif
                                    @if($projet->etudiant2)
                                        <div style="border-top: 1px dashed #E2E8F0; margin: 6px 0; padding-top: 6px;"></div>
                                        <div class="student-name">{{ $projet->etudiant2->nom }} {{ $projet->etudiant2->prenom }}</div>
                                        <div class="student-sub">{{ $projet->etudiant2->cne }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php 
                                        $f = mb_strtoupper($projet->etudiant->filiere ?? '', 'UTF-8');
                                        $filiereCode = 'other';
                                        if (str_contains($f, 'TDIA') || str_contains($f, 'TRANSFORM') || str_contains($f, 'ARTIFIC')) {
                                            $filiereCode = 'tdia';
                                        } elseif (str_contains($f, 'INGÉNIERIE') || str_contains($f, 'INGENIERIE') || str_contains($f, 'DONNÉES') || str_contains($f, 'DONNEES') || str_contains($f, 'ID')) {
                                            $filiereCode = 'id';
                                        } elseif (str_contains($f, 'GÉNIE') || str_contains($f, 'GENIE') || str_contains($f, 'GI')) {
                                            $filiereCode = 'gi';
                                        }
                                    @endphp
                                    @if($filiereCode === 'tdia')
                                        <span class="badge badge-tdia">TDIA</span>
                                    @elseif($filiereCode === 'gi')
                                        <span class="badge badge-gi">GI</span>
                                    @elseif($filiereCode === 'id')
                                        <span class="badge badge-id">ID</span>
                                    @else
                                        <span class="badge badge-other">{{ $projet->etudiant->filiere ?? '—' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($projet->encadrant)
                                        Dr. {{ $projet->encadrant->nom }} {{ $projet->encadrant->prenom }}
                                    @else
                                        <span style="color:#94A3B8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($projet->encadrant)
                                        <span class="badge badge-ok">✓ Affecté</span>
                                    @else
                                        <span class="badge badge-none">Non affecté</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center; color:#94A3B8; padding: 40px;">
                                    Aucun projet importé. <a href="{{ route('import.form') }}" style="color:#3B82F6;">Importer un fichier Excel</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    #planningModal {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45);
        z-index:9999; align-items:center; justify-content:center;
    }
    .modal-box {
        background:#fff; border-radius:16px; padding:28px 32px; width:460px;
        max-width:95vw; box-shadow:0 20px 60px rgba(0,0,0,0.2);
    }
    .modal-box h3 { font-size:1.05rem; font-weight:700; color:#0F172A; margin:0 0 6px; }
    .modal-box p { font-size:0.82rem; color:#64748B; margin:0 0 18px; }
    .date-list { display:flex; flex-direction:column; gap:10px; margin-bottom:20px; }
    .date-row { display:flex; align-items:center; gap:10px; }
    .date-row input[type=date] {
        flex:1; padding:8px 12px; border:1.5px solid #E2E8F0; border-radius:8px;
        font-size:0.875rem; color:#0F172A;
    }
    .btn-remove { background:none; border:none; color:#EF4444; cursor:pointer; font-size:1.1rem; padding:4px 6px; }
    .btn-add-date { background:#F8FAFC; border:1.5px dashed #CBD5E1; border-radius:8px;
        padding:8px 14px; font-size:0.83rem; color:#475569; cursor:pointer; width:100%; }
    .btn-add-date:hover { background:#F1F5F9; }
    .modal-footer { display:flex; gap:10px; justify-content:flex-end; }
</style>

<div id="planningModal">
    <div class="modal-box">
        <h3>📅 Choisir les jours de soutenance</h3>
        <p>Sélectionnez une ou plusieurs dates pour planifier les soutenances.</p>
        <form action="{{ route('planning.run') }}" method="POST" id="planningForm">
            @csrf
            <div class="date-list" id="dateList">
                <div class="date-row">
                    <input type="date" name="dates[]" required>
                    <button type="button" class="btn-remove" onclick="removeDate(this)">✕</button>
                </div>
            </div>
            <button type="button" class="btn-add-date" onclick="addDate()">+ Ajouter une date</button>
            <div class="modal-footer" style="margin-top:20px;">
                <button type="button" class="btn btn-outline btn-sm"
                    onclick="document.getElementById('planningModal').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-success">▶ Générer</button>
            </div>
        </form>
    </div>
</div>

<script>
function addDate() {
    const list = document.getElementById('dateList');
    const row = document.createElement('div');
    row.className = 'date-row';
    row.innerHTML = '<input type="date" name="dates[]" required><button type="button" class="btn-remove" onclick="removeDate(this)">✕</button>';
    list.appendChild(row);
}
function removeDate(btn) {
    const list = document.getElementById('dateList');
    if (list.children.length > 1) btn.closest('.date-row').remove();
}
</script>
@endpush
