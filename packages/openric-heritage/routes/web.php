<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Heritage\Controllers\HeritageController;

Route::prefix('heritage')->name('heritage.')->group(function () {
    Route::get('/', [HeritageController::class, 'dashboard'])->name('dashboard');
    Route::get('/browse', [HeritageController::class, 'browse'])->name('browse');
    Route::get('/create', [HeritageController::class, 'create'])->name('create');
    Route::post('/', [HeritageController::class, 'store'])->name('store');
    Route::get('/{iri}', [HeritageController::class, 'show'])->name('show')->where('iri', '.*');
    Route::get('/{iri}/edit', [HeritageController::class, 'edit'])->name('edit')->where('iri', '.*');
    Route::put('/{iri}', [HeritageController::class, 'update'])->name('update')->where('iri', '.*');
    Route::delete('/{iri}', [HeritageController::class, 'destroy'])->name('destroy')->where('iri', '.*');
});
