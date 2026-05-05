@extends('layouts.app')

@section('title', 'Gestion des Salles')

@section('content')

<style>
    .action-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    .salle-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 40px;
    }
    .salle-card {
        background: white;
        border: 1px solid #E2E8F0;
        padding: 18px 20px;
        border-radius: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .salle-card:hover {
        border-color: #EF4444;
        box-shadow: 0 4px 12px rgba(239,68,68,0.1);
    }
    .salle-name { font-weight: 600; color: #0F172A; }
    .salle-capacity {
        background: #F1F5F9;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
    }
    .btn-delete {
        background: none;
        border: none;
        color: #CBD5E1;
        cursor: pointer;
        font-size: 1rem;
        transition: color 0.2s;
        padding: 5px;
    }
    .btn-delete:hover { color: #EF4444; }
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 100;
        justify-content: center;
        align-items: center;
    }
    .modal-overlay.active { display: flex; }
    .modal {
        background: white;
        padding: 35px;
        border-radius: 16px;
        width: 420px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.15);
    }
    .modal h3 { margin-bottom: 20px; color: #0F172A; }
    .form-group { margin-bottom: 18px; }
    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
    }
    .form-group input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        font-size: 0.95rem;
        font-family: 'Inter', sans-serif;
        outline: none;
        transition: border-color 0.2s;
    }
    .form-group input:focus { border-color: #EF4444; }
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 25px;
    }
    .btn-cancel {
        padding: 10px 20px;
        border-radius: 8px;
        border: 1px solid #E2E8F0;
        background: white;
        color: #64748B;
        font-weight: 600;
        cursor: pointer;
    }
    .error-message {
        color: #EF4444;
        font-size: 0.8rem;
        margin-top: 4px;
    }
</style>

{{-- PAGE HEADER --}}
<div class="action-header">
    <div>
        <h2 style="font-family:'Outfit',sans-serif; color:#0F172A;">Salles Disponibles</h2>
        <p style="color:#64748B; font-size:0.9rem; margin-top:4px;">
            {{ $salles->count() }} salle(s) enregistrée(s)
        </p>
    </div>
    <button class="btn btn-red" onclick="openModal()">
        + Ajouter une Salle
    </button>
</div>

{{-- SUCCESS MESSAGE --}}
@if(session('success'))
    <div style="background:#D1FAE5; color:#065F46; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:500;">
        ✅ {{ session('success') }}
    </div>
@endif

{{-- SALLES GRID --}}
<div class="salle-grid">
    @forelse($salles as $salle)
        <div class="salle-card">
            <div>
                <div class="salle-name">🏛️ {{ $salle->nom }}</div>
                <div style="margin-top:6px;">
                    <span class="salle-capacity">👥 {{ $salle->capacite }} personnes</span>
                </div>
            </div>
            <form action="{{ route('salles.destroy', $salle->id) }}" method="POST"
                  onsubmit="return confirm('Supprimer {{ $salle->nom }} ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-delete" title="Supprimer">🗑️</button>
            </form>
        </div>
    @empty
        <div style="grid-column:span 3; text-align:center; padding:40px; color:#94A3B8;">
            Aucune salle enregistrée pour le moment.
        </div>
    @endforelse
</div>

{{-- ADD SALLE MODAL --}}
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <h3>➕ Ajouter une Salle</h3>
        <form action="{{ route('salles.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Nom de la salle</label>
                <input type="text" name="nom" placeholder="ex: Amphi A, Salle 201"
                       value="{{ old('nom') }}">
                @error('nom')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label>Capacité (nombre de personnes)</label>
                <input type="number" name="capacite" placeholder="ex: 30"
                       value="{{ old('capacite') }}" min="1">
                @error('capacite')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn btn-red">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modalOverlay').classList.add('active');
    }
    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('active');
    }
    // Close modal if clicking outside
    document.getElementById('modalOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Reopen modal if there are validation errors
    @if($errors->any())
        openModal();
    @endif
</script>

@endsection