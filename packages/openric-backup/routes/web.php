<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Backup\Http\Controllers\BackupController;

Route::get('/', [BackupController::class, 'index'])->name('backups.index');
Route::post('/', [BackupController::class, 'create'])->name('backups.create');
Route::get('/{id}/download', [BackupController::class, 'download'])->name('backups.download')->whereNumber('id');
Route::delete('/{id}', [BackupController::class, 'delete'])->name('backups.delete')->whereNumber('id');
Route::get('/schedule', [BackupController::class, 'schedule'])->name('backups.schedule');
