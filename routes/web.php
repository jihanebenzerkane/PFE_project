<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\PvController;

Route::get('/generation-pvs', function () {
    return view('pvs.intro');
})->name('pv.intro');

Route::get('/pv', [PvController::class, 'index'])->name('pv.index');
Route::get('/pv/download/{id}', [PvController::class, 'downloadSinglePv'])->name('pv.download');
Route::get('/pv/archive/download-all', [PvController::class, 'downloadPvsArchive'])->name('pv.downloadAll');
