<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Accession\Controllers\AccessionController;

/*
|--------------------------------------------------------------------------
| Accession Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio accession routes. Uses integer IDs instead of slugs.
| All routes require authentication (middleware applied in ServiceProvider).
|
*/

Route::prefix('accessions')->name('accessions.')->group(function () {
    // Basic CRUD
    Route::get('/', [AccessionController::class, 'index'])->name('index');
    Route::get('/create', [AccessionController::class, 'create'])->name('create');
    Route::post('/', [AccessionController::class, 'store'])->name('store');
    Route::get('/{id}', [AccessionController::class, 'show'])->name('show')->whereNumber('id');
    Route::get('/{id}/edit', [AccessionController::class, 'edit'])->name('edit')->whereNumber('id');
    Route::put('/{id}', [AccessionController::class, 'update'])->name('update')->whereNumber('id');
    Route::delete('/{id}', [AccessionController::class, 'destroy'])->name('destroy')->whereNumber('id');
    
    // Additional routes matching controller methods
    Route::get('/queue', [AccessionController::class, 'queue'])->name('queue');
    Route::get('/queue/{id}', [AccessionController::class, 'queueDetail'])->name('queue-detail');
    Route::get('/dashboard', [AccessionController::class, 'dashboard'])->name('dashboard');
    Route::get('/browse', [AccessionController::class, 'browse'])->name('browse');
    Route::get('/export-csv', [AccessionController::class, 'exportCsv'])->name('export-csv');
    Route::get('/appraisal-templates', [AccessionController::class, 'appraisalTemplates'])->name('appraisal-templates');
    Route::get('/intake-config', [AccessionController::class, 'intakeConfig'])->name('intake-config');
    Route::post('/intake-config', [AccessionController::class, 'intakeConfigStore'])->name('intake-config-store');
    Route::get('/intake-queue', [AccessionController::class, 'intakeQueue'])->name('intake-queue');
    Route::get('/numbering', [AccessionController::class, 'numbering'])->name('numbering');
    Route::post('/numbering', [AccessionController::class, 'numberingStore'])->name('numbering-store');
    
    // Record-specific routes
    Route::get('/{id}/appraisal', [AccessionController::class, 'appraisal'])->name('appraisal');
    Route::post('/{id}/appraisal', [AccessionController::class, 'appraisalStore'])->name('appraisal-store');
    Route::get('/{id}/valuation', [AccessionController::class, 'valuation'])->name('valuation');
    Route::get('/{id}/valuation-report', [AccessionController::class, 'valuationReport'])->name('valuation-report');
    Route::get('/{id}/containers', [AccessionController::class, 'containers'])->name('containers');
    Route::get('/{id}/rights', [AccessionController::class, 'rights'])->name('rights');
    Route::get('/{id}/attachments', [AccessionController::class, 'attachments'])->name('attachments');
    Route::post('/{id}/attachments', [AccessionController::class, 'attachmentsStore'])->name('attachments-store');
    Route::get('/{id}/checklist', [AccessionController::class, 'checklist'])->name('checklist');
    Route::post('/{id}/checklist', [AccessionController::class, 'checklistStore'])->name('checklist-store');
    Route::get('/{id}/timeline', [AccessionController::class, 'timeline'])->name('timeline');
    Route::get('/{id}/confirm-delete', [AccessionController::class, 'confirmDelete'])->name('confirm-delete');
});
