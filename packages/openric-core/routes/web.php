<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Core\Controllers\MappingController;
use OpenRiC\Core\Controllers\RelationshipController;

Route::middleware(['web', 'auth.required', 'acl:read'])->prefix('relationships')->group(function () {
    Route::get('/', [RelationshipController::class, 'index'])->name('relationships.index');
    Route::post('/', [RelationshipController::class, 'store'])->name('relationships.store');
    Route::delete('/', [RelationshipController::class, 'destroy'])->name('relationships.destroy');
});

Route::middleware(['web', 'auth.required'])->get('/admin/mappings', [MappingController::class, 'index'])->name('admin.mappings');
