<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\CustomFields\Http\Controllers\CustomFieldsController;

Route::get('/', [CustomFieldsController::class, 'index'])->name('custom-fields.index');
Route::post('/', [CustomFieldsController::class, 'store'])->name('custom-fields.store');
Route::get('/{id}', [CustomFieldsController::class, 'show'])->name('custom-fields.show')->whereNumber('id');
Route::put('/{id}', [CustomFieldsController::class, 'update'])->name('custom-fields.update')->whereNumber('id');
Route::delete('/{id}', [CustomFieldsController::class, 'destroy'])->name('custom-fields.destroy')->whereNumber('id');
