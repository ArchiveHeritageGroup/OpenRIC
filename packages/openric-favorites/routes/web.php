<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Favorites\Http\Controllers\FavoritesController;

Route::middleware('auth')->prefix('favorites')->name('favorites.')->group(function (): void {
    Route::get('/', [FavoritesController::class, 'index'])->name('index');
    Route::post('/toggle', [FavoritesController::class, 'toggle'])->name('toggle');
    Route::post('/clear', [FavoritesController::class, 'clear'])->name('clear');
    Route::post('/remove/{id}', [FavoritesController::class, 'remove'])->name('remove');
    Route::get('/status', [FavoritesController::class, 'status'])->name('status');
    Route::get('/export/csv', [FavoritesController::class, 'exportCsv'])->name('export.csv');
});
