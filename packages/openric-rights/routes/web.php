<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Rights\Controllers\EmbargoController;
use OpenRiC\Rights\Controllers\ExtendedRightsController;
use OpenRiC\Rights\Controllers\PremisRightsController;
use OpenRiC\Rights\Controllers\RightsAdminController;
use OpenRiC\Rights\Controllers\RightsController;
use OpenRiC\Rights\Controllers\RightsHolderController;

/*
|--------------------------------------------------------------------------
| Rights Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio ahg-rights-holder-manage routes (67 lines).
| Heratio uses slug-based routing; OpenRiC uses integer IDs.
| All routes require authentication (middleware applied in ServiceProvider).
|
*/

// ── Rights Statements CRUD ──────────────────────────────────────
Route::prefix('admin/rights')->name('rights.')->group(function () {
    Route::get('/', [RightsController::class, 'index'])->name('index');
    Route::get('/create', [RightsController::class, 'create'])->name('create');
    Route::post('/', [RightsController::class, 'store'])->name('store');
    Route::get('/{id}', [RightsController::class, 'show'])->name('show')->where('id', '[0-9]+');
    Route::get('/{id}/edit', [RightsController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
    Route::put('/{id}', [RightsController::class, 'update'])->name('update')->where('id', '[0-9]+');
    Route::delete('/{id}', [RightsController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');

    Route::get('/embargoes', [RightsController::class, 'embargoes'])->name('embargoes');
    Route::post('/embargoes', [RightsController::class, 'createEmbargo'])->name('embargoes.store');
    Route::post('/embargoes/{id}/lift', [RightsController::class, 'liftEmbargoAction'])->name('embargoes.lift');

    Route::get('/tk-labels', [RightsController::class, 'tkLabels'])->name('tk-labels');
    Route::post('/tk-labels', [RightsController::class, 'assignTkLabel'])->name('tk-labels.store');
    Route::delete('/tk-labels/{id}', [RightsController::class, 'removeTkLabelAction'])->name('tk-labels.destroy');
});

// ── Rights Holders CRUD ─────────────────────────────────────────
Route::prefix('rights-holders')->name('rights.holders.')->group(function () {
    Route::get('/browse', [RightsHolderController::class, 'browse'])->name('browse');
    Route::get('/create', [RightsHolderController::class, 'create'])->name('create');
    Route::post('/', [RightsHolderController::class, 'store'])->name('store');
    Route::get('/{id}', [RightsHolderController::class, 'show'])->name('show')->where('id', '[0-9]+');
    Route::get('/{id}/edit', [RightsHolderController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
    Route::put('/{id}', [RightsHolderController::class, 'update'])->name('update')->where('id', '[0-9]+');
    Route::get('/{id}/delete', [RightsHolderController::class, 'confirmDelete'])->name('confirmDelete')->where('id', '[0-9]+');
    Route::delete('/{id}', [RightsHolderController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
});

// ── Embargo Management ──────────────────────────────────────────
Route::prefix('embargo')->name('rights.embargo.')->group(function () {
    Route::get('/', [EmbargoController::class, 'index'])->name('index');
    Route::get('/add', [EmbargoController::class, 'add'])->name('add');
    Route::post('/', [EmbargoController::class, 'store'])->name('store');
    Route::get('/{id}', [EmbargoController::class, 'view'])->name('view')->where('id', '[0-9]+');
    Route::get('/{id}/lift', [EmbargoController::class, 'liftForm'])->name('liftForm')->where('id', '[0-9]+');
    Route::post('/{id}/lift', [EmbargoController::class, 'lift'])->name('lift')->where('id', '[0-9]+');
});

// ── Extended Rights ─────────────────────────────────────────────
Route::prefix('extended-rights')->name('rights.extended.')->group(function () {
    Route::get('/', [ExtendedRightsController::class, 'index'])->name('index');
    Route::get('/dashboard', [ExtendedRightsController::class, 'dashboard'])->name('dashboard');
    Route::get('/view', [ExtendedRightsController::class, 'view'])->name('view');
    Route::get('/batch', [ExtendedRightsController::class, 'batch'])->name('batch');
    Route::post('/batch', [ExtendedRightsController::class, 'batchStore'])->name('batch.store');
    Route::get('/clear', [ExtendedRightsController::class, 'clear'])->name('clear');
    Route::post('/clear', [ExtendedRightsController::class, 'clearStore'])->name('clear.store');
    Route::get('/embargoes', [ExtendedRightsController::class, 'embargoes'])->name('embargoes');
    Route::get('/expiring-embargoes', [ExtendedRightsController::class, 'expiringEmbargoes'])->name('expiring-embargoes');
    Route::get('/export', [ExtendedRightsController::class, 'export'])->name('export');
    Route::get('/embargo-status', [ExtendedRightsController::class, 'embargoStatus'])->name('embargo-status');
    Route::get('/embargo-blocked', [ExtendedRightsController::class, 'embargoBlocked'])->name('embargo-blocked');
    Route::post('/lift-embargo/{id}', [ExtendedRightsController::class, 'liftEmbargo'])->name('lift-embargo')->where('id', '[0-9]+');
});

// ── PREMIS Rights Display ───────────────────────────────────────
Route::get('/premis-rights', [PremisRightsController::class, 'index'])->name('rights.premis.index');

// ── Rights Admin ────────────────────────────────────────────────
Route::prefix('rights-admin')->name('rights.admin.')->group(function () {
    Route::get('/', [RightsAdminController::class, 'index'])->name('index');
    Route::get('/embargoes', [RightsAdminController::class, 'embargoes'])->name('embargoes');
    Route::get('/embargoes/{id}/edit', [RightsAdminController::class, 'embargoEdit'])->name('embargo-edit')->where('id', '[0-9]+');
    Route::put('/embargoes/{id}', [RightsAdminController::class, 'embargoUpdate'])->name('embargo-update')->where('id', '[0-9]+');
    Route::get('/orphan-works', [RightsAdminController::class, 'orphanWorks'])->name('orphan-works');
    Route::get('/orphan-works/{id}/edit', [RightsAdminController::class, 'orphanWorkEdit'])->name('orphan-work-edit')->where('id', '[0-9]+');
    Route::put('/orphan-works/{id}', [RightsAdminController::class, 'orphanWorkUpdate'])->name('orphan-work-update')->where('id', '[0-9]+');
    Route::get('/report', [RightsAdminController::class, 'report'])->name('report');
    Route::get('/statements', [RightsAdminController::class, 'statements'])->name('statements');
    Route::get('/tk-labels', [RightsAdminController::class, 'tkLabels'])->name('tk-labels');
});
