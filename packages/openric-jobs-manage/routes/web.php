<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\JobsManage\Http\Controllers\JobsManageController;

Route::get('/', [JobsManageController::class, 'index'])->name('jobs.index');
Route::get('/failed', [JobsManageController::class, 'failed'])->name('jobs.failed');
Route::post('/retry', [JobsManageController::class, 'retry'])->name('jobs.retry');
Route::post('/delete', [JobsManageController::class, 'delete'])->name('jobs.delete');
Route::post('/clear-failed', [JobsManageController::class, 'clearFailed'])->name('jobs.clearFailed');
Route::get('/export-csv', [JobsManageController::class, 'exportCsv'])->name('jobs.export-csv');
Route::get('/queue-batches', [JobsManageController::class, 'queueBatches'])->name('jobs.queue-batches');
Route::get('/queue-browse', [JobsManageController::class, 'queueBrowse'])->name('jobs.queue-browse');
Route::get('/queue/{id}', [JobsManageController::class, 'queueDetail'])->name('jobs.queue-detail');
Route::get('/report', [JobsManageController::class, 'report'])->name('jobs.report');
