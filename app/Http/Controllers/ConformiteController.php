<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use App\Models\Creneau;
use App\Models\Etudiant;
use App\Models\Soutenance;
use Illuminate\Support\Facades\Storage;

class ConformiteController extends Controller
{
    public function index()
    {
        // 1. Try to load from persistent JSON file (written at last algorithm run)
        if (Storage::exists('conformite_diagnostic.json')) {
            $diagnostic = json_decode(Storage::get('conformite_diagnostic.json'), true);
        } else {
            // 2. Fallback: build live from current DB state
            $diagnostic = $this->buildLiveDiagnostic();
        }

        return view('conformite.index', compact('diagnostic'));
    }

    private function buildLiveDiagnostic(): array
    {
        $totalEtudiants = Etudiant::count();
        $affectes       = Soutenance::count();
        $nonAffectes    = $totalEtudiants - $affectes;
        $pct            = $totalEtudiants > 0 ? round(($affectes / $totalEtudiants) * 100) : 0;
        $nbSalles       = Salle::count();
        $nbDates        = Creneau::distinct('date')->count('date');
        $capaciteMax    = $nbDates * 7 * $nbSalles;

        $etudiantsNonAffectes = Etudiant::whereDoesntHave('projet.soutenance')
            ->with('projet.encadrant')
            ->get();

        return [
            'pct'                 => $pct,
            'total'               => $totalEtudiants,
            'affectes'            => $affectes,
            'non_affectes'        => $nonAffectes,
            'nb_salles'           => $nbSalles,
            'nb_dates'            => $nbDates,
            'capacite_max'        => $capaciteMax,
            'manque_capacite'     => max(0, $totalEtudiants - $capaciteMax),
            'etudiants_manquants' => $etudiantsNonAffectes->map(fn($e) => [
                'nom'       => $e->nom,
                'prenom'    => $e->prenom,
                'filiere'   => $e->filiere,
                'encadrant' => $e->projet?->encadrant
                    ? ($e->projet->encadrant->nom . ' ' . $e->projet->encadrant->prenom)
                    : 'Non assigné',
            ])->toArray(),
        ];
    }
}
