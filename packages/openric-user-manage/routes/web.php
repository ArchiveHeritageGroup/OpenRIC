<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\UserManage\Http\Controllers\UserManageController;

Route::get('/profile', [UserManageController::class, 'profile'])->name('user.profile');
Route::get('/', [UserManageController::class, 'browse'])->name('user.browse');
Route::get('/create', [UserManageController::class, 'create'])->name('user.add');
Route::post('/', [UserManageController::class, 'store'])->name('user.store');
Route::get('/{id}', [UserManageController::class, 'show'])->where('id', '[0-9]+')->name('user.show');
Route::get('/{id}/edit', [UserManageController::class, 'edit'])->where('id', '[0-9]+')->name('user.edit');
Route::match(['put', 'patch', 'post'], '/{id}', [UserManageController::class, 'update'])->where('id', '[0-9]+')->name('user.update');
Route::delete('/{id}', [UserManageController::class, 'destroy'])->where('id', '[0-9]+')->name('user.destroy');
Route::get('/{id}/confirm-delete', [UserManageController::class, 'confirmDelete'])->where('id', '[0-9]+')->name('user.confirmDelete');
Route::post('/{id}/deactivate', [UserManageController::class, 'deactivate'])->where('id', '[0-9]+')->name('user.deactivate');
Route::post('/{id}/reset-password', [UserManageController::class, 'resetPassword'])->where('id', '[0-9]+')->name('user.passwordReset');
Route::get('/{id}/activity', [UserManageController::class, 'activity'])->where('id', '[0-9]+')->name('user.activity');
Route::get('/{id}/acl/actor', [UserManageController::class, 'indexActorAcl'])->where('id', '[0-9]+')->name('user.indexActorAcl');
Route::match(['get', 'post'], '/{id}/acl/actor/edit', [UserManageController::class, 'editActorAcl'])->where('id', '[0-9]+')->name('user.editActorAcl');
Route::get('/{id}/acl/information-object', [UserManageController::class, 'indexInformationObjectAcl'])->where('id', '[0-9]+')->name('user.indexInformationObjectAcl');
Route::match(['get', 'post'], '/{id}/acl/information-object/edit', [UserManageController::class, 'editInformationObjectAcl'])->where('id', '[0-9]+')->name('user.editInformationObjectAcl');
Route::get('/{id}/acl/repository', [UserManageController::class, 'indexRepositoryAcl'])->where('id', '[0-9]+')->name('user.indexRepositoryAcl');
Route::match(['get', 'post'], '/{id}/acl/repository/edit', [UserManageController::class, 'editRepositoryAcl'])->where('id', '[0-9]+')->name('user.editRepositoryAcl');
Route::get('/{id}/acl/term', [UserManageController::class, 'indexTermAcl'])->where('id', '[0-9]+')->name('user.indexTermAcl');
Route::match(['get', 'post'], '/{id}/acl/term/edit', [UserManageController::class, 'editTermAcl'])->where('id', '[0-9]+')->name('user.editTermAcl');
Route::match(['get', 'post'], '/{id}/acl/researcher', [UserManageController::class, 'editResearcherAcl'])->where('id', '[0-9]+')->name('user.editResearcherAcl');
Route::get('/clipboard', [UserManageController::class, 'clipboard'])->name('user.clipboard');
Route::delete('/clipboard/{itemId}', [UserManageController::class, 'removeClipboardItem'])->name('user.clipboard.remove');
Route::post('/clipboard/clear', [UserManageController::class, 'clearClipboard'])->name('user.clipboard.clear');
Route::post('/bulk-action', [UserManageController::class, 'bulkAction'])->name('user.bulk-action');

// Legacy aliases
Route::get('/list', [UserManageController::class, 'browse'])->name('user-manage.index');
