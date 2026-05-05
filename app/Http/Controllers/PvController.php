<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Services\PvService;
use App\Repositories\SoutenanceDAO;

class PvController extends Controller
{
    protected PvService $pvService;
    protected SoutenanceDAO $soutenanceDAO;

    public function __construct(PvService $pvService, SoutenanceDAO $soutenanceDAO)
    {
        $this->pvService = $pvService;
        $this->soutenanceDAO = $soutenanceDAO;
    }

    public function index()
    {
        // 1. Fetch all soutenances (Eager loaded for performance!)
        $soutenances = $this->soutenanceDAO->findAll();
        
        // 2. Return the HTML Blade View
        return view('pvs.index', compact('soutenances'));
    }

    public function downloadPvsArchive()
    {
        $zipFilePath = $this->pvService->organizePvsByTeacher();
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }

    public function downloadSinglePv($id)
    {
        $soutenance = $this->soutenanceDAO->findById($id);
        $savePath = $this->pvService->generatePvForStudent($soutenance);
        return response()->download($savePath)->deleteFileAfterSend(true);
    }

}
