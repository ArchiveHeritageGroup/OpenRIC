<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\DataMigration\Http\Controllers\DataMigrationController;

Route::get('/', [DataMigrationController::class, 'index'])->name('data-migration.index');
Route::match(['get', 'post'], '/upload', [DataMigrationController::class, 'upload'])->name('data-migration.upload');
Route::get('/analyze', [DataMigrationController::class, 'analyze'])->name('data-migration.analyze');
Route::get('/mapping', [DataMigrationController::class, 'mapping'])->name('data-migration.mapping');
Route::match(['get', 'post'], '/preview', [DataMigrationController::class, 'preview'])->name('data-migration.preview');
Route::post('/execute', [DataMigrationController::class, 'execute'])->name('data-migration.execute');
Route::get('/history', [DataMigrationController::class, 'history'])->name('data-migration.history');
Route::post('/rollback', [DataMigrationController::class, 'rollback'])->name('data-migration.rollback');
