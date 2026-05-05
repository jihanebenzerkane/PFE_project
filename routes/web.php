<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SalleController;
use App\Http\Controllers\JuryController;
use App\Http\Controllers\ExportController;

Route::get('/', function () {
    return view('welcome');

});






// Salle Routes

































Route::get('/salles', [SalleController::class, 'index'])
    ->name('salles.index');

Route::post('/salles', [SalleController::class, 'store'])
    ->name('salles.store');

Route::delete('/salles/{id}', [SalleController::class, 'destroy'])
    ->name('salles.destroy');

// Jury Routes 

Route::get('/jurys', [JuryController::class, 'index'])
    ->name('jurys.index');

Route::post('/jurys', [JuryController::class, 'store'])
    ->name('jurys.store');

Route::get('/jurys/{id}/pv', [JuryController::class, 'provePvId'])
    ->name('jurys.pv');

// Export Routes

Route::get('/export/planning', [ExportController::class, 'downloadPlanning'])
    ->name('export.planning');

Route::get('/export/supervision', [ExportController::class, 'downloadSupervision'])
    ->name('export.supervision');
