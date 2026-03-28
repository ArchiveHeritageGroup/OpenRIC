<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\AI\Controllers\AiController;

/*
 * AI routes — adapted from Heratio ahg-ai-services routes (190 lines).
 * All routes require authentication + admin role.
 */
Route::middleware(['web', 'auth'])->prefix('admin/ai')->group(function () {
    // Dashboard & config
    Route::get('/', [AiController::class, 'dashboard'])->name('admin.ai.dashboard');
    Route::get('config', [AiController::class, 'config'])->name('admin.ai.config');
    Route::post('config', [AiController::class, 'configStore'])->name('admin.ai.config.store');
    Route::delete('config/{id}', [AiController::class, 'configDelete'])->name('admin.ai.config.delete');

    // AJAX JSON endpoints
    Route::post('summarize', [AiController::class, 'summarize'])->name('admin.ai.summarize');
    Route::post('translate', [AiController::class, 'translate'])->name('admin.ai.translate');
    Route::post('extract-entities', [AiController::class, 'extractEntities'])->name('admin.ai.extract-entities');
    Route::post('suggest-description', [AiController::class, 'suggestDescription'])->name('admin.ai.suggest-description');
    Route::post('spellcheck', [AiController::class, 'spellcheck'])->name('admin.ai.spellcheck');
    Route::post('test-connection', [AiController::class, 'testConnection'])->name('admin.ai.test-connection');
    Route::get('health', [AiController::class, 'health'])->name('admin.ai.health');

    // NER entity management
    Route::get('ner/review', [AiController::class, 'nerReview'])->name('admin.ai.ner.review');
    Route::post('ner/extract', [AiController::class, 'nerExtract'])->name('admin.ai.ner.extract');
    Route::get('ner/entities', [AiController::class, 'nerEntities'])->name('admin.ai.ner.entities');
    Route::post('ner/update', [AiController::class, 'nerUpdate'])->name('admin.ai.ner.update');
    Route::post('ner/bulk-save', [AiController::class, 'nerBulkSave'])->name('admin.ai.ner.bulk-save');

    // HTR
    Route::get('htr/health', [AiController::class, 'htrHealth'])->name('admin.ai.htr.health');

    // Suggestions
    Route::get('suggestions', [AiController::class, 'suggestions'])->name('admin.ai.suggestions');
    Route::post('suggestions/{id}/decision', [AiController::class, 'suggestionDecision'])->name('admin.ai.suggestions.decision');

    // Jobs
    Route::get('jobs', [AiController::class, 'jobs'])->name('admin.ai.jobs');
});
