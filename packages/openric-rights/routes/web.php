<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Rights\Controllers\RightsController;

Route::prefix('admin/rights')->name('rights.')->group(function () {
    Route::get('/', [RightsController::class, 'index'])->name('index');
    Route::get('/create', [RightsController::class, 'create'])->name('create');
    Route::post('/', [RightsController::class, 'store'])->name('store');
    Route::get('/{id}', [RightsController::class, 'show'])->name('show')->where('id', '[0-9]+');
    Route::get('/{id}/edit', [RightsController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
    Route::put('/{id}', [RightsController::class, 'update'])->name('update')->where('id', '[0-9]+');
    Route::delete('/{id}', [RightsController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');

    Route::get('/embargoes', [RightsController::class, 'embargoes'])->name('embargoes');
    Route::post('/embargoes', [RightsController::class, 'createEmbargo'])->name('embargoes.store');
    Route::post('/embargoes/{id}/lift', [RightsController::class, 'liftEmbargoAction'])->name('embargoes.lift');

    Route::get('/tk-labels', [RightsController::class, 'tkLabels'])->name('tk-labels');
    Route::post('/tk-labels', [RightsController::class, 'assignTkLabel'])->name('tk-labels.store');
    Route::delete('/tk-labels/{id}', [RightsController::class, 'removeTkLabelAction'])->name('tk-labels.destroy');
});
