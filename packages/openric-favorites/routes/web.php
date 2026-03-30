<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Favorites\Http\Controllers\FavoritesController;

// Public: shared folder view (no auth required)
Route::get('/favorites/shared/{token}', [FavoritesController::class, 'viewShared'])
    ->name('favorites.shared');

// All other routes require authentication
Route::middleware('auth')->prefix('favorites')->name('favorites.')->group(function (): void {
    // Browse
    Route::get('/', [FavoritesController::class, 'browse'])->name('browse');

    // Toggle / Remove / Clear
    Route::post('/toggle', [FavoritesController::class, 'toggle'])->name('toggle');
    Route::post('/remove/{id}', [FavoritesController::class, 'remove'])->name('remove')
        ->where('id', '[0-9]+');
    Route::post('/clear', [FavoritesController::class, 'clear'])->name('clear');

    // Bulk operations
    Route::post('/bulk', [FavoritesController::class, 'bulk'])->name('bulk');

    // Notes (AJAX)
    Route::post('/notes/{id}', [FavoritesController::class, 'updateNotes'])->name('notes')
        ->where('id', '[0-9]+');

    // AJAX status check
    Route::get('/ajax/status', [FavoritesController::class, 'ajaxStatus'])->name('ajax.status');

    // Folder management
    Route::post('/folder/create', [FavoritesController::class, 'folderCreate'])->name('folder.create');
    Route::post('/folder/{id}/edit', [FavoritesController::class, 'folderEdit'])->name('folder.edit')
        ->where('id', '[0-9]+');
    Route::post('/folder/{id}/delete', [FavoritesController::class, 'folderDelete'])->name('folder.delete')
        ->where('id', '[0-9]+');
    Route::post('/folder/{id}/share', [FavoritesController::class, 'shareFolder'])->name('folder.share')
        ->where('id', '[0-9]+');
    Route::post('/folder/{id}/revoke-share', [FavoritesController::class, 'revokeSharing'])->name('folder.revoke')
        ->where('id', '[0-9]+');

    // Export / Import
    Route::get('/export/csv', [FavoritesController::class, 'exportCsv'])->name('export.csv');
    Route::get('/export/json', [FavoritesController::class, 'exportJson'])->name('export.json');
    Route::post('/import', [FavoritesController::class, 'importFavorites'])->name('import');
});
