<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\LandingPage\Http\Controllers\LandingPageController;

Route::get('/', [LandingPageController::class, 'admin'])->name('landing-page.admin');
Route::post('/', [LandingPageController::class, 'update'])->name('landing-page.update');
