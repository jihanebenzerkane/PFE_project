@extends('layouts.app')

@section('title', 'Résultats du Planning Généré')

@push('styles')
    <style>
        .planning-header {
            margin-bottom: 30px;
        }

        .top-buttons .btn {
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-pdf-supervision {
            color: #ef4444;
            background: white;
            border: 1px solid #ef4444;
        }

        .btn-pdf-supervision:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        .btn-pdf-planning {
            color: white;
            background: #ef4444;
            border: 1px solid #ef4444;
        }

        .btn-pdf-planning:hover {
            background: #dc2626;
            color: white;
        }

        .btn-relancer {
            color: white;
            background: #10b981;
            border: 1px solid #10b981;
        }

        .btn-relancer:hover {
            background: #059669;
            color: white;
        }

        /* Side Cards */
        .side-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 16px;
            height: 100%;
        }

        .side-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .prof-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 250px;
            overflow-y: auto;
        }

        .prof-item {
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.85rem;
            color: #475569;
        }

        .salle-mini-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Main Table Styles */
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .planning-table {
            width: 100%;
            border-collapse: collapse;
        }

        .planning-table thead th {
            text-align: left;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            padding: 16px 20px;
            background: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
        }

        .planning-table tbody td {
            padding: 16px 20px;
            font-size: 0.9rem;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
            color: #334155;
        }

        .planning-table tbody tr:hover {
            background: #F8FAFC;
        }

        .text-gray {
            color: #64748b;
            font-size: 0.8rem;
        }

        .fw-600 {
            font-weight: 600;
        }

        .text-dark {
            color: #0f172a;
        }

        /* Badges */
        .filiere-badge {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-block;
        }

        .f-tdia {
            background: #dcfce7;
            color: #166534;
        }

        .f-gi {
            background: #dbeafe;
            color: #1e40af;
        }

        .f-id {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .f-other {
            background: #f1f5f9;
            color: #64748b;
        }

        /* Incomplete jury row highlight */
        .planning-table tbody tr[style*="background:#fff5f5"] td {
            border-left: 3px solid #ef4444;
        }
        .planning-table tbody tr[style*="background:#fff5f5"] td:first-child {
            border-left: 3px solid #ef4444;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 12px;
            width: 400px;
        }
    </style>
@endpush

@section('content')

    @php
        // Read persistent diagnostic to decide whether to show the warning banner
        $persistedDiag = null;
        if (\Illuminate\Support\Facades\Storage::exists('conformite_diagnostic.json')) {
            $persistedDiag = json_decode(\Illuminate\Support\Facades\Storage::get('conformite_diagnostic.json'), true);
        }
    @endphp

    {{-- PERSISTENT WARNING: stays visible until all students are scheduled --}}
    @if ($persistedDiag && ($persistedDiag['non_affectes'] ?? 0) > 0)
        <div class="alert alert-warning border-0 shadow-sm mb-4" style="border-radius:10px;">
            ⚠️ Seulement <strong>{{ $persistedDiag['pct'] }}%</strong> des étudiants ont pu être planifiés
            (<strong>{{ $persistedDiag['affectes'] }}/{{ $persistedDiag['total'] }}</strong>).
            Consultez le <a href="{{ route('conformite.index') }}" class="alert-link fw-bold">Contrôle de Conformité</a> pour
            plus de détails.
        </div>
    @elseif(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius:10px;">
            {!! session('success') !!}
        </div>
    @endif


    {{-- TOP HEADER --}}
    <div class="planning-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="fw-bold mb-2 text-dark">Résultats du Planning Généré</h3>
        </div>

        <div class="top-buttons d-flex flex-wrap gap-2">
            <a href="{{ route('export.affectation') }}" class="btn btn-pdf-supervision shadow-sm">📄 PDF Supervision</a>
            <a href="{{ route('export.planning') }}" class="btn btn-pdf-planning shadow-sm">📄 PDF Planning Général</a>
            <a href="{{ route('export.planning.word') }}" class="btn btn-outline-secondary shadow-sm"
                style="font-weight:600; border-radius:8px; padding:10px 20px;">📄 Word</a>
            <a href="{{ route('conformite.index') }}" class="btn shadow-sm"
                style="font-weight:600; border-radius:8px; padding:10px 20px; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;">🔍
                Contrôle de Conformité</a>
            <button class="btn btn-relancer shadow-sm"
                onclick="document.getElementById('planningModal').style.display='flex'">
                ⏱ Relancer l'Algorithme
            </button>
        </div>
    </div>

    {{-- SIDEBARS ROW: Professeurs & Salles always side by side --}}
    <div style="display:flex !important; flex-direction:row !important; gap:16px; margin-bottom:24px; align-items:stretch; width:100%;">

        {{-- LEFT: PROFESSORS --}}
        <div style="flex:1; min-width:0;">
            <div class="side-card">
                <div class="side-title">👥 PROFESSEURS</div>
                <ul class="prof-list">
                    @foreach ($enseignants as $prof)
                        <li class="prof-item">{{ $prof->nom }} {{ $prof->prenom }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- RIGHT: SALLES --}}
        <div style="flex:1; min-width:0;">
            <div class="side-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div class="side-title" style="margin-bottom:0;">🏛️ SALLES</div>
                    <button type="button" onclick="openModal()"
                        style="background:#f1f5f9; border:none; color:#475569; font-size:0.75rem; font-weight:700; cursor:pointer; padding:6px 12px; border-radius:6px;">
                        + Ajouter
                    </button>
                </div>
                <div style="max-height:250px; overflow-y:auto;">
                    @foreach ($salles as $salle)
                        <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border-radius:8px; padding:10px 12px; margin-bottom:8px;">
                            <span style="font-weight:600; font-size:0.85rem; color:#1e293b;">{{ $salle->nom }}</span>
                            <form action="{{ route('salles.destroy', $salle->id) }}" method="POST" style="margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:0.9rem; padding:4px;" title="Supprimer">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- FULL WIDTH: PLANNING TABLE --}}
    <div class="table-card w-100 mb-5">
        <div class="table-responsive">
            <table class="planning-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Étudiant(s)</th>
                        <th>Filière</th>
                        <th>Encadrant (Président)</th>
                        <th>Examinateurs (Rapporteurs)</th>
                        <th>Date & Heure</th>
                        <th>Salle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($soutenances as $index => $row)
                        @php
                            $filiere = mb_strtoupper($row['filiere'] ?? '', 'UTF-8');
                            $fShort = '—'; $fClass = 'f-other';
                            if (str_contains($filiere, 'TDIA') || str_contains($filiere, 'TRANSFORM') || str_contains($filiere, 'ARTIF') || str_contains($filiere, 'INTELLIGENCE')) {
                                $fShort = 'TDIA'; $fClass = 'f-tdia';
                            } elseif (str_contains($filiere, 'DONN') || ($filiere === 'ID') || (str_contains($filiere, 'ING') && str_contains($filiere, 'DONN'))) {
                                $fShort = 'ID'; $fClass = 'f-id';
                            } elseif (str_contains($filiere, 'GENIE') || str_contains($filiere, 'GÉNIE') || ($filiere === 'GI') || str_contains($filiere, 'INFORMATIQUE')) {
                                $fShort = 'GI'; $fClass = 'f-gi';
                            }
                            $nbRapporteurs = count($row['examinateurs'] ?? []);
                            $juryOk = $nbRapporteurs >= 2 && !empty($row['president']) && $row['president'] !== 'N/A';
                        @endphp
                        <tr style="{{ !$juryOk ? 'background:#fff5f5;' : '' }}">
                            <td class="fw-600 text-gray">#P{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                            <td>
                                <div class="fw-600 text-dark">{{ $row['etudiant_nom'] ?? '' }}
                                    {{ $row['etudiant_prenom'] ?? '' }}</div>
                                <div class="text-gray mt-1">Sujet: {{ Str::limit($row['titre'] ?? '—', 30) }}</div>
                            </td>
                            <td>
                                @if($fShort !== '—')
                                    <span class="filiere-badge {{ $fClass }}">{{ $fShort }}</span>
                                @else
                                    <span class="filiere-badge" style="background:#f1f5f9;color:#64748b;">{{ $filiere ?: '—' }}</span>
                                @endif
                            </td>
                            <td class="fw-600 text-dark">
                                {{ $row['encadrant'] ?? '—' }}
                                @if(!empty($row['president']) && $row['president'] !== 'N/A' && $row['president'] !== ($row['encadrant'] ?? ''))
                                    <div class="text-gray" style="font-size:0.75rem;font-weight:400;">
                                        Président jury: {{ $row['president'] }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-gray">
                                @if ($nbRapporteurs >= 2)
                                    @foreach ($row['examinateurs'] as $ex)
                                        <div>{{ $ex }}</div>
                                    @endforeach
                                @elseif ($nbRapporteurs === 1)
                                    @foreach ($row['examinateurs'] as $ex)
                                        <div>{{ $ex }}</div>
                                    @endforeach
                                    <span class="badge" style="background:#fef3c7;color:#92400e;font-size:0.7rem;">⚠ 1 rapporteur manquant</span>
                                @else
                                    <span class="badge bg-danger" style="font-size:0.75rem;">⚠ Jury incomplet — 0 rapporteurs</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-600 text-dark">{{ $row['date'] ?? '—' }}</div>
                                <div class="text-gray mt-1">{{ $row['heure_debut'] ?? '' }} -
                                    {{ $row['heure_fin'] ?? '' }}</div>
                            </td>
                            <td class="text-gray">{{ $row['salle'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Aucune donnée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL AJOUTER SALLE --}}
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-content">
            <h5 class="fw-bold mb-4">Ajouter une Salle</h5>
            <form action="{{ route('salles.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Nom de la salle</label>
                    <input type="text" name="nom" class="form-control bg-light border-0" required>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL RELANCER PLANNING --}}
    <div class="modal-overlay" id="planningModal">
        <div class="modal-content" style="width: 480px; border-radius: 16px; padding: 40px;">
            <div class="mb-3">
                <h5 class="fw-bold mb-1" style="font-size:1.1rem;">📅 Choisir les jours de soutenance</h5>
                <p class="text-muted small mb-0">Sélectionnez une ou plusieurs dates pour planifier les soutenances.</p>
            </div>
            <form action="{{ route('planning.run') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <div id="dateList">
                        <div class="date-row d-flex align-items-center gap-3 mb-2">
                            <input type="date" name="dates[]" class="form-control" required>
                            <button type="button" class="btn btn-link text-danger p-0 text-decoration-none fw-bold fs-5"
                                onclick="removeDate(this)">✕</button>
                        </div>
                    </div>
                    <button type="button" onclick="addDate()"
                        style="background:#F8FAFC; border:1.5px dashed #CBD5E1; border-radius:8px;
                        padding:10px 14px; font-size:0.83rem; color:#475569; cursor:pointer; width:100%; margin-top:12px; display:block;">
                        + Ajouter une date
                    </button>
                    <small class="text-danger mt-3 d-block mb-3">⚠ L'ancien planning sera écrasé.</small>
                </div>
                <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                    <button type="button" class="btn btn-light px-4"
                        onclick="document.getElementById('planningModal').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-success px-4">▶ Générer</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function openModal() {
                document.getElementById('modalOverlay').style.display = 'flex';
            }

            function closeModal() {
                document.getElementById('modalOverlay').style.display = 'none';
            }

            function addDate() {
                const list = document.getElementById('dateList');
                const row = document.createElement('div');
                row.className = 'date-row d-flex align-items-center gap-2 mb-2';
                row.innerHTML =
                    '<input type="date" name="dates[]" class="form-control" required><button type="button" class="btn btn-link text-danger p-0 text-decoration-none fw-bold fs-5" onclick="removeDate(this)">✕</button>';
                list.appendChild(row);
            }

            function removeDate(btn) {
                const list = document.getElementById('dateList');
                if (list.children.length > 1) {
                    btn.closest('.date-row').remove();
                }
            }
        </script>
    @endpush
@endsection
