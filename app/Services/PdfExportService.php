<?php

namespace App\Services;

use App\Repositories\SalleDAO;
use App\Repositories\JuryDAO;
use App\Models\Soutenance;
use App\Models\Enseignant;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfExportService
{
    public function __construct(
        private SalleDAO $salleDAO,
        private JuryDAO  $juryDAO,
    ) {}

    public function generatePlanning(): \Symfony\Component\HttpFoundation\Response
    {
        $soutenances = Soutenance::with([
            'etudiant',
            'encadrant',
            'jury.enseignants',
            'salle',
            'creneau',
        ])->get();

        $pdf = Pdf::loadView('pdf.planning', [
            'soutenances' => $soutenances,
        ]);

        return $pdf->download('planning.pdf');
    }

    public function generateSupervision(): \Symfony\Component\HttpFoundation\Response
    {
        $enseignants = Enseignant::with('projets.etudiant')->get();

        $pdf = Pdf::loadView('pdf.supervision', [
            'enseignants' => $enseignants,
        ]);

        return $pdf->download('supervision.pdf');
    }
}