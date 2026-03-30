<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\DoiManage\Http\Controllers\DoiController;

/*
|--------------------------------------------------------------------------
| DOI Management Routes -- adapted from Heratio ahg-doi-manage/routes/web.php
|--------------------------------------------------------------------------
|
| All routes are prefixed with /admin/doi and require admin middleware
| (applied by the service provider).
|
*/

Route::prefix('admin/doi')->group(function (): void {
    // Dashboard
    Route::get('/', [DoiController::class, 'index'])->name('doi.index');

    // Browse
    Route::get('/browse', [DoiController::class, 'browse'])->name('doi.browse');

    // View single DOI
    Route::get('/view/{id}', [DoiController::class, 'show'])->name('doi.view')->whereNumber('id');

    // Mint (single)
    Route::get('/mint', [DoiController::class, 'mintForm'])->name('doi.mint.form');
    Route::post('/mint', [DoiController::class, 'mint'])->name('doi.mint');

    // Batch Mint
    Route::match(['get', 'post'], '/batch-mint', [DoiController::class, 'batchMint'])->name('doi.batch-mint');

    // Sync metadata
    Route::post('/{id}/sync', [DoiController::class, 'sync'])->name('doi.sync')->whereNumber('id');

    // Deactivate / Reactivate
    Route::post('/{id}/deactivate', [DoiController::class, 'deactivate'])->name('doi.deactivate')->whereNumber('id');
    Route::post('/{id}/reactivate', [DoiController::class, 'reactivate'])->name('doi.reactivate')->whereNumber('id');

    // Queue
    Route::get('/queue', [DoiController::class, 'queue'])->name('doi.queue');

    // Configuration
    Route::get('/config', [DoiController::class, 'config'])->name('doi.config');
    Route::post('/config', [DoiController::class, 'configSave'])->name('doi.configSave');

    // Reports & Export
    Route::get('/report', [DoiController::class, 'report'])->name('doi.report');
});
