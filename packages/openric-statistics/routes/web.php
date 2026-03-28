<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Statistics\Http\Controllers\StatisticsController;

Route::prefix('admin/statistics')->name('statistics.')->group(function () {
    Route::get('/', [StatisticsController::class, 'dashboard'])->name('dashboard');
    Route::get('/views', [StatisticsController::class, 'views'])->name('views');
    Route::get('/downloads', [StatisticsController::class, 'downloads'])->name('downloads');
    Route::get('/top-items', [StatisticsController::class, 'topItems'])->name('topItems');
    Route::get('/geographic', [StatisticsController::class, 'geographic'])->name('geographic');
    Route::get('/entity', [StatisticsController::class, 'entity'])->name('entity');
    Route::get('/export', [StatisticsController::class, 'export'])->name('export');
    Route::match(['get', 'post'], '/admin', [StatisticsController::class, 'admin'])->name('admin');
    Route::match(['get', 'post'], '/admin/bots', [StatisticsController::class, 'bots'])->name('bots');
    Route::post('/actions', [StatisticsController::class, 'post'])->name('actions');
});
