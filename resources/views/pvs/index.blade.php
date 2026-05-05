<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Génération des PVs - Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table thead th { background-color: #f1f3f5; border-bottom: none; }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-earmark-word text-primary"></i> Génération des PVs</h2>
        
        <!-- The Download All Archive Button -->
        <a href="{{ route('pv.downloadAll') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-file-zip"></i> Télécharger Tous (Archive ZIP)
        </a>
    </div>

    <div class="card p-4">
        <div class="table-responsive">
            <table id="pvTable" class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Étudiant</th>
                        <th>Filière</th>
                        <th>Président (Encadrant)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($soutenances as $soutenance)
                    <tr>
                        <td>#{{ $soutenance->id }}</td>
                        <!-- Check if relationships exist to avoid null errors during development -->
                        <td>
                            @if($soutenance->projet && $soutenance->projet->etudiant)
                                {{ $soutenance->projet->etudiant->nom }} {{ $soutenance->projet->etudiant->prenom }}
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($soutenance->projet && $soutenance->projet->etudiant)
                                <span class="badge bg-secondary">{{ $soutenance->projet->etudiant->filiere }}</span>
                            @endif
                        </td>
                        <td>
                            @if($soutenance->projet && $soutenance->projet->encadrant)
                                Pr. {{ $soutenance->projet->encadrant->nom }}
                            @else
                                <span class="text-danger">Non assigné</span>
                            @endif
                        </td>
                        <td>
                            <!-- The Download Single PV Button -->
                            <a href="{{ route('pv.download', $soutenance->id) }}" class="btn btn-sm btn-success">
                                <i class="bi bi-download"></i> Télécharger PV
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- jQuery (Required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#pvTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json' // Translates DataTables to French!
            }
        });
    });
</script>
</body>
</html>
