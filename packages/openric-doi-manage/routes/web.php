<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\DoiManage\Http\Controllers\DoiController;

Route::get('/', [DoiController::class, 'index'])->name('doi.index');
Route::post('/mint', [DoiController::class, 'mint'])->name('doi.mint');
Route::get('/resolve/{doi}', [DoiController::class, 'resolve'])->name('doi.resolve')->where('doi', '.*');
Route::get('/settings', [DoiController::class, 'settings'])->name('doi.settings');
