<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Integrity\Http\Controllers\IntegrityController;

Route::middleware('auth')->prefix('admin/integrity')->name('integrity.')->group(function (): void {
    Route::get('/', [IntegrityController::class, 'dashboard'])->name('dashboard');
    Route::post('/run', [IntegrityController::class, 'runCheck'])->name('run');
    Route::get('/results/{runId?}', [IntegrityController::class, 'results'])->name('results');
    Route::get('/alerts', [IntegrityController::class, 'alerts'])->name('alerts');
    Route::get('/dead-letter', [IntegrityController::class, 'deadLetter'])->name('dead-letter');
    Route::get('/disposition', [IntegrityController::class, 'disposition'])->name('disposition');
    Route::get('/export', [IntegrityController::class, 'export'])->name('export');
    Route::get('/holds', [IntegrityController::class, 'holds'])->name('holds');
    Route::get('/ledger', [IntegrityController::class, 'ledger'])->name('ledger');
    Route::get('/policies', [IntegrityController::class, 'policies'])->name('policies');
    Route::get('/policies/{id}/edit', [IntegrityController::class, 'policyEdit'])->name('policies.edit')->where('id', '[0-9]+');
    Route::put('/policies/{id}', [IntegrityController::class, 'policyUpdate'])->name('policies.update')->where('id', '[0-9]+');
    Route::get('/report', [IntegrityController::class, 'report'])->name('report');
    Route::get('/runs', [IntegrityController::class, 'runs'])->name('runs');
    Route::get('/runs/{runId}', [IntegrityController::class, 'runDetail'])->name('run-detail');
    Route::get('/schedules', [IntegrityController::class, 'schedules'])->name('schedules');
    Route::get('/schedules/{id}/edit', [IntegrityController::class, 'scheduleEdit'])->name('schedules.edit')->where('id', '[0-9]+');
    Route::put('/schedules/{id}', [IntegrityController::class, 'scheduleUpdate'])->name('schedules.update')->where('id', '[0-9]+');
});
