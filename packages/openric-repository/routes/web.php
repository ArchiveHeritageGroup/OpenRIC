<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Repository\Http\Controllers\RepositoryController;

/*
|--------------------------------------------------------------------------
| Repository (Archival Institution) Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio ahg-repository-manage routes/web.php.
| Public routes: browse, show, print, autocomplete.
| Auth routes: create, edit, update, theme, upload-limit.
| Admin routes: delete.
|
*/

// Public routes
Route::get('/repository/browse', [RepositoryController::class, 'browse'])->name('repository.browse');
Route::get('/repository/autocomplete', [RepositoryController::class, 'autocomplete'])->name('repository.autocomplete');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/repository/add', [RepositoryController::class, 'create'])->name('repository.create');
    Route::post('/repository/add', [RepositoryController::class, 'store'])->name('repository.store');
    Route::get('/repository/{slug}/edit', [RepositoryController::class, 'edit'])->name('repository.edit');
    Route::post('/repository/{slug}/edit', [RepositoryController::class, 'update'])->name('repository.update');
});

// Admin routes
Route::middleware('admin')->group(function () {
    Route::get('/repository/{slug}/delete', [RepositoryController::class, 'confirmDelete'])->name('repository.confirmDelete');
    Route::delete('/repository/{slug}/delete', [RepositoryController::class, 'destroy'])->name('repository.destroy');
});

// Public detail routes (must come after named routes to avoid slug collision)
Route::get('/repository/{slug}/print', [RepositoryController::class, 'print'])->name('repository.print');
Route::get('/repository/{slug}', [RepositoryController::class, 'show'])->name('repository.show');
