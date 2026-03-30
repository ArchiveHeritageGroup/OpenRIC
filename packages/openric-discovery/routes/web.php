<?php

use Illuminate\Support\Facades\Route;
use OpenRic\Discovery\Controllers\DiscoveryController;

// Public discovery routes (search is available to all users)
Route::prefix('discovery')->middleware(['web'])->group(function () {
    Route::get('/', [DiscoveryController::class, 'index'])->name('openricdiscovery.index');
    Route::get('/index', [DiscoveryController::class, 'index']);
    Route::get('/search', [DiscoveryController::class, 'search'])->name('openricdiscovery.search');
    Route::get('/suggest', [DiscoveryController::class, 'suggest'])->name('openricdiscovery.suggest');
    Route::get('/popular', [DiscoveryController::class, 'popular'])->name('openricdiscovery.popular');
    Route::post('/click', [DiscoveryController::class, 'click'])->name('openricdiscovery.click');
});
