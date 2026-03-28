<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Export\Controllers\ExportController;

/*
|--------------------------------------------------------------------------
| Export & IIIF Routes (Public — No Auth)
|--------------------------------------------------------------------------
|
| All export routes are public to support linked data consumers, OAI-PMH
| harvesters, and IIIF viewers. CORS headers are set in the controller.
|
*/

// Available formats listing
Route::get('/export/formats', [ExportController::class, 'formats'])
    ->name('export.formats');

// Bulk export (must come before {iri} routes to avoid capture)
Route::get('/export/bulk', [ExportController::class, 'bulk'])
    ->name('export.bulk');

// Single entity exports — the {iri} parameter is URL-encoded
Route::get('/export/{iri}/jsonld', [ExportController::class, 'jsonLd'])
    ->where('iri', '.*')
    ->name('export.jsonld');

Route::get('/export/{iri}/turtle', [ExportController::class, 'turtle'])
    ->where('iri', '.*')
    ->name('export.turtle');

Route::get('/export/{iri}/rdfxml', [ExportController::class, 'rdfXml'])
    ->where('iri', '.*')
    ->name('export.rdfxml');

Route::get('/export/{iri}/ead3', [ExportController::class, 'ead3'])
    ->where('iri', '.*')
    ->name('export.ead3');

Route::get('/export/{iri}/eaccpf', [ExportController::class, 'eacCpf'])
    ->where('iri', '.*')
    ->name('export.eaccpf');

Route::get('/export/{iri}/dc', [ExportController::class, 'dublinCore'])
    ->where('iri', '.*')
    ->name('export.dc');

// IIIF Presentation API 3.0 endpoints
Route::get('/iiif/{iri}/manifest', [ExportController::class, 'iiifManifest'])
    ->where('iri', '.*')
    ->name('iiif.manifest');

Route::get('/iiif/{iri}/collection', [ExportController::class, 'iiifCollection'])
    ->where('iri', '.*')
    ->name('iiif.collection');
