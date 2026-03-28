<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Help\Http\Controllers\HelpController;

Route::prefix('help')->name('help.')->group(function (): void {
    Route::get('/', [HelpController::class, 'index'])->name('index');
    Route::get('/search', [HelpController::class, 'search'])->name('search');
    Route::get('/{slug}', [HelpController::class, 'show'])->name('show');
});
