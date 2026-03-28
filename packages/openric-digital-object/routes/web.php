<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\DigitalObject\Controllers\DigitalObjectController;

/*
|--------------------------------------------------------------------------
| Digital Object Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio DAM routes. Uses IRI-based routing instead of slugs.
| All routes require authentication (middleware applied in ServiceProvider).
|
*/

Route::prefix('digital-objects')->name('digital-objects.')->group(function () {
    Route::get('/', [DigitalObjectController::class, 'browse'])->name('browse');
    Route::get('/dashboard', [DigitalObjectController::class, 'dashboard'])->name('dashboard');
    Route::get('/create', [DigitalObjectController::class, 'create'])->name('create');
    Route::post('/', [DigitalObjectController::class, 'store'])->name('store');
    Route::get('/{iri}', [DigitalObjectController::class, 'show'])->name('show');
    Route::get('/{iri}/edit', [DigitalObjectController::class, 'edit'])->name('edit');
    Route::put('/{iri}', [DigitalObjectController::class, 'update'])->name('update');
    Route::delete('/{iri}', [DigitalObjectController::class, 'destroy'])->name('destroy');
    Route::post('/{iri}/upload', [DigitalObjectController::class, 'upload'])->name('upload');
});
