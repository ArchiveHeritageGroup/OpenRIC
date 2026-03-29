<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Backup\Http\Controllers\BackupController;

/*
|--------------------------------------------------------------------------
| Backup & Restore Routes
|--------------------------------------------------------------------------
| Adapted from Heratio ahg-backup/routes/web.php.
| All routes require admin middleware (applied by the service provider).
*/

// Dashboard
Route::get('/admin/backups', [BackupController::class, 'index'])->name('backups.index');

// Create (AJAX)
Route::post('/admin/backups/create', [BackupController::class, 'create'])->name('backups.create');

// Settings
Route::get('/admin/backups/settings', [BackupController::class, 'settingsForm'])->name('backups.settings');
Route::post('/admin/backups/settings', [BackupController::class, 'saveSettings'])->name('backups.saveSettings');

// Restore
Route::get('/admin/backups/restore', [BackupController::class, 'restoreForm'])->name('backups.restore');
Route::post('/admin/backups/restore', [BackupController::class, 'doRestore'])->name('backups.doRestore');

// Upload
Route::get('/admin/backups/upload', [BackupController::class, 'uploadForm'])->name('backups.upload');
Route::post('/admin/backups/upload', [BackupController::class, 'doUpload'])->name('backups.doUpload');

// Download
Route::get('/admin/backups/{id}/download', [BackupController::class, 'download'])->name('backups.download')->whereNumber('id');

// Delete (AJAX)
Route::delete('/admin/backups/{id}', [BackupController::class, 'destroy'])->name('backups.destroy')->whereNumber('id');

// Connection tests (AJAX)
Route::post('/admin/backups/test-database', [BackupController::class, 'testDatabase'])->name('backups.testDatabase');
Route::post('/admin/backups/test-triplestore', [BackupController::class, 'testTriplestore'])->name('backups.testTriplestore');
