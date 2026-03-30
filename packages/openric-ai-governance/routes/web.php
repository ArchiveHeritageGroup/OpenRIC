<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\AiGovernance\Http\Controllers\DashboardController;
use OpenRiC\AiGovernance\Http\Controllers\RightsController;
use OpenRiC\AiGovernance\Http\Controllers\BiasController;
use OpenRiC\AiGovernance\Http\Controllers\ProvenanceController;
use OpenRiC\AiGovernance\Http\Controllers\MetricsController;
use OpenRiC\AiGovernance\Http\Controllers\ReadinessController;
use OpenRiC\AiGovernance\Http\Controllers\DerivativesController;
use OpenRiC\AiGovernance\Http\Controllers\LanguagesController;

/*
 * AI Governance routes — all 8 modules of the AI Preparedness Control Framework.
 * Routes are prefixed with /admin/ai-governance by the ServiceProvider.
 */
Route::prefix('')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('ai-governance.dashboard');

    // Module 1: Rights Matrix
    Route::get('rights', [RightsController::class, 'index'])->name('ai-governance.rights');
    Route::get('rights/create', [RightsController::class, 'create'])->name('ai-governance.rights.create');
    Route::post('rights', [RightsController::class, 'store'])->name('ai-governance.rights.store');
    Route::get('rights/{id}', [RightsController::class, 'show'])->name('ai-governance.rights.show');

    // Module 2: Bias Register
    Route::get('bias', [BiasController::class, 'index'])->name('ai-governance.bias');
    Route::get('bias/create', [BiasController::class, 'create'])->name('ai-governance.bias.create');
    Route::post('bias', [BiasController::class, 'store'])->name('ai-governance.bias.store');

    // Module 3: Provenance
    Route::get('provenance', [ProvenanceController::class, 'index'])->name('ai-governance.provenance');

    // Module 4: Metrics
    Route::get('metrics', [MetricsController::class, 'index'])->name('ai-governance.metrics');

    // Module 5: Readiness
    Route::get('readiness', [ReadinessController::class, 'index'])->name('ai-governance.readiness');
    Route::get('readiness/create', [ReadinessController::class, 'create'])->name('ai-governance.readiness.create');
    Route::post('readiness', [ReadinessController::class, 'store'])->name('ai-governance.readiness.store');
    Route::get('readiness/projects', [ReadinessController::class, 'projects'])->name('ai-governance.readiness.projects');

    // Module 6: Derivatives
    Route::get('derivatives', [DerivativesController::class, 'index'])->name('ai-governance.derivatives');
    Route::get('derivatives/create', [DerivativesController::class, 'create'])->name('ai-governance.derivatives.create');
    Route::post('derivatives', [DerivativesController::class, 'store'])->name('ai-governance.derivatives.store');

    // Module 7: Languages
    Route::get('languages', [LanguagesController::class, 'index'])->name('ai-governance.languages');
    Route::get('languages/create', [LanguagesController::class, 'create'])->name('ai-governance.languages.create');
    Route::post('languages', [LanguagesController::class, 'store'])->name('ai-governance.languages.store');
});
