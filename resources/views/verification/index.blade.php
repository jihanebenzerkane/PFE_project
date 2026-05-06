@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="bi bi-shield-check text-primary me-2"></i> Contrôle de Conformité
    </h1>
</div>

<div class="row">
    <!-- Audit Affectation -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100 border-left-info">
            <div class="card-header py-3 bg-info text-white">
                <h6 class="m-0 font-weight-bold"><i class="bi bi-person-lines-fill me-2"></i> Audit des Affectations</h6>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <p class="mb-1 text-muted small">Moyenne d'étudiants par Professeur (Norme : 3 à 4)</p>
                    <h3 class="mb-0 {{ ($moyenneEncadrement >= 3 && $moyenneEncadrement <= 4) ? 'text-success' : 'text-warning' }}">
                        {{ $moyenneEncadrement }} étudiants/prof
                    </h3>
                </div>

                @if(count($affectationAnomalies) > 0)
                    <div class="alert alert-warning mb-0 border-0 shadow-sm">
                        <h6 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Anomalies Détectées ({{ count($affectationAnomalies) }})</h6>
                        <hr class="mt-2 mb-3">
                        <ul class="mb-0 ps-3">
                            @foreach($affectationAnomalies as $anomalie)
                                <li class="mb-2">
                                    <strong>{{ $anomalie['type'] }} :</strong><br>
                                    <span class="small">{{ $anomalie['message'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="alert alert-success mb-0 border-0 shadow-sm">
                        <i class="bi bi-check-circle-fill me-2"></i> Aucune anomalie d'affectation détectée. La répartition est équitable.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Audit Planning -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100 border-left-warning">
            <div class="card-header py-3 bg-warning text-dark">
                <h6 class="m-0 font-weight-bold"><i class="bi bi-calendar-x me-2"></i> Audit du Planning</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">
                    Contrôles effectués : Chevauchements de salles, Doubles assignations horaires, Respect de l'heure de repos.
                </p>

                @if(count($planningAnomalies) > 0)
                    <div class="alert alert-danger mb-0 border-0 shadow-sm">
                        <h6 class="alert-heading fw-bold"><i class="bi bi-x-octagon-fill me-1"></i> Anomalies Détectées ({{ count($planningAnomalies) }})</h6>
                        <hr class="mt-2 mb-3">
                        <ul class="mb-0 ps-3">
                            @foreach($planningAnomalies as $anomalie)
                                <li class="mb-2">
                                    <strong>{{ $anomalie['type'] }} :</strong><br>
                                    <span class="small">{{ $anomalie['message'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="alert alert-success mb-0 border-0 shadow-sm">
                        <i class="bi bi-check-circle-fill me-2"></i> Aucune anomalie de planning détectée. Le calendrier est 100% conforme.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.border-left-info { border-left: 4px solid #0dcaf0 !important; }
.border-left-warning { border-left: 4px solid #ffc107 !important; }
.card-header { border-bottom: none; }
</style>
@endsection
