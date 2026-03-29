<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Spectrum\Http\Controllers\SpectrumController;

/*
|--------------------------------------------------------------------------
| OpenRiC Spectrum Routes
|--------------------------------------------------------------------------
|
| All routes for the Spectrum 5.1 collections management procedures.
| Adapted from Heratio ahg-spectrum routes.
|
*/

Route::prefix('admin/spectrum')->middleware(['web', 'auth'])->group(function () {

    // Dashboard & overview
    Route::get('/dashboard', [SpectrumController::class, 'dashboard'])->name('spectrum.dashboard');
    Route::get('/index', [SpectrumController::class, 'index'])->name('spectrum.index');

    // Workflow (per-object) — GET + POST
    Route::match(['get', 'post'], '/workflow', [SpectrumController::class, 'workflow'])->name('spectrum.workflow');

    // General procedures (institution-level) — GET + POST
    Route::get('/general', [SpectrumController::class, 'general'])->name('spectrum.general');
    Route::match(['get', 'post'], '/general-workflow', [SpectrumController::class, 'generalWorkflow'])->name('spectrum.general-workflow');

    // My tasks
    Route::get('/my-tasks', [SpectrumController::class, 'myTasks'])->name('spectrum.my-tasks');

    // Label printing
    Route::get('/label', [SpectrumController::class, 'label'])->name('spectrum.label');

    // Procedure browse pages
    Route::get('/object-entry', [SpectrumController::class, 'objectEntry'])->name('spectrum.object-entry');
    Route::get('/acquisitions', [SpectrumController::class, 'acquisitions'])->name('spectrum.acquisitions');
    Route::get('/loans', [SpectrumController::class, 'loans'])->name('spectrum.loans');
    Route::get('/movements', [SpectrumController::class, 'movements'])->name('spectrum.movements');
    Route::get('/conditions', [SpectrumController::class, 'conditions'])->name('spectrum.conditions');
    Route::get('/conservation', [SpectrumController::class, 'conservation'])->name('spectrum.conservation');
    Route::get('/valuations', [SpectrumController::class, 'valuations'])->name('spectrum.valuations');

    // Condition management
    Route::get('/condition-admin', [SpectrumController::class, 'conditionAdmin'])->name('spectrum.condition-admin');
    Route::get('/condition-photos', [SpectrumController::class, 'conditionPhotos'])->name('spectrum.condition-photos');
    Route::get('/condition-risk', [SpectrumController::class, 'conditionRisk'])->name('spectrum.condition-risk');

    // Data quality
    Route::get('/data-quality', [SpectrumController::class, 'dataQuality'])->name('spectrum.data-quality');

    // GRAP heritage assets
    Route::get('/grap-dashboard', [SpectrumController::class, 'grapDashboard'])->name('spectrum.grap-dashboard');

    // Export
    Route::get('/export', [SpectrumController::class, 'export'])->name('spectrum.export');
    Route::get('/spectrum-export', [SpectrumController::class, 'spectrumExport'])->name('spectrum.spectrum-export');

    // Security compliance
    Route::get('/security-compliance', [SpectrumController::class, 'securityCompliance'])->name('spectrum.security-compliance');

    // Privacy compliance suite
    Route::get('/privacy-admin', [SpectrumController::class, 'privacyAdmin'])->name('spectrum.privacy-admin');
    Route::get('/privacy-compliance', [SpectrumController::class, 'privacyCompliance'])->name('spectrum.privacy-compliance');
    Route::get('/privacy-ropa', [SpectrumController::class, 'privacyRopa'])->name('spectrum.privacy-ropa');
    Route::get('/privacy-dsar', [SpectrumController::class, 'privacyDsar'])->name('spectrum.privacy-dsar');
    Route::get('/privacy-breaches', [SpectrumController::class, 'privacyBreaches'])->name('spectrum.privacy-breaches');
    Route::get('/privacy-templates', [SpectrumController::class, 'privacyTemplates'])->name('spectrum.privacy-templates');
});
