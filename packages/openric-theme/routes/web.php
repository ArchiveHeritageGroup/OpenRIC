<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Theme\Controllers\HomeController;

Route::middleware('web')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
});
