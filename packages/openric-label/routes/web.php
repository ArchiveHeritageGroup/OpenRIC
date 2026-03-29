<?php

declare(strict_types=1);

/**
 * Label routes — adapted from Heratio ahg-label routes/web.php.
 *
 * Heratio routes:
 *   GET  /label/{slug}        → LabelController@index   (ahglabel.index)
 *   POST /label/generate      → LabelController@generate (ahglabel.generate)
 *   POST /label/batch-print   → LabelController@batchPrint (ahglabel.batch)
 *
 * OpenRiC routes use IRI-based routing instead of slug-based:
 *   GET  /label/{iri}         → LabelController@index   (openric.label.index)
 *   POST /label/generate      → LabelController@generate (openric.label.generate)
 *   POST /label/batch-print   → LabelController@batchPrint (openric.label.batch)
 *
 * The {iri} parameter is base64-encoded to avoid URL routing conflicts with IRI slashes.
 */

use Illuminate\Support\Facades\Route;
use OpenRiC\Label\Http\Controllers\LabelController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/label/{iri}', [LabelController::class, 'index'])
        ->where('iri', '[A-Za-z0-9_\-+/=]+')
        ->name('openric.label.index');

    Route::post('/label/generate', [LabelController::class, 'generate'])
        ->name('openric.label.generate');

    Route::post('/label/batch-print', [LabelController::class, 'batchPrint'])
        ->name('openric.label.batch');
});
