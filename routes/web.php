<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PvController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\SalleController;
use App\Http\Controllers\JuryController;
use App\Http\Controllers\ExportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generation-pvs', function () {
    return view('pvs.intro');
})->name('pv.intro');

// PV Generation Routes (Maroua)
Route::get('/pv', [PvController::class, 'index'])->name('pv.index');
Route::get('/pv/download/{id}', [PvController::class, 'downloadSinglePv'])->name('pv.download');
Route::get('/pv/archive/download-all', [PvController::class, 'downloadPvsArchive'])->name('pv.downloadAll');

// Excel Import Routes (Jihane)
Route::get('/import', [ImportController::class, 'showForm'])->name('import.form');
Route::post('/import', [ImportController::class, 'import'])->name('import.store');

// Planning Algorithm Routes (Maroua)
Route::get('/planning', [AssignmentController::class, 'showPlanning'])->name('planning.index');
Route::post('/algorithm/run', [AssignmentController::class, 'runAlgorithm'])->name('algorithm.run');

// Salle Routes (Fati)
Route::get('/salles', [SalleController::class, 'index'])->name('salles.index');
Route::post('/salles', [SalleController::class, 'store'])->name('salles.store');
Route::delete('/salles/{id}', [SalleController::class, 'destroy'])->name('salles.destroy');

// Jury Routes (Fati)
Route::get('/jurys', [JuryController::class, 'index'])->name('jurys.index');
Route::post('/jurys', [JuryController::class, 'store'])->name('jurys.store');
Route::get('/jurys/{id}/pv', [JuryController::class, 'provePvId'])->name('jurys.pv');

// Export Routes (Fati)
Route::get('/export/planning', [ExportController::class, 'downloadPlanning'])->name('export.planning');
Route::get('/export/supervision', [ExportController::class, 'downloadSupervision'])->name('export.supervision');
