<?php

namespace App\Http\Controllers;

use App\Services\PdfExportService;

class ExportController extends Controller
{
    public function __construct(
        private PdfExportService $pdfExportService
    ) {}

    public function downloadPlanning()
    {
        return $this->pdfExportService->generatePlanning();
    }

    public function downloadSupervision()
    {
        return $this->pdfExportService->generateSupervision();
    }
}