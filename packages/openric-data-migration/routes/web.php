<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\DataMigration\Http\Controllers\DataMigrationController;

/*
|--------------------------------------------------------------------------
| Data Migration Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio ahg-data-migration routes (146 lines).
| All routes require authentication (middleware applied in ServiceProvider).
|
*/

// ── Main admin UI pages — adapted from Heratio ─────────────────────────
Route::get('/', [DataMigrationController::class, 'index'])->name('data-migration.index');

Route::match(['get', 'post'], '/upload', [DataMigrationController::class, 'upload'])->name('data-migration.upload');

Route::get('/map', [DataMigrationController::class, 'map'])->name('data-migration.map');

Route::post('/save-mapping', [DataMigrationController::class, 'saveMapping'])->name('data-migration.save-mapping');

Route::post('/delete-mapping/{id}', [DataMigrationController::class, 'deleteMapping'])->name('data-migration.delete-mapping')
    ->whereNumber('id');

Route::match(['get', 'post'], '/preview', [DataMigrationController::class, 'preview'])->name('data-migration.preview');

Route::post('/execute', [DataMigrationController::class, 'execute'])->name('data-migration.execute');

Route::get('/jobs', [DataMigrationController::class, 'jobs'])->name('data-migration.jobs');

Route::get('/job/{id}', [DataMigrationController::class, 'jobStatus'])->name('data-migration.job')
    ->whereNumber('id');

Route::get('/batch-export', [DataMigrationController::class, 'batchExport'])->name('data-migration.batch-export');

Route::match(['get', 'post'], '/export', [DataMigrationController::class, 'export'])->name('data-migration.export');

Route::get('/import-results', [DataMigrationController::class, 'importResults'])->name('data-migration.import-results');

Route::get('/download', [DataMigrationController::class, 'download'])->name('data-migration.download');

Route::get('/mapping', [DataMigrationController::class, 'getMapping'])->name('data-migration.get-mapping');

Route::get('/export-mapping/{id}', [DataMigrationController::class, 'exportMapping'])->name('data-migration.export-mapping')
    ->whereNumber('id');

Route::post('/import-mapping', [DataMigrationController::class, 'importMapping'])->name('data-migration.import-mapping');

// ── Preservica — adapted from Heratio ───────────────────────────────────
Route::match(['get', 'post'], '/preservica/import', [DataMigrationController::class, 'preservicaImport'])->name('data-migration.preservica-import');

Route::match(['get', 'post'], '/preservica/export', [DataMigrationController::class, 'preservicaExport'])->name('data-migration.preservica-export');

Route::match(['get', 'post'], '/preservica/export/{id}', [DataMigrationController::class, 'preservicaExport'])->name('data-migration.preservica-export-id')
    ->whereNumber('id');

// ── AJAX routes — adapted from Heratio camelCase routes ─────────────────
Route::get('/job-progress', [DataMigrationController::class, 'jobProgress'])->name('data-migration.job-progress');

Route::post('/queue-job', [DataMigrationController::class, 'queueJob'])->name('data-migration.queue-job');

Route::post('/cancel-job', [DataMigrationController::class, 'cancelJob'])->name('data-migration.cancel-job');

Route::get('/export-csv', [DataMigrationController::class, 'exportCsv'])->name('data-migration.export-csv');

Route::get('/load-mapping', [DataMigrationController::class, 'loadMapping'])->name('data-migration.load-mapping');

Route::post('/preview-validation', [DataMigrationController::class, 'previewValidation'])->name('data-migration.preview-validation');

Route::post('/validate', [DataMigrationController::class, 'validateFile'])->name('data-migration.validate');

Route::post('/rollback', [DataMigrationController::class, 'rollback'])->name('data-migration.rollback');

Route::get('/history', [DataMigrationController::class, 'history'])->name('data-migration.history');

// ── Misc views — adapted from Heratio ───────────────────────────────────
Route::get('/preview-data', [DataMigrationController::class, 'previewData'])->name('data-migration.preview-data');

Route::match(['get', 'post'], '/sector-export', [DataMigrationController::class, 'sectorExport'])->name('data-migration.sector-export');
