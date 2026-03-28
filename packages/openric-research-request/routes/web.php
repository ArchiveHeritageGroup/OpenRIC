<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\ResearchRequest\Http\Controllers\ResearchRequestController;

Route::middleware('auth')->prefix('research')->name('research.')->group(function (): void {
    Route::get('/cart', [ResearchRequestController::class, 'cart'])->name('cart');
    Route::post('/cart/add', [ResearchRequestController::class, 'add'])->name('cart.add');
    Route::post('/cart/remove/{id}', [ResearchRequestController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/submit', [ResearchRequestController::class, 'submit'])->name('cart.submit');

    // Admin request management routes
    Route::get('/requests', [ResearchRequestController::class, 'requests'])->name('requests');
    Route::get('/requests/{id}', [ResearchRequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{id}/approve', [ResearchRequestController::class, 'approve'])->name('requests.approve');
    Route::post('/requests/{id}/deny', [ResearchRequestController::class, 'deny'])->name('requests.deny');
});
