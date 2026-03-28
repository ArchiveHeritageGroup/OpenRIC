<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Integrity\Http\Controllers\IntegrityController;

Route::middleware('auth')->prefix('admin/integrity')->name('integrity.')->group(function (): void {
    Route::get('/', [IntegrityController::class, 'dashboard'])->name('dashboard');
    Route::post('/run', [IntegrityController::class, 'runCheck'])->name('run');
    Route::get('/results/{runId?}', [IntegrityController::class, 'results'])->name('results');
});
