<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\UserManage\Http\Controllers\UserManageController;

Route::get('/', [UserManageController::class, 'index'])->name('user-manage.index');
Route::get('/create', [UserManageController::class, 'create'])->name('user-manage.create');
Route::post('/', [UserManageController::class, 'store'])->name('user-manage.store');
Route::get('/{id}', [UserManageController::class, 'show'])->where('id', '[0-9]+')->name('user-manage.show');
Route::get('/{id}/edit', [UserManageController::class, 'edit'])->where('id', '[0-9]+')->name('user-manage.edit');
Route::put('/{id}', [UserManageController::class, 'update'])->where('id', '[0-9]+')->name('user-manage.update');
Route::post('/{id}/deactivate', [UserManageController::class, 'deactivate'])->where('id', '[0-9]+')->name('user-manage.deactivate');
Route::post('/{id}/reset-password', [UserManageController::class, 'resetPassword'])->where('id', '[0-9]+')->name('user-manage.reset-password');
Route::get('/{id}/activity', [UserManageController::class, 'activity'])->where('id', '[0-9]+')->name('user-manage.activity');
Route::post('/bulk-action', [UserManageController::class, 'bulkAction'])->name('user-manage.bulk-action');
