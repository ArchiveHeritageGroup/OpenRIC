<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Dedupe\Controllers\DedupeController;

/*
|--------------------------------------------------------------------------
| Dedupe Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio dedupe routes. Uses integer IDs for candidate tracking.
| All routes require authentication (middleware applied in ServiceProvider).
|
*/

Route::prefix('dedupe')->name('dedupe.')->group(function () {
    Route::get('/', [DedupeController::class, 'dashboard'])->name('dashboard');
    Route::get('/records', [DedupeController::class, 'records'])->name('records');
    Route::get('/agents', [DedupeController::class, 'agents'])->name('agents');
    Route::get('/compare/{id}', [DedupeController::class, 'compare'])->name('compare')->whereNumber('id');
    Route::match(['GET', 'POST'], '/merge/{id}', [DedupeController::class, 'merge'])->name('merge')->whereNumber('id');
    Route::post('/resolve/{id}', [DedupeController::class, 'resolve'])->name('resolve')->whereNumber('id');
});
