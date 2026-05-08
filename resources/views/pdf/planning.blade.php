<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Planning des Soutenances</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>Planning des Soutenances</h2>
    <table>
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
                        {{ $soutenance->creneau->date->format('d/m/Y') }}<br>
                        {{ $soutenance->creneau->heure_debut->format('H:i') }} - {{ $soutenance->creneau->heure_fin->format('H:i') }}
                    @else
                        Non assigné
                    @endif
                </td>
                <td>{{ $soutenance->salle ?? 'Non assigné' }}</td>
                <td>
                    @if($soutenance->projet && $soutenance->projet->etudiant)
                        {{ $soutenance->projet->etudiant->nom }} {{ $soutenance->projet->etudiant->prenom }}
                        @if($soutenance->projet->etudiant2)
                            <br>{{ $soutenance->projet->etudiant2->nom }} {{ $soutenance->projet->etudiant2->prenom }}
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    {{ $soutenance->projet ? $soutenance->projet->titre : 'N/A' }}
                </td>
                <td>
                    @if($soutenance->projet && $soutenance->projet->encadrant)
                        Pr. {{ $soutenance->projet->encadrant->nom }}
                    @else
                        Non assigné
                    @endif
                </td>
                <td>
                    @if($soutenance->jury && $soutenance->jury->enseignants)
                        @foreach($soutenance->jury->enseignants as $enseignant)
                            {{ $enseignant->pivot->role === 'President' ? '[P]' : '[R]' }} Pr. {{ $enseignant->nom }}<br>
                        @endforeach
                    @else
                        Non assigné
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
