<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\DropdownManage\Http\Controllers\DropdownManageController;

Route::get('/', [DropdownManageController::class, 'taxonomies'])->name('dropdowns.taxonomies');
Route::get('/mappings', [DropdownManageController::class, 'mappings'])->name('dropdowns.mappings');
Route::post('/mappings', [DropdownManageController::class, 'createMapping'])->name('dropdowns.mappings.create');
Route::delete('/mappings/{id}', [DropdownManageController::class, 'destroyMapping'])->name('dropdowns.mappings.destroy');
Route::get('/{taxonomy}', [DropdownManageController::class, 'values'])->name('dropdowns.values');
Route::post('/terms', [DropdownManageController::class, 'store'])->name('dropdowns.terms.store');
Route::put('/terms/{id}', [DropdownManageController::class, 'update'])->name('dropdowns.terms.update');
Route::delete('/terms/{id}', [DropdownManageController::class, 'destroy'])->name('dropdowns.terms.destroy');
Route::post('/reorder', [DropdownManageController::class, 'reorder'])->name('dropdowns.reorder');
