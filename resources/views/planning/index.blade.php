<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planification des Soutenances</title>
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
        <h2><i class="bi bi-calendar-check text-primary"></i> Planification des Soutenances</h2>
        
        <div class="d-flex gap-2">
            <!-- Form to run the assignment algorithm -->
            <form action="{{ route('algorithm.run') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning shadow-sm">
                    <i class="bi bi-magic"></i> Exécuter l'Algorithme
                </button>
            </form>

            <!-- Button to export the planning -->
            <a href="{{ route('export.planning') }}" class="btn btn-success shadow-sm">
                <i class="bi bi-file-earmark-pdf"></i> Exporter PDF
            </a>
        </div>
    </div>

    <!-- Alert for Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card p-4">
        <div class="table-responsive">
            <table id="planningTable" class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date & Heure</th>
                        <th>Salle</th>
                        <th>Étudiant</th>
                        <th>Sujet</th>
                        <th>Encadrant</th>
                        <th>Jury</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($soutenances as $soutenance)
                    <tr>
                        <td>
                            @if($soutenance->creneau)
                                <strong>{{ $soutenance->creneau->date->format('d/m/Y') }}</strong><br>
                                <span class="text-muted">{{ $soutenance->creneau->heure_debut->format('H:i') }} - {{ $soutenance->creneau->heure_fin->format('H:i') }}</span>
                            @else
                                <span class="badge bg-danger">Non assigné</span>
                            @endif
                        </td>
                        <td>
                            @if($soutenance->salle)
                                <span class="badge bg-info text-dark">{{ $soutenance->salle }}</span>
                            @else
                                <span class="badge bg-danger">Non assigné</span>
                            @endif
                        </td>
                        <td>
                            @if($soutenance->projet && $soutenance->projet->etudiant)
                                {{ $soutenance->projet->etudiant->nom }} {{ $soutenance->projet->etudiant->prenom }}
                                <br><small class="text-muted">{{ $soutenance->projet->etudiant->filiere }}</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($soutenance->projet)
                                {{ Str::limit($soutenance->projet->titre, 50) }}
                            @else
                                <span class="text-muted">N/A</span>
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
                            @if($soutenance->jury && $soutenance->jury->enseignants)
                                <ul class="list-unstyled mb-0" style="font-size: 0.9em;">
                                    @foreach($soutenance->jury->enseignants as $enseignant)
                                        <li>
                                            @if($enseignant->pivot->role === 'President')
                                                <strong>[P]</strong>
                                            @else
                                                [R]
                                            @endif
                                            Pr. {{ $enseignant->nom }}
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-danger">Non assigné</span>
                            @endif
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
        $('#planningTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
            },
            order: [[0, 'asc']] // Trier par date/heure par défaut
        });
    });
</script>
</body>
</html>
