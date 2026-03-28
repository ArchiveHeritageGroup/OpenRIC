<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\JobsManage\Http\Controllers\JobsManageController;

Route::get('/', [JobsManageController::class, 'index'])->name('jobs.index');
Route::get('/failed', [JobsManageController::class, 'failed'])->name('jobs.failed');
Route::post('/retry', [JobsManageController::class, 'retry'])->name('jobs.retry');
Route::post('/delete', [JobsManageController::class, 'delete'])->name('jobs.delete');
Route::post('/clear-failed', [JobsManageController::class, 'clearFailed'])->name('jobs.clearFailed');
