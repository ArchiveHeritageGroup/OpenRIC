<?php

use OpenRic\Cart\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::prefix('cart')->group(function () {
    Route::get('/browse', [CartController::class, 'browse'])->name('cart.browse');
    Route::get('/', [CartController::class, 'index'])->name('cart.index')->middleware('auth');
    Route::post('/add', [CartController::class, 'addItem'])->name('cart.add')->middleware('auth');
    Route::post('/remove/{itemIri}', [CartController::class, 'removeItem'])->name('cart.remove')->middleware('auth');
    Route::post('/update/{itemIri}', [CartController::class, 'updateQuantity'])->name('cart.update')->middleware('auth');
    Route::get('/checkout', [CartController::class, 'checkout'])->name('cart.checkout')->middleware('auth');
    Route::post('/payment', [CartController::class, 'processPayment'])->name('cart.payment')->middleware('auth');
    Route::get('/confirmation/{order}', [CartController::class, 'confirmation'])->name('cart.confirmation')->middleware('auth');
    Route::get('/orders', [CartController::class, 'orders'])->name('cart.orders')->middleware('auth');
});

Route::prefix('admin/cart')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/orders', [CartController::class, 'adminOrders'])->name('cart.admin.orders');
    Route::get('/settings', [CartController::class, 'adminSettings'])->name('cart.admin.settings');
});
