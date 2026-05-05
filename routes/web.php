<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PvController;
use App\Http\Controllers\ImportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generation-pvs', function () {
    return view('pvs.intro');
})->name('pv.intro');

Route::get('/pv', [PvController::class, 'index'])->name('pv.index');
Route::get('/pv/download/{id}', [PvController::class, 'downloadSinglePv'])->name('pv.download');
Route::get('/pv/archive/download-all', [PvController::class, 'downloadPvsArchive'])->name('pv.downloadAll');

Route::get('/import', [ImportController::class, 'showForm'])->name('import.form');
Route::post('/import', [ImportController::class, 'import'])->name('import.store');
