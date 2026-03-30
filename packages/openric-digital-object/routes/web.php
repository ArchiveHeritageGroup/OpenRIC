<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\DigitalObject\Controllers\DigitalObjectController;
use OpenRiC\DigitalObject\Controllers\IiifCollectionController;

/*
|--------------------------------------------------------------------------
| Digital Object Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio DAM routes. Uses IRI-based routing instead of slugs.
| All routes require authentication (middleware applied in ServiceProvider).
|
*/

Route::prefix('digital-objects')->name('digital-objects.')->group(function () {
    Route::get('/', [DigitalObjectController::class, 'browse'])->name('browse');
    Route::get('/dashboard', [DigitalObjectController::class, 'dashboard'])->name('dashboard');
    Route::get('/create', [DigitalObjectController::class, 'create'])->name('create');
    Route::post('/', [DigitalObjectController::class, 'store'])->name('store');
    Route::get('/{iri}', [DigitalObjectController::class, 'show'])->name('show');
    Route::get('/{iri}/edit', [DigitalObjectController::class, 'edit'])->name('edit');
    Route::put('/{iri}', [DigitalObjectController::class, 'update'])->name('update');
    Route::delete('/{iri}', [DigitalObjectController::class, 'destroy'])->name('destroy');
    Route::post('/{iri}/upload', [DigitalObjectController::class, 'upload'])->name('upload');
});

/*
|--------------------------------------------------------------------------
| IIIF Collection Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio ahg-iiif-collection routes.
| Public + authenticated routes for IIIF collections, viewer, comparison.
|
*/

// Public IIIF routes
Route::get('/manifest-collections', [IiifCollectionController::class, 'index'])->name('iiif-collection.index');
Route::get('/manifest-collection/{id}/view', [IiifCollectionController::class, 'view'])->name('iiif-collection.view');
Route::get('/manifest-collection/{slug}/manifest.json', [IiifCollectionController::class, 'manifest'])->name('iiif-collection.manifest');
Route::get('/iiif-manifest/{slug}', [IiifCollectionController::class, 'objectManifest'])->name('iiif-collection.object-manifest');

// IIIF Auth endpoints (public)
Route::get('/iiif-auth/access-service-close', fn () => view('openric-digital-object::iiifAuth.access-service-close'))->name('iiif-auth.access-service-close');
Route::get('/iiif-auth/access-token-iframe', fn (\Illuminate\Http\Request $r) => view('openric-digital-object::iiifAuth.access-token-iframe', ['tokenData' => $r->input('tokenData', []), 'origin' => $r->input('origin', '*')]))->name('iiif-auth.access-token-iframe');
Route::get('/iiif-auth/auth-failed', fn () => view('openric-digital-object::iiifAuth.auth-failed'))->name('iiif-auth.auth-failed');
Route::get('/iiif-auth/auth-success', fn () => view('openric-digital-object::iiifAuth.auth-success'))->name('iiif-auth.auth-success');
Route::get('/iiif-auth/clickthrough', fn () => view('openric-digital-object::iiifAuth.clickthrough', ['terms' => '', 'acceptUrl' => '']))->name('iiif-auth.clickthrough');
Route::get('/iiif-auth/logout-success', fn () => view('openric-digital-object::iiifAuth.logout-success'))->name('iiif-auth.logout-success');

// IIIF viewer/compare (public)
Route::get('/iiif-viewer/{slug}', [IiifCollectionController::class, 'viewer'])->name('iiif.viewer');
Route::get('/iiif-compare', [IiifCollectionController::class, 'compare'])->name('iiif.compare');

// Authenticated IIIF collection routes
Route::middleware('auth')->group(function () {
    Route::get('/manifest-collection/new', [IiifCollectionController::class, 'create'])->name('iiif-collection.create');
    Route::post('/manifest-collection', [IiifCollectionController::class, 'store'])->name('iiif-collection.store');
    Route::get('/manifest-collection/{id}/edit', [IiifCollectionController::class, 'edit'])->name('iiif-collection.edit');
    Route::put('/manifest-collection/{id}', [IiifCollectionController::class, 'update'])->name('iiif-collection.update');
    Route::delete('/manifest-collection/{id}', [IiifCollectionController::class, 'destroy'])->name('iiif-collection.destroy');
    Route::match(['get', 'post'], '/manifest-collection/{id}/items/add', [IiifCollectionController::class, 'addItems'])->name('iiif-collection.add-items');
    Route::get('/manifest-collection/remove-item', [IiifCollectionController::class, 'removeItem'])->name('iiif-collection.remove-item');
    Route::post('/manifest-collection/reorder', [IiifCollectionController::class, 'reorder'])->name('iiif-collection.reorder');
    Route::get('/manifest-collections/autocomplete', [IiifCollectionController::class, 'autocomplete'])->name('iiif-collection.autocomplete');

    // IIIF Settings
    Route::get('/admin/iiif-settings', [IiifCollectionController::class, 'settings'])->name('iiif.settings');
    Route::post('/admin/iiif-settings', [IiifCollectionController::class, 'settingsUpdate'])->name('iiif.settings.update');

    // IIIF Validation
    Route::get('/admin/iiif-validation', [IiifCollectionController::class, 'validationDashboard'])->name('iiif.validation-dashboard');

    // Media Settings
    Route::get('/admin/iiif-media/queue', [IiifCollectionController::class, 'mediaQueue'])->name('iiif.media-settings.queue');
    Route::get('/admin/iiif-media/test', [IiifCollectionController::class, 'mediaTest'])->name('iiif.media-settings.test');
    Route::post('/admin/iiif-media/test', [IiifCollectionController::class, 'mediaTestRun'])->name('iiif.media-settings.test.run');

    // 3D Reports
    Route::get('/admin/iiif-3d-reports', [IiifCollectionController::class, 'threeDIndex'])->name('iiif.three-d-reports.index');
    Route::get('/admin/iiif-3d-reports/digital-objects', [IiifCollectionController::class, 'threeDDigitalObjects'])->name('iiif.three-d-reports.digital-objects');
    Route::get('/admin/iiif-3d-reports/hotspots', [IiifCollectionController::class, 'threeDHotspots'])->name('iiif.three-d-reports.hotspots');
    Route::get('/admin/iiif-3d-reports/models', [IiifCollectionController::class, 'threeDModels'])->name('iiif.three-d-reports.models');
    Route::get('/admin/iiif-3d-reports/settings', [IiifCollectionController::class, 'threeDSettings'])->name('iiif.three-d-reports.settings');
    Route::get('/admin/iiif-3d-reports/thumbnails', [IiifCollectionController::class, 'threeDThumbnails'])->name('iiif.three-d-reports.thumbnails');
});
