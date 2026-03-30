<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\CustomFields\Http\Controllers\CustomFieldsController;

// JSON API routes
Route::get('/', [CustomFieldsController::class, 'index'])->name('custom-fields.index');
Route::post('/', [CustomFieldsController::class, 'store'])->name('custom-fields.store');
Route::get('/create', [CustomFieldsController::class, 'create'])->name('custom-fields.create');
Route::get('/export', [CustomFieldsController::class, 'export'])->name('custom-fields.export');
Route::post('/import', [CustomFieldsController::class, 'import'])->name('custom-fields.import');
Route::post('/reorder', [CustomFieldsController::class, 'reorder'])->name('custom-fields.reorder');
Route::get('/{id}', [CustomFieldsController::class, 'show'])->name('custom-fields.show')->whereNumber('id');
Route::get('/{id}/edit', [CustomFieldsController::class, 'edit'])->name('custom-fields.edit')->whereNumber('id');
Route::put('/{id}', [CustomFieldsController::class, 'update'])->name('custom-fields.update')->whereNumber('id');
Route::delete('/{id}', [CustomFieldsController::class, 'destroy'])->name('custom-fields.destroy')->whereNumber('id');
