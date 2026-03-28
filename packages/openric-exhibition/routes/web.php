<?php

use Illuminate\Support\Facades\Route;
use OpenRiC\Exhibition\Http\Controllers\ExhibitionController;

// Public routes
Route::prefix('exhibitions')->group(function () {
    Route::get('/', [ExhibitionController::class, 'index'])->name('exhibition.index');
    Route::get('/dashboard', [ExhibitionController::class, 'dashboard'])->name('exhibition.dashboard');
    Route::get('/{idOrSlug}', [ExhibitionController::class, 'show'])->name('exhibition.show');
});

// Admin routes — authenticated
Route::middleware('auth')->prefix('admin/exhibitions')->group(function () {
    // Exhibition CRUD
    Route::get('/create', [ExhibitionController::class, 'create'])->name('exhibition.create');
    Route::post('/', [ExhibitionController::class, 'store'])->name('exhibition.store');
    Route::get('/{id}/edit', [ExhibitionController::class, 'edit'])->name('exhibition.edit');
    Route::put('/{id}', [ExhibitionController::class, 'update'])->name('exhibition.update');
    Route::delete('/{id}', [ExhibitionController::class, 'destroy'])->name('exhibition.destroy');

    // Objects
    Route::get('/{id}/objects', [ExhibitionController::class, 'objects'])->name('exhibition.objects');
    Route::get('/{id}/object-list', [ExhibitionController::class, 'objectList'])->name('exhibition.objectList');
    Route::get('/{id}/object-list/csv', [ExhibitionController::class, 'objectListCsv'])->name('exhibition.objectListCsv');
    Route::post('/{id}/objects', [ExhibitionController::class, 'addObject'])->name('exhibition.objects.add');
    Route::delete('/{exhibitionId}/objects/{objectId}', [ExhibitionController::class, 'removeObject'])->name('exhibition.objects.remove');
    Route::post('/{id}/objects/reorder', [ExhibitionController::class, 'reorderObjects'])->name('exhibition.objects.reorder');

    // Storylines
    Route::get('/{id}/storylines', [ExhibitionController::class, 'storylines'])->name('exhibition.storylines');
    Route::get('/{exhibitionId}/storylines/{storylineId}', [ExhibitionController::class, 'storyline'])->name('exhibition.storyline');
    Route::post('/{id}/storylines', [ExhibitionController::class, 'storeStoryline'])->name('exhibition.storylines.store');
    Route::delete('/{exhibitionId}/storylines/{storylineId}', [ExhibitionController::class, 'destroyStoryline'])->name('exhibition.storylines.destroy');

    // Sections
    Route::get('/{id}/sections', [ExhibitionController::class, 'sections'])->name('exhibition.sections');
    Route::post('/{id}/sections', [ExhibitionController::class, 'storeSection'])->name('exhibition.sections.store');
    Route::delete('/{exhibitionId}/sections/{sectionId}', [ExhibitionController::class, 'destroySection'])->name('exhibition.sections.destroy');

    // Events
    Route::get('/{id}/events', [ExhibitionController::class, 'events'])->name('exhibition.events');
    Route::post('/{id}/events', [ExhibitionController::class, 'storeEvent'])->name('exhibition.events.store');
    Route::delete('/{exhibitionId}/events/{eventId}', [ExhibitionController::class, 'destroyEvent'])->name('exhibition.events.destroy');

    // Checklists
    Route::get('/{id}/checklists', [ExhibitionController::class, 'checklists'])->name('exhibition.checklists');
    Route::post('/{id}/checklists', [ExhibitionController::class, 'storeChecklist'])->name('exhibition.checklists.store');
    Route::post('/{exhibitionId}/checklists/{checklistId}/toggle', [ExhibitionController::class, 'toggleChecklist'])->name('exhibition.checklists.toggle');
    Route::delete('/{exhibitionId}/checklists/{checklistId}', [ExhibitionController::class, 'destroyChecklist'])->name('exhibition.checklists.destroy');
});
