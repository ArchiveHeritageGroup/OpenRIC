<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Research\Controllers\ResearchController;

Route::prefix('research')->name('research.')->group(function () {
    Route::get('/workspaces', [ResearchController::class, 'workspaces'])->name('workspaces');
    Route::get('/workspaces/create', [ResearchController::class, 'createWorkspace'])->name('workspaces.create');
    Route::post('/workspaces', [ResearchController::class, 'storeWorkspace'])->name('workspaces.store');
    Route::get('/workspaces/{id}', [ResearchController::class, 'workspace'])->name('workspace');

    Route::post('/workspaces/{workspaceId}/items', [ResearchController::class, 'addItem'])->name('workspace.add-item');
    Route::delete('/workspaces/{workspaceId}/items', [ResearchController::class, 'removeItem'])->name('workspace.remove-item');

    Route::get('/annotations/{entityIri}', [ResearchController::class, 'annotations'])->name('annotations')->where('entityIri', '.*');
    Route::post('/annotations', [ResearchController::class, 'addAnnotation'])->name('annotations.store');

    Route::get('/citations/{entityIri}', [ResearchController::class, 'citations'])->name('citations')->where('entityIri', '.*');
    Route::post('/citations', [ResearchController::class, 'addCitation'])->name('citations.store');
});
