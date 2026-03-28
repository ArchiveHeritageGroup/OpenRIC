<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Reports\Controllers\ReportController;

Route::prefix('admin/reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'dashboard'])->name('dashboard');
    Route::get('/collections', [ReportController::class, 'collections'])->name('collections');
    Route::get('/users', [ReportController::class, 'users'])->name('users');
    Route::get('/access', [ReportController::class, 'access'])->name('access');
    Route::get('/search', [ReportController::class, 'search'])->name('search');
    Route::get('/export', [ReportController::class, 'export'])->name('export');
});
