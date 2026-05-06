@extends('layouts.app')

@section('content')

<style>
    .import-wrapper {
        max-width: 1060px;
        margin: 40px auto;
        font-family: 'Inter', 'Outfit', sans-serif;
    }
    .import-page-title { font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0 0 4px; }
    .import-page-sub   { font-size: 0.93rem; color: #64748b; margin: 0 0 32px; }

    .import-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        align-items: start;
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

    /* Hint */
    .hint-tag {
        display: flex; align-items: center; gap: 6px;
        background: #ede9fe; color: #6d28d9;
        border-radius: 8px; padding: 8px 14px;
        font-size: 0.78rem; font-weight: 600; margin-bottom: 20px;
    }
    .hint-tag.blue { background: #dbeafe; color: #1d4ed8; }

    /* ── Mini file slots (students) ── */
    .file-slots { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }

    .file-slot {
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
        background: #f9fafb;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .file-slot:hover, .file-slot.has-file { border-color: #7c3aed; background: #f5f3ff; }
    .file-slot.has-file { background: #f0fdf4; border-color: #059669; }

    .slot-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: 1.25rem;
    }
    .slot-icon.purple { background: #ede9fe; }
    .slot-icon.green  { background: #d1fae5; }
    .slot-icon.blue   { background: #dbeafe; }

    .slot-text { flex: 1; }
    .slot-label { font-size: 0.88rem; font-weight: 700; color: #1e293b; margin: 0; }
    .slot-sub   { font-size: 0.75rem; color: #94a3b8; margin: 0; }
    .slot-chosen { font-size: 0.75rem; color: #059669; font-weight: 600; margin: 2px 0 0; display: none; }

    .btn-browse-mini {
        background: #7c3aed; color: white;
        border: none; border-radius: 8px; padding: 7px 14px;
        font-size: 0.78rem; font-weight: 600; cursor: pointer;
        transition: all 0.2s; white-space: nowrap; flex-shrink: 0;
    }
    .btn-browse-mini:hover { background: #6d28d9; }

    /* Big drop zone (profs) */
    .drop-zone {
        border: 2px dashed #cbd5e1; border-radius: 14px;
        background: #f8faff; padding: 36px 20px; text-align: center;
        cursor: pointer; transition: all 0.25s; margin-bottom: 18px;
    }
    .drop-zone:hover, .drop-zone.dragover { border-color: #2563eb; background: #eff6ff; }
    .drop-icon { margin: 0 auto 12px; }
    .drop-title { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
    .drop-sub   { font-size: 0.78rem; color: #94a3b8; margin: 0 0 16px; }

    .btn-browse {
        display: inline-block; background: #2563eb; color: white;
        border: none; border-radius: 10px; padding: 10px 28px;
        font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
    }
    .btn-browse:hover { background: #1d4ed8; transform: translateY(-2px); }
    .file-chosen { margin-top: 8px; font-size: 0.82rem; color: #059669; font-weight: 600; display: none; }

    /* Launch button */
    .btn-launch {
        width: 100%; border: none; border-radius: 12px;
        padding: 13px; font-size: 0.95rem; font-weight: 700;
        cursor: pointer; display: flex; align-items: center;
        justify-content: center; gap: 10px; transition: all 0.25s; color: white;
    }
    .btn-launch.purple {
        background: linear-gradient(135deg, #a78bfa 0%, #7c3aed 100%);
        box-shadow: 0 4px 14px rgba(124,58,237,0.2);
    }
    .btn-launch.purple:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124,58,237,0.3); }
    .btn-launch.blue {
        background: linear-gradient(135deg, #60a5fa 0%, #2563eb 100%);
        box-shadow: 0 4px 14px rgba(37,99,235,0.2);
    }
    .btn-launch.blue:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37,99,235,0.3); }

    /* Alerts */
    .alert-success-custom {
        background: #d1fae5; color: #065f46; border: none;
        border-radius: 12px; padding: 14px 20px; margin-bottom: 24px;
        font-weight: 600; font-size: 0.92rem; display: flex; align-items: center; gap: 10px;
    }
    .alert-danger-custom {
        background: #fee2e2; color: #991b1b; border: none;
        border-radius: 12px; padding: 14px 20px; margin-bottom: 24px; font-size: 0.9rem;
    }

    @media (max-width: 720px) { .import-grid { grid-template-columns: 1fr; } }
</style>

<div class="import-wrapper">



    @if($errors->any())
        <div class="alert-danger-custom">
            <strong>Erreur :</strong>
            <ul class="mb-0 mt-2 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <h1 class="import-page-title">📦 Importation des Données</h1>
    <p class="import-page-sub">Chargez vos fichiers Excel pour démarrer le processus de planification.</p>

    <div class="import-grid">

        {{-- CARD 1: ÉTUDIANTS --}}
        <div class="import-card">
            <div class="card-header-custom">
                <div>
                    <h2 class="card-title-custom">📋 Liste des Étudiants</h2>
                    <p class="card-sub-custom">Sélectionnez jusqu'à 3 fichiers simultanément (un par filière)</p>
                </div>
            </div>

            <div class="hint-tag">
                💡 La filière est détectée automatiquement depuis le nom du fichier
            </div>

            <form action="{{ route('import.etudiants') }}" method="POST" enctype="multipart/form-data" id="formEtudiants">
                @csrf
                <input type="file" name="file_etudiants[]" id="fileEtu" style="display:none;"
                       accept=".xlsx,.xls" multiple>

                <div class="drop-zone" id="dropEtu" onclick="document.getElementById('fileEtu').click()">
                    <div class="drop-icon">
                        <svg width="52" height="52" viewBox="0 0 64 64" fill="none">
                            <rect x="8" y="14" width="40" height="36" rx="5" fill="#ede9fe" stroke="#7c3aed" stroke-width="2"/>
                            <rect x="14" y="20" width="28" height="4" rx="2" fill="#a78bfa" opacity="0.5"/>
                            <rect x="14" y="28" width="20" height="3" rx="1.5" fill="#a78bfa" opacity="0.4"/>
                            <line x1="32" y1="42" x2="32" y2="54" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round"/>
                            <polyline points="27,49 32,54 37,49" fill="none" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <p class="drop-title">Glissez vos fichiers ici</p>
                    <p class="drop-sub">Vous pouvez sélectionner plusieurs fichiers (.xls, .xlsx)</p>
                    <button type="button" class="btn-browse"
                            onclick="event.stopPropagation(); document.getElementById('fileEtu').click()">
                        Parcourir sur le PC
                    </button>
                    <div id="chosenEtu" style="margin-top:12px; display:none; text-align:left; background:#f5f3ff; border-radius:8px; padding:10px 14px;"></div>
                </div>

                <button type="submit" class="btn-launch purple">
                    Importer tous les fichiers
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </form>
        </div>


        {{-- ═══════════════════════════════════════
             CARD 2: PROFESSEURS — single file
        ════════════════════════════════════════ --}}
        <div class="import-card">
            <div class="card-header-custom">
                <div>
                    <h2 class="card-title-custom">👨‍🏫 Liste des Professeurs</h2>
                    <p class="card-sub-custom">Importer le fichier Excel des enseignants</p>
                </div>
            </div>

            <div class="hint-tag blue">
                📋 Format requis : <em style="margin-left:4px;">Nom, Prénom, Spécialité</em> &nbsp;(à partir de la ligne 3)
            </div>

            <form action="{{ route('import.profs') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="drop-zone" id="dropProf" onclick="document.getElementById('fileProf').click()">
                    <input type="file" name="file_profs" id="fileProf" style="display:none;" accept=".xlsx,.xls">

                    <div class="drop-icon">
                        <svg width="52" height="52" viewBox="0 0 64 64" fill="none">
                            <circle cx="32" cy="20" r="11" fill="#dbeafe" stroke="#2563eb" stroke-width="2"/>
                            <path d="M10 54c0-12.15 9.85-22 22-22s22 9.85 22 22" fill="#dbeafe" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="32" cy="20" r="5" fill="#93c5fd"/>
                        </svg>
                    </div>
                    <p class="drop-title">Glissez le fichier ici</p>
                    <p class="drop-sub">Supporte uniquement les fichiers Excel (.xls, .xlsx)</p>
                    <button type="button" class="btn-browse"
                            onclick="event.stopPropagation(); document.getElementById('fileProf').click()">
                        Parcourir sur le PC
                    </button>
                    <div class="file-chosen" id="chosenProf"></div>
                </div>

                <button type="submit" class="btn-launch blue">
                    Importer Professeurs
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </form>
        </div>

    </div>
</div>

<script>
    // When a file is chosen, show name and reveal the submit button
    // Students multi-file input
    const fileEtu   = document.getElementById('fileEtu');
    const dropEtu   = document.getElementById('dropEtu');
    const chosenEtu = document.getElementById('chosenEtu');

    function showSelectedFiles(files) {
        if (!files.length) return;
        chosenEtu.style.display = 'block';
        chosenEtu.innerHTML = Array.from(files)
            .map(f => `<div style="font-size:0.82rem; color:#7c3aed; font-weight:600; margin-bottom:3px;">📄 ${f.name}</div>`)
            .join('');
        dropEtu.classList.add('dragover');
    }

    fileEtu.addEventListener('change', () => showSelectedFiles(fileEtu.files));

    dropEtu.addEventListener('dragover',  (e) => { e.preventDefault(); dropEtu.classList.add('dragover'); });
    ['dragleave', 'drop'].forEach(ev => dropEtu.addEventListener(ev, () => dropEtu.classList.remove('dragover')));
    dropEtu.addEventListener('drop', (e) => {
        e.preventDefault();
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        fileEtu.files = dt.files;
        showSelectedFiles(dt.files);
    });


    // Profs drop zone
    const dropProf  = document.getElementById('dropProf');
    const fileProf  = document.getElementById('fileProf');
    const chosenPrf = document.getElementById('chosenProf');

    fileProf.addEventListener('change', () => {
        if (fileProf.files.length) {
            chosenPrf.textContent = '📄 ' + fileProf.files[0].name;
            chosenPrf.style.display = 'block';
        }
    });

    dropProf.addEventListener('dragover', (e) => { e.preventDefault(); dropProf.classList.add('dragover'); });
    ['dragleave', 'drop'].forEach(ev => dropProf.addEventListener(ev, () => dropProf.classList.remove('dragover')));
    dropProf.addEventListener('drop', (e) => {
        e.preventDefault();
        if (e.dataTransfer.files.length) {
            const dt = new DataTransfer();
            dt.items.add(e.dataTransfer.files[0]);
            fileProf.files = dt.files;
            chosenPrf.textContent = '📄 ' + e.dataTransfer.files[0].name;
            chosenPrf.style.display = 'block';
        }
    });
</script>
@endsection