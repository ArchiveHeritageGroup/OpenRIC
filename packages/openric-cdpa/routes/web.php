<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin/cdpa')->middleware(['web', 'admin'])->group(function () {
    // Dashboard
    Route::get('/', [\OpenRicCdpa\Controllers\CdpaController::class, 'index'])->name('openriccdpa.index');

    // Config (GET + POST) — ACL must be checked in controller (Route::match)
    Route::match(['get', 'post'], '/config', [\OpenRicCdpa\Controllers\CdpaController::class, 'config'])->name('openriccdpa.config');

    // DPO management
    Route::get('/dpo', [\OpenRicCdpa\Controllers\CdpaController::class, 'dpo'])->name('openriccdpa.dpo');
    Route::match(['get', 'post'], '/dpo/edit', [\OpenRicCdpa\Controllers\CdpaController::class, 'dpoEdit'])->name('openriccdpa.dpo-edit'); // ACL must be checked in controller (Route::match)

    // Processing activities CRUD
    Route::get('/processing', [\OpenRicCdpa\Controllers\CdpaController::class, 'processing'])->name('openriccdpa.processing');
    Route::match(['get', 'post'], '/processing/create', [\OpenRicCdpa\Controllers\CdpaController::class, 'processingCreate'])->name('openriccdpa.processing-create'); // ACL must be checked in controller (Route::match)
    Route::match(['get', 'post'], '/processing/edit', [\OpenRicCdpa\Controllers\CdpaController::class, 'processingEdit'])->name('openriccdpa.processing-edit'); // ACL must be checked in controller (Route::match)

    // Consent records
    Route::get('/consent', [\OpenRicCdpa\Controllers\CdpaController::class, 'consent'])->name('openriccdpa.consent');

    // Data subject requests CRUD
    Route::get('/requests', [\OpenRicCdpa\Controllers\CdpaController::class, 'requests'])->name('openriccdpa.requests');
    Route::match(['get', 'post'], '/requests/create', [\OpenRicCdpa\Controllers\CdpaController::class, 'requestCreate'])->name('openriccdpa.request-create'); // ACL must be checked in controller (Route::match)
    Route::match(['get', 'post'], '/requests/view', [\OpenRicCdpa\Controllers\CdpaController::class, 'requestView'])->name('openriccdpa.request-view'); // ACL must be checked in controller (Route::match)

    // DPIA CRUD
    Route::get('/dpia', [\OpenRicCdpa\Controllers\CdpaController::class, 'dpia'])->name('openriccdpa.dpia');
    Route::match(['get', 'post'], '/dpia/create', [\OpenRicCdpa\Controllers\CdpaController::class, 'dpiaCreate'])->name('openriccdpa.dpia-create'); // ACL must be checked in controller (Route::match)
    Route::match(['get', 'post'], '/dpia/view', [\OpenRicCdpa\Controllers\CdpaController::class, 'dpiaView'])->name('openriccdpa.dpia-view'); // ACL must be checked in controller (Route::match)

    // Breach notifications CRUD
    Route::get('/breaches', [\OpenRicCdpa\Controllers\CdpaController::class, 'breaches'])->name('openriccdpa.breaches');
    Route::match(['get', 'post'], '/breaches/create', [\OpenRicCdpa\Controllers\CdpaController::class, 'breachCreate'])->name('openriccdpa.breach-create'); // ACL must be checked in controller (Route::match)
    Route::match(['get', 'post'], '/breaches/view', [\OpenRicCdpa\Controllers\CdpaController::class, 'breachView'])->name('openriccdpa.breach-view'); // ACL must be checked in controller (Route::match)

    // Controller/processor licenses
    Route::get('/license', [\OpenRicCdpa\Controllers\CdpaController::class, 'license'])->name('openriccdpa.license');
    Route::match(['get', 'post'], '/license/edit', [\OpenRicCdpa\Controllers\CdpaController::class, 'licenseEdit'])->name('openriccdpa.license-edit'); // ACL must be checked in controller (Route::match)

    // Reports
    Route::get('/reports', [\OpenRicCdpa\Controllers\CdpaController::class, 'reports'])->name('openriccdpa.reports');
});
