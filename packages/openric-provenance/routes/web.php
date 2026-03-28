<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Provenance\Controllers\HistoryController;

Route::middleware(['web', 'auth.required'])->group(function () {
    Route::get('/history/{iri}', [HistoryController::class, 'show'])->name('history.show');
    Route::get('/provenance/{iri}', [HistoryController::class, 'provenance'])->name('provenance.show');
});
