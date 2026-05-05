<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Génération des Dossiers PVs</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; justify-content: center; padding-top: 50px; }
        .intro-card {
            background-color: #fff8d6;
            border: 2px dashed #f5c242;
            border-radius: 15px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            text-align: center;
        }
        .btn-generate {
            background-color: #f39c12;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            padding: 15px 30px;
            border-radius: 8px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .btn-generate:hover {
            background-color: #e67e22;
            color: white;
            transform: translateY(-2px);
        }
        .tree-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: left;
            margin-top: 30px;
            font-family: monospace;
            font-size: 1.1rem;
            color: #555;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .folder { color: #f1c40f; font-weight: bold; }
        .file { color: #95a5a6; }
        .indent-1 { margin-left: 20px; }
        .indent-2 { margin-left: 40px; }
    </style>
</head>
<body>

<div class="intro-card shadow-sm">
    <h2 class="fw-bold mb-3">Génération des Dossiers PVs</h2>
    <p class="text-muted mb-4 fs-5">Construisez localement l'arborescence des dossiers contenant les documents Word (.doc).</p>
    
    <!-- This button redirects directly to the Table Dashboard -->
    <a href="{{ route('pv.index') }}" class="btn btn-generate text-decoration-none">
        Générer les Dossiers PVs (Local)
    </a>

    <div class="tree-card">
        <div><span class="folder">📁 Dossiers_PVs_Soutenances_2026/</span></div>
        
        <div class="indent-1 mt-2"><span class="folder">📁 Pr_Cherradi_Mohamed/</span></div>
        <div class="indent-2"><span class="file">📄 Fiche_Evaluation_PFE_Ahmed_Alami.docx</span></div>
        <div class="indent-2"><span class="file">📄 Fiche_Evaluation_PFE_Sara_Nouri.docx</span></div>

        <div class="indent-1 mt-3"><span class="folder">📁 Pr_Abakouy_Redouan/</span></div>
        <div class="indent-2"><span class="file">📄 Fiche_Evaluation_PFE_Youssef_Tariq.docx</span></div>
        
        <div class="indent-2 fst-italic text-muted">[... Tous les PVs Centralisés]</div>
    </div>
</div>

</body>
</html>
