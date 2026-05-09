@extends('layouts.app')

@section('content')

<style>
    .import-wrapper {
        max-width: 1100px;
        margin: 40px auto;
        font-family: 'Inter', 'Outfit', sans-serif;
    }
    .import-page-title { font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0 0 4px; }
    .import-page-sub   { font-size: 0.93rem; color: #64748b; margin: 0 0 32px; }

    .import-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 24px;
        align-items: start;
        margin-bottom: 32px;
    }

    .import-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.07);
        padding: 28px 28px 24px;
    }

    .card-header-custom {
        display: flex; justify-content: space-between; align-items: flex-start;
        margin-bottom: 20px;
    }
    .card-title-custom { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0 0 4px; }
    .card-sub-custom   { font-size: 0.82rem; color: #64748b; margin: 0; }

    .hint-tag {
        display: flex; align-items: center; gap: 6px;
        background: #ede9fe; color: #6d28d9;
        border-radius: 8px; padding: 8px 14px;
        font-size: 0.78rem; font-weight: 600; margin-bottom: 14px;
    }

    .drop-zone {
        border: 2px dashed #cbd5e1; border-radius: 14px;
        background: #f8faff; padding: 24px 16px; text-align: center;
        cursor: pointer; transition: all 0.25s; margin-bottom: 12px;
    }
    .drop-zone:hover { border-color: #2563eb; background: #eff6ff; }
    .drop-title { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0 0 10px; }
    .drop-label {
        font-size: 0.78rem; font-weight: 700; color: #475569;
        margin: 14px 0 6px; text-transform: uppercase; letter-spacing: .04em;
        display: block;
    }

    .btn-browse {
        display: inline-block; background: #2563eb; color: white;
        border: none; border-radius: 10px; padding: 8px 22px;
        font-size: 0.85rem; font-weight: 600; cursor: pointer;
    }
    .btn-browse:hover { background: #1d4ed8; }
    .file-chosen { margin-top: 8px; font-size: 0.82rem; color: #059669; font-weight: 600; display: none; }

    .btn-launch {
        width: 100%; border: none; border-radius: 12px;
        padding: 13px; font-size: 0.95rem; font-weight: 700;
        cursor: pointer; display: flex; align-items: center;
        justify-content: center; gap: 10px; color: white;
        background: linear-gradient(135deg, #60a5fa 0%, #2563eb 100%);
        box-shadow: 0 4px 14px rgba(37,99,235,0.2);
        transition: all 0.25s;
    }
    .btn-launch:hover { transform: translateY(-2px); }
    .btn-launch.green {
        background: linear-gradient(135deg, #34d399 0%, #059669 100%);
        box-shadow: 0 4px 14px rgba(5,150,105,0.2);
    }
    .btn-launch-main {
        width: 100%; border: none; border-radius: 14px;
        padding: 16px; font-size: 1.05rem; font-weight: 800;
        cursor: pointer; color: white; margin-top: 8px;
        background: linear-gradient(135deg, #6366f1 0%, #2563eb 100%);
        box-shadow: 0 6px 20px rgba(99,102,241,0.3);
        display: flex; align-items: center; justify-content: center; gap: 10px;
        transition: all 0.25s;
    }
    .btn-launch-main:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(99,102,241,0.4); }

    .alert-danger-custom {
        background: #fee2e2; color: #991b1b; border-radius: 12px;
        padding: 14px 20px; margin-bottom: 24px; font-size: 0.9rem;
    }
    .alert-success-custom {
        background: #d1fae5; color: #065f46; border-radius: 12px;
        padding: 14px 20px; margin-bottom: 24px;
        font-size: 0.9rem; font-weight: 600;
    }

    @media (max-width: 820px) { .import-grid { grid-template-columns: 1fr; } }
</style>

<div class="import-wrapper">

    <h1 class="import-page-title">📦 Importation des Données</h1>
    <p class="import-page-sub">Chargez vos fichiers Excel puis cliquez sur le bouton unique pour tout importer.</p>

    @if(session('success'))
        <div class="alert-success-custom">✅ {{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert-danger-custom">
            <strong>Erreur :</strong>
            <ul class="mb-0 mt-2 ps-3">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ONE FORM wrapping everything --}}
    <form action="{{ route('import.all') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="import-grid">

            {{-- CARD 1: ÉTUDIANTS & PROJETS --}}
            <div class="import-card">
                <div class="card-header-custom">
                    <div>
                        <h2 class="card-title-custom">📋 Étudiants & Projets</h2>
                        <p class="card-sub-custom">Fichiers projets + emails par filière</p>
                    </div>
                </div>

                <div class="hint-tag">
                    💡 Filière détectée automatiquement depuis le nom du fichier
                </div>

                <span class="drop-label">📋 Fichiers Projets</span>
                <div class="drop-zone" onclick="document.getElementById('fileProjets').click()">
                    <input type="file" name="file_projets[]" id="fileProjets"
                           style="display:none;" accept=".xlsx,.xls" multiple>
                    <p class="drop-title">GI.xlsx, TDIA.xlsx, ID.xlsx</p>
                    <button type="button" class="btn-browse">Parcourir</button>
                    <div id="chosenProjets" style="margin-top:8px;display:none;text-align:left;"></div>
                </div>

                <span class="drop-label">📧 Fichiers Emails</span>
                <div class="drop-zone" onclick="document.getElementById('fileEmails').click()">
                    <input type="file" name="file_emails[]" id="fileEmails"
                           style="display:none;" accept=".xlsx,.xls" multiple>
                    <p class="drop-title">GI_Email.xlsx, TDIA_Email.xlsx, ID_Email.xlsx</p>
                    <button type="button" class="btn-browse">Parcourir</button>
                    <div id="chosenEmails" style="margin-top:8px;display:none;text-align:left;"></div>
                </div>
            </div>

            {{-- CARD 2: ENSEIGNANTS --}}
            <div class="import-card">
                <div class="card-header-custom">
                    <div>
                        <h2 class="card-title-custom">👨‍🏫 Enseignants</h2>
                        <p class="card-sub-custom">Fichier unique des encadrants</p>
                    </div>
                </div>
                <div class="drop-zone" onclick="document.getElementById('fileEns').click()">
                    <input type="file" name="file_enseignants" id="fileEns"
                           style="display:none;" accept=".xlsx,.xls">
                    <div class="drop-icon">
                        <svg width="48" height="48" viewBox="0 0 64 64" fill="none">
                            <circle cx="32" cy="20" r="11" fill="#dbeafe" stroke="#2563eb" stroke-width="2"/>
                            <path d="M10 54c0-12.15 9.85-22 22-22s22 9.85 22 22" fill="#dbeafe" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <p class="drop-title">Liste_des_Profs.xlsx</p>
                    <button type="button" class="btn-browse">Parcourir</button>
                    <div class="file-chosen" id="chosenEns"></div>
                </div>
            </div>

            {{-- CARD 3: SALLES --}}
            <div class="import-card">
                <div class="card-header-custom">
                    <div>
                        <h2 class="card-title-custom">🏫 Salles</h2>
                        <p class="card-sub-custom">Fichier des salles disponibles</p>
                    </div>
                </div>
                <div class="drop-zone" onclick="document.getElementById('fileSalle').click()">
                    <input type="file" name="file_salles" id="fileSalle"
                           style="display:none;" accept=".xlsx,.xls">
                    <div class="drop-icon">
                        <svg width="48" height="48" viewBox="0 0 64 64" fill="none">
                            <rect x="8" y="20" width="48" height="34" rx="4" fill="#d1fae5" stroke="#059669" stroke-width="2"/>
                            <path d="M24 54V38h16v16" stroke="#059669" stroke-width="2" stroke-linecap="round"/>
                            <path d="M8 28l24-16 24 16" fill="#d1fae5" stroke="#059669" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <p class="drop-title">Salles.xlsx</p>
                    <button type="button" class="btn-browse">Parcourir</button>
                    <div class="file-chosen" id="chosenSalle"></div>
                </div>
            </div>

        </div>

        {{-- ONE SINGLE IMPORT BUTTON --}}
        <button type="submit" class="btn-launch-main">
            🚀 Lancer l'importation complète
        </button>

    </form>

    {{-- DANGER ZONE --}}
    <div class="danger-zone">
        <div class="danger-header">
            <h3 class="danger-title">⚠️ Zone de Danger</h3>
            <p class="danger-sub">Supprimer toutes les données existantes (Étudiants, Projets, Profs, Salles, etc.)</p>
        </div>
        <form action="{{ route('import.clear') }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer TOUTES les données ? Cette action est irréversible.')">
            @csrf
            <button type="submit" class="btn-delete">🗑️ Tout Supprimer</button>
        </form>
    </div>

</div>

<style>
    .danger-zone {
        margin-top: 60px;
        padding: 24px;
        background: #fff5f5;
        border: 1px solid #feb2b2;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .danger-header { flex: 1; }
    .danger-title { color: #c53030; font-size: 1.05rem; font-weight: 800; margin: 0 0 4px; }
    .danger-sub { color: #742a2a; font-size: 0.82rem; margin: 0; }
    .btn-delete {
        background: #c53030; color: white; border: none; border-radius: 10px;
        padding: 10px 24px; font-weight: 700; cursor: pointer; transition: all 0.2s;
    }
    .btn-delete:hover { background: #9b2c2c; transform: scale(1.02); }

    @media (max-width: 600px) {
        .danger-zone { flex-direction: column; text-align: center; gap: 16px; }
    }
</style>

<script>
    const fileProjets = document.getElementById('fileProjets');
    fileProjets.addEventListener('change', () => {
        const el = document.getElementById('chosenProjets');
        el.style.display = 'block';
        el.innerHTML = Array.from(fileProjets.files)
            .map(f => `<div style="font-size:0.82rem;color:#7c3aed;font-weight:600;margin-bottom:3px;">📄 ${f.name}</div>`)
            .join('');
    });

    const fileEmails = document.getElementById('fileEmails');
    fileEmails.addEventListener('change', () => {
        const el = document.getElementById('chosenEmails');
        el.style.display = 'block';
        el.innerHTML = Array.from(fileEmails.files)
            .map(f => `<div style="font-size:0.82rem;color:#2563eb;font-weight:600;margin-bottom:3px;">📄 ${f.name}</div>`)
            .join('');
    });

    const fileEns = document.getElementById('fileEns');
    fileEns.addEventListener('change', () => {
        const el = document.getElementById('chosenEns');
        el.textContent = '📄 ' + fileEns.files[0].name;
        el.style.display = 'block';
    });

    const fileSalle = document.getElementById('fileSalle');
    fileSalle.addEventListener('change', () => {
        const el = document.getElementById('chosenSalle');
        el.textContent = '📄 ' + fileSalle.files[0].name;
        el.style.display = 'block';
    });
</script>

@endsection