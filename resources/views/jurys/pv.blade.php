@extends('layouts.app')

@section('title', 'Aperçu PV — Jury #' . $jury->id)

@section('content')

<style>
    .pv-container {
        max-width: 700px;
        margin: 0 auto;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 50px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .pv-title {
        text-align: center;
        font-family: 'Outfit', sans-serif;
        font-size: 1.6rem;
        color: #0F172A;
        margin-bottom: 8px;
    }
    .pv-subtitle {
        text-align: center;
        color: #64748B;
        margin-bottom: 40px;
        font-size: 0.95rem;
    }
    .pv-section {
        margin-bottom: 25px;
        padding-bottom: 25px;
        border-bottom: 1px solid #F1F5F9;
    }
    .pv-section:last-child { border-bottom: none; }
    .pv-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #94A3B8;
        margin-bottom: 8px;
    }
    .pv-value {
        font-size: 1rem;
        color: #0F172A;
        font-weight: 500;
    }
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

<div class="pv-container">
    <div class="pv-title">PROCÈS-VERBAL DE SOUTENANCE</div>
    <div class="pv-subtitle">Jury #{{ $jury->id }}</div>

    {{-- JURY MEMBERS --}}
    <div class="pv-section">
        <div class="pv-label">Composition du Jury</div>
        @foreach($jury->enseignants as $enseignant)
            <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #F8FAFC;">
                <div class="pv-value">
                    {{ $enseignant->nom }} {{ $enseignant->prenom }}
                </div>
                <span class="role-badge role-{{ $enseignant->pivot->role }}">
                    {{ ucfirst($enseignant->pivot->role) }}
                </span>
            </div>
        @endforeach
    </div>

    {{-- BACK BUTTON --}}
    <div style="text-align:center; margin-top:30px;">
        <a href="{{ route('jurys.index') }}" class="btn btn-outline-red">
            ← Retour aux Jurys
        </a>
    </div>
</div>

@endsection