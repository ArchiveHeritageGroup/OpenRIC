<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Donor\Controllers\DonorController;

/*
|--------------------------------------------------------------------------
| Donor Routes
|--------------------------------------------------------------------------
|
| Adapted from Heratio donor routes. Uses integer IDs instead of slugs.
| All routes require authentication (middleware applied in ServiceProvider).
|
*/

Route::prefix('donors')->name('donors.')->group(function () {
    Route::get('/', [DonorController::class, 'index'])->name('index');
    Route::get('/create', [DonorController::class, 'create'])->name('create');
    Route::post('/', [DonorController::class, 'store'])->name('store');
    Route::get('/{id}', [DonorController::class, 'show'])->name('show')->whereNumber('id');
    Route::get('/{id}/edit', [DonorController::class, 'edit'])->name('edit')->whereNumber('id');
    Route::put('/{id}', [DonorController::class, 'update'])->name('update')->whereNumber('id');
    Route::delete('/{id}', [DonorController::class, 'destroy'])->name('destroy')->whereNumber('id');
});
