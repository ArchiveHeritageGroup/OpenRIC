<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Search\Controllers\BrowseController;
use OpenRiC\Search\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| Search Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio ahg-search routes (27 lines). Expanded to full parity
| with all Heratio endpoints: search, advanced, autocomplete, suggest,
| description updates, global replace, and legacy aliases.
|
*/

Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/advanced', [SearchController::class, 'advanced'])->name('search.advanced');
Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
Route::get('/browse', [BrowseController::class, 'index'])->name('browse');

// Legacy aliases
Route::get('/search/index', fn () => redirect('/search', 301));
Route::get('/search/semantic', [SearchController::class, 'index'])->name('search.semantic');

// Admin search pages
Route::middleware('auth')->group(function () {
    Route::get('/search/descriptionUpdates', [SearchController::class, 'descriptionUpdates'])->name('search.descriptionUpdates');
    Route::match(['get', 'post'], '/search/globalReplace', [SearchController::class, 'globalReplace'])->name('search.globalReplace');
});
