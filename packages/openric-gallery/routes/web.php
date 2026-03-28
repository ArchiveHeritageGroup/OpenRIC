<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Gallery\Http\Controllers\GalleryController;

// Public routes
Route::get('/', [GalleryController::class, 'index'])->name('gallery.index');
Route::get('/{slug}', [GalleryController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('gallery.show');

// Admin routes (auth required)
Route::middleware(['auth'])->prefix('/admin/galleries')->withoutMiddleware([])->group(function (): void {
    Route::get('/', [GalleryController::class, 'admin'])->name('gallery.admin');
    Route::get('/create', [GalleryController::class, 'create'])->name('gallery.admin.create');
    Route::post('/', [GalleryController::class, 'store'])->name('gallery.admin.store');
    Route::get('/{id}/edit', [GalleryController::class, 'edit'])->where('id', '[0-9]+')->name('gallery.admin.edit');
    Route::put('/{id}', [GalleryController::class, 'update'])->where('id', '[0-9]+')->name('gallery.admin.update');
    Route::delete('/{id}', [GalleryController::class, 'destroy'])->where('id', '[0-9]+')->name('gallery.admin.destroy');
    Route::get('/{id}/items', [GalleryController::class, 'items'])->where('id', '[0-9]+')->name('gallery.admin.items');
    Route::post('/{id}/items/add', [GalleryController::class, 'addItem'])->where('id', '[0-9]+')->name('gallery.admin.add-item');
    Route::post('/{id}/items/remove', [GalleryController::class, 'removeItem'])->where('id', '[0-9]+')->name('gallery.admin.remove-item');
    Route::post('/{id}/items/reorder', [GalleryController::class, 'reorder'])->where('id', '[0-9]+')->name('gallery.admin.reorder');
});
