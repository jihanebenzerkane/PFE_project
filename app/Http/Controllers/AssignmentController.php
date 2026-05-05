<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AssignmentService;
use App\Models\Soutenance;

class AssignmentController extends Controller
{
    public function __construct(
        protected AssignmentService $assignmentService
    ) {}

    public function runAlgorithm(Request $request)
    {
        try {
            // 1. Planifier les créneaux (générer les dates/heures)
            $this->assignmentService->planifierCreneaux();

            // 2. Assigner les encadrants aux projets sans encadrant
            $this->assignmentService->assignStudentsToEncadrants();

            // 3. Assigner les soutenances (Projet -> Créneau -> Salle)
            $this->assignmentService->runAssignment();

            // 4. Construire les jurys
            $this->assignmentService->buildJuries();

            return redirect()->route('planning.index')->with('success', 'L\'algorithme d\'affectation et de planification a été exécuté avec succès !');
        } catch (\Exception $e) {
            return redirect()->route('planning.index')->with('error', 'Erreur lors de l\'exécution de l\'algorithme: ' . $e->getMessage());
        }
    }

    public function showPlanning()
    {
        // Récupérer toutes les soutenances avec leurs relations pour l'affichage
        $soutenances = Soutenance::with(['projet.etudiant', 'projet.encadrant', 'creneau', 'jury.enseignants'])->get();

        return view('planning.index', compact('soutenances'));
    }
}