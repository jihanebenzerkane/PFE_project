@extends('layouts.app')

@section('title', 'Génération des PVs')

@push('styles')
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        .pv-card {
            border-radius: 16px;
            border: none;
            background: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .pv-table thead th {
            background: #F8FAFC;
            border-bottom: none;
            font-weight: 700;
            color: #0F172A;
        }

        .pv-badge {
            font-size: 0.78rem;
            padding: 7px 14px;
            border-radius: 999px;
            font-weight: 700;
            display: inline-block;
            white-space: nowrap;
        }

        .btn-download {
            border-radius: 10px;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title h2 {
            margin: 0;
            font-size: 1.9rem;
            font-weight: 700;
            color: #0F172A;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')

    <div class="header-actions">
        <div class="page-title">
            <i class="bi bi-file-earmark-word-fill text-primary fs-1"></i>
            <h2>Génération des PVs</h2>
        </div>

        <a href="{{ route('pv.downloadAll') }}" class="btn btn-primary btn-download shadow-sm">
            <i class="bi bi-file-zip"></i>
            Télécharger Tous (Archive ZIP)
        </a>
    </div>

    <div class="pv-card p-4">

        <div class="table-responsive">
            <table id="pvTable" class="table table-hover align-middle pv-table">

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
                    @foreach ($soutenances as $soutenance)
                        @php
                            $etudiant = $soutenance->projet?->etudiant;
                            $encadrant = $soutenance->projet?->encadrant;

                            $filiere = $etudiant?->filiere ?? '';

                            // Use centralized filière colors from PdfExportService
                            $bgColor = \App\Services\PdfExportService::applyFiliereColor($filiere);

                            // Choose readable text color
                            $textColor = '#000000';

                            if (in_array($bgColor, ['#BDD7EE'])) {
                                $textColor = '#0F172A';
                            }

                            if (in_array($bgColor, ['#C6EFCE'])) {
                                $textColor = '#065F46';
                            }

                            if (in_array($bgColor, ['#F4B183'])) {
                                $textColor = '#7C2D12';
                            }
                        @endphp

                        <tr>

                            <td data-order="{{ $soutenance->id }}">
                                <strong>#{{ $soutenance->id }}</strong>
                            </td>

                            <td>
                                @if ($etudiant)
                                    <div class="fw-semibold">
                                        {{ strtoupper($etudiant->nom) }} {{ $etudiant->prenom }}
                                    </div>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>

                            <td>
                                @if ($etudiant)
                                    <span class="pv-badge"
                                        style="
        background-color: {{ $bgColor }};
        color: {{ $textColor }};
      ">
                                        {{ $filiere }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if ($encadrant)
                                    <span class="fw-semibold">
                                        Pr. {{ strtoupper($encadrant->nom) }}
                                    </span>
                                @else
                                    <span class="text-danger fw-semibold">
                                        Non assigné
                                    </span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('pv.download', $soutenance->id) }}"
                                    class="btn btn-success btn-sm btn-download">
                                    <i class="bi bi-download"></i>
                                    Télécharger PV
                                </a>
                            </td>

                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

    </div>

@endsection

@push('scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#pvTable').DataTable({
                order: [
                    [0, 'asc']
                ],
                columns: [{
                        type: 'num'
                    },
                    null,
                    null,
                    null,
                    null
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
                },
                pageLength: 10,
                responsive: true
            });
        });
    </script>
@endpush
