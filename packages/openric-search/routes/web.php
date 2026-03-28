<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Search\Controllers\BrowseController;
use OpenRiC\Search\Controllers\SearchController;

Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
Route::get('/browse', [BrowseController::class, 'index'])->name('browse');
