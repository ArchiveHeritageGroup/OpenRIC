<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Export\Controllers\ExportController;

Route::get('/export/formats', [ExportController::class, 'formats'])->name('export.formats');
Route::get('/export/bulk', [ExportController::class, 'bulk'])->name('export.bulk');

Route::get('/export/{iri}/jsonld', [ExportController::class, 'jsonLd'])->where('iri', '.*')->name('export.jsonld');
Route::get('/export/{iri}/turtle', [ExportController::class, 'turtle'])->where('iri', '.*')->name('export.turtle');
Route::get('/export/{iri}/rdfxml', [ExportController::class, 'rdfXml'])->where('iri', '.*')->name('export.rdfxml');
Route::get('/export/{iri}/ead3', [ExportController::class, 'ead3'])->where('iri', '.*')->name('export.ead3');
Route::get('/export/{iri}/eaccpf', [ExportController::class, 'eacCpf'])->where('iri', '.*')->name('export.eaccpf');
Route::get('/export/{iri}/dc', [ExportController::class, 'dublinCore'])->where('iri', '.*')->name('export.dc');

Route::get('/iiif/{iri}/manifest', [ExportController::class, 'iiifManifest'])->where('iri', '.*')->name('iiif.manifest');
Route::get('/iiif/{iri}/collection', [ExportController::class, 'iiifCollection'])->where('iri', '.*')->name('iiif.collection');
