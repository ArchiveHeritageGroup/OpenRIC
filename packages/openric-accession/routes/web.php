<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Accession\Controllers\AccessionController;

/*
|--------------------------------------------------------------------------
| Accession Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio accession routes. Uses integer IDs instead of slugs.
| All routes require authentication (middleware applied in ServiceProvider).
|
*/

Route::prefix('accessions')->name('accessions.')->group(function () {
    Route::get('/', [AccessionController::class, 'index'])->name('index');
    Route::get('/create', [AccessionController::class, 'create'])->name('create');
    Route::post('/', [AccessionController::class, 'store'])->name('store');
    Route::get('/{id}', [AccessionController::class, 'show'])->name('show')->whereNumber('id');
    Route::get('/{id}/edit', [AccessionController::class, 'edit'])->name('edit')->whereNumber('id');
    Route::put('/{id}', [AccessionController::class, 'update'])->name('update')->whereNumber('id');
    Route::delete('/{id}', [AccessionController::class, 'destroy'])->name('destroy')->whereNumber('id');
});
