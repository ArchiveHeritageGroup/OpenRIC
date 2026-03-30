<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\AiGovernance\Http\Controllers\DashboardController;
use OpenRiC\AiGovernance\Http\Controllers\RightsController;
use OpenRiC\AiGovernance\Http\Controllers\ProvenanceController;
use OpenRiC\AiGovernance\Http\Controllers\BiasController;
use OpenRiC\AiGovernance\Http\Controllers\MetricsController;
use OpenRiC\AiGovernance\Http\Controllers\ReadinessController;
use OpenRiC\AiGovernance\Http\Controllers\DerivativesController;
use OpenRiC\AiGovernance\Http\Controllers\LanguagesController;

/*
|--------------------------------------------------------------------------
| AI Governance Routes
|--------------------------------------------------------------------------
|
| Routes for the AI Governance Framework module.
| Requires admin middleware for most routes.
|
*/

Route::prefix('ai-governance')->name('ai-governance.')->group(function () {
    
    // Dashboard - requires admin
    Route::middleware(['admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        
        // AI Rights Management
        Route::get('/rights', [RightsController::class, 'index'])->name('rights.index');
        Route::get('/rights/create', [RightsController::class, 'create'])->name('rights.create');
        Route::post('/rights', [RightsController::class, 'store'])->name('rights.store');
        Route::get('/rights/{id}', [RightsController::class, 'show'])->name('rights.show');
        Route::put('/rights/{id}', [RightsController::class, 'update'])->name('rights.update');
        Route::delete('/rights/{id}', [RightsController::class, 'destroy'])->name('rights.destroy');
        Route::get('/rights/entity/{iri}', [RightsController::class, 'byEntity'])->name('rights.by-entity');
        
        // AI Output Provenance
        Route::get('/provenance', [ProvenanceController::class, 'index'])->name('provenance.index');
        Route::get('/provenance/pending', [ProvenanceController::class, 'pending'])->name('provenance.pending');
        Route::get('/provenance/{id}', [ProvenanceController::class, 'show'])->name('provenance.show');
        Route::post('/provenance/{id}/approve', [ProvenanceController::class, 'approve'])->name('provenance.approve');
        Route::get('/provenance/entity/{iri}', [ProvenanceController::class, 'byEntity'])->name('provenance.by-entity');
        
        // Bias/Harm Register
        Route::get('/bias', [BiasController::class, 'index'])->name('bias.index');
        Route::get('/bias/create', [BiasController::class, 'create'])->name('bias.create');
        Route::post('/bias', [BiasController::class, 'store'])->name('bias.store');
        Route::get('/bias/{id}', [BiasController::class, 'show'])->name('bias.show');
        Route::put('/bias/{id}', [BiasController::class, 'update'])->name('bias.update');
        Route::post('/bias/{id}/resolve', [BiasController::class, 'resolve'])->name('bias.resolve');
        
        // Evaluation Metrics
        Route::get('/metrics', [MetricsController::class, 'index'])->name('metrics.index');
        Route::get('/metrics/create', [MetricsController::class, 'create'])->name('metrics.create');
        Route::post('/metrics', [MetricsController::class, 'store'])->name('metrics.store');
        Route::get('/metrics/{metricType}', [MetricsController::class, 'show'])->name('metrics.show');
        
        // AI Readiness Profiles
        Route::get('/readiness', [ReadinessController::class, 'index'])->name('readiness.index');
        Route::get('/readiness/create', [ReadinessController::class, 'create'])->name('readiness.create');
        Route::post('/readiness', [ReadinessController::class, 'store'])->name('readiness.store');
        Route::get('/readiness/{id}/edit', [ReadinessController::class, 'edit'])->name('readiness.edit');
        Route::put('/readiness/{id}', [ReadinessController::class, 'update'])->name('readiness.update');
        Route::get('/readiness/collection/{iri}', [ReadinessController::class, 'byCollection'])->name('readiness.by-collection');
        
        // Derivative Profiles
        Route::get('/derivatives', [DerivativesController::class, 'index'])->name('derivatives.index');
        Route::get('/derivatives/create', [DerivativesController::class, 'create'])->name('derivatives.create');
        Route::post('/derivatives', [DerivativesController::class, 'store'])->name('derivatives.store');
        Route::get('/derivatives/{id}/edit', [DerivativesController::class, 'edit'])->name('derivatives.edit');
        Route::put('/derivatives/{id}', [DerivativesController::class, 'update'])->name('derivatives.update');
        
        // Language AI Settings
        Route::get('/languages', [LanguagesController::class, 'index'])->name('languages.index');
        Route::get('/languages/create', [LanguagesController::class, 'create'])->name('languages.create');
        Route::post('/languages', [LanguagesController::class, 'store'])->name('languages.store');
        Route::get('/languages/{code}/edit', [LanguagesController::class, 'edit'])->name('languages.edit');
        Route::put('/languages/{code}', [LanguagesController::class, 'update'])->name('languages.update');
        
        // Project Readiness
        Route::get('/projects', [ReadinessController::class, 'projects'])->name('projects.index');
        Route::get('/projects/create', [ReadinessController::class, 'createProject'])->name('projects.create');
        Route::post('/projects', [ReadinessController::class, 'storeProject'])->name('projects.store');
        Route::get('/projects/{id}', [ReadinessController::class, 'showProject'])->name('projects.show');
        Route::put('/projects/{id}', [ReadinessController::class, 'updateProject'])->name('projects.update');
        Route::post('/projects/{id}/submit', [ReadinessController::class, 'submitProject'])->name('projects.submit');
        Route::post('/projects/{id}/approve', [ReadinessController::class, 'approveProject'])->name('projects.approve');
    });
});
