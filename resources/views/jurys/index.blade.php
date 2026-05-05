@extends('layouts.app')

@section('title', 'Gestion des Jurys')

@section('content')

<style>
    .jury-table {
        background: white;
        border-radius: 12px;
        border: 1px solid #E2E8F0;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .jury-table table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    .jury-table th {
        background: #F1F5F9;
        padding: 14px 18px;
        text-align: left;
        font-weight: 600;
        color: #475569;
        border-bottom: 2px solid #E2E8F0;
    }
    .jury-table td {
        padding: 15px 18px;
        border-bottom: 1px solid #F1F5F9;
        color: #334155;
    }
    .jury-table tr:last-child td { border-bottom: none; }
    .jury-table tr:hover td { background: #F8FAFC; }
    .role-badge {
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .role-president { background: #FEE2E2; color: #991B1B; }
    .role-examinateur { background: #DBEAFE; color: #1E40AF; }
    .role-rapporteur { background: #D1FAE5; color: #065F46; }
</style>

<div class="action-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
    <div>
        <h2 style="font-family:'Outfit',sans-serif; color:#0F172A;">Jurys de Soutenance</h2>
        <p style="color:#64748B; font-size:0.9rem; margin-top:4px;">
            {{ $jurys->count() }} jury(s) enregistré(s)
        </p>
    </div>
</div>

<div class="jury-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Membres du Jury</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($jurys as $jury)
                <tr>
                    <td><strong>#{{ $jury->id }}</strong></td>
                    <td>
                        @foreach($jury->enseignants as $enseignant)
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                                <span>{{ $enseignant->nom }} {{ $enseignant->prenom }}</span>
                                <span class="role-badge role-{{ $enseignant->pivot->role }}">
                                    {{ ucfirst($enseignant->pivot->role) }}
                                </span>
                            </div>
                        @endforeach
                    </td>
                    <td>
                        <a href="{{ route('jurys.pv', $jury->id) }}" class="btn btn-red" style="font-size:0.85rem; padding:8px 14px;">
                            👁️ Voir PV
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center; padding:40px; color:#94A3B8;">
                        Aucun jury enregistré pour le moment.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection