<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\Gallery\Http\Controllers\GalleryController;

// =========================================================================
// Gallery Collections (curated entity groups) — public routes
// =========================================================================
Route::get('/', [GalleryController::class, 'index'])->name('gallery.index');
Route::get('/{slug}', [GalleryController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('gallery.show');

// =========================================================================
// Gallery Collections — admin routes
// =========================================================================
Route::middleware(['auth'])->prefix('/admin/galleries')->group(function (): void {
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

// =========================================================================
// Artwork CCO Cataloguing — public routes
// =========================================================================
Route::get('/gallery/browse', [GalleryController::class, 'browseArtworks'])->name('gallery.artwork.browse');
Route::get('/gallery/artists', [GalleryController::class, 'artists'])->name('gallery.artists');
Route::get('/gallery/artists/{id}', [GalleryController::class, 'showArtist'])->name('gallery.artists.show')->where('id', '[0-9]+');

// =========================================================================
// Artwork CCO Cataloguing — authenticated routes
// =========================================================================
Route::middleware('auth')->group(function (): void {
    // Dashboard & index
    Route::get('/gallery/dashboard', [GalleryController::class, 'dashboard'])->name('gallery.dashboard');
    Route::get('/gallery/index', [GalleryController::class, 'galleryIndex'])->name('gallery.gallery-index');

    // Loans
    Route::get('/gallery/loans', [GalleryController::class, 'loans'])->name('gallery.loans');
    Route::get('/gallery/loans/{id}', [GalleryController::class, 'showLoan'])->name('gallery.loans.show')->where('id', '[0-9]+');
    Route::get('/gallery/loans/create', [GalleryController::class, 'createLoan'])->name('gallery.loans.create');
    Route::post('/gallery/loans/store', [GalleryController::class, 'storeLoan'])->name('gallery.loans.store');

    // Valuations
    Route::get('/gallery/valuations', [GalleryController::class, 'valuations'])->name('gallery.valuations');
    Route::get('/gallery/valuations/{id}', [GalleryController::class, 'showValuation'])->name('gallery.valuations.show')->where('id', '[0-9]+');
    Route::get('/gallery/valuations/create', [GalleryController::class, 'createValuation'])->name('gallery.valuations.create');
    Route::post('/gallery/valuations/store', [GalleryController::class, 'storeValuation'])->name('gallery.valuations.store');

    // Venues
    Route::get('/gallery/venues', [GalleryController::class, 'venues'])->name('gallery.venues');
    Route::get('/gallery/venues/{id}', [GalleryController::class, 'showVenue'])->name('gallery.venues.show')->where('id', '[0-9]+');
    Route::get('/gallery/venues/create', [GalleryController::class, 'createVenue'])->name('gallery.venues.create');
    Route::post('/gallery/venues/store', [GalleryController::class, 'storeVenue'])->name('gallery.venues.store');

    // Facility reports
    Route::get('/gallery/facility-report/{id}', [GalleryController::class, 'facilityReport'])->name('gallery.facility-report')->where('id', '[0-9]+');

    // Gallery Reports
    Route::get('/gallery-reports', [GalleryController::class, 'reportsIndex'])->name('gallery-reports.index');
    Route::get('/gallery-reports/exhibitions', [GalleryController::class, 'reportsExhibitions'])->name('gallery-reports.exhibitions');
    Route::get('/gallery-reports/facility-reports', [GalleryController::class, 'reportsFacilityReports'])->name('gallery-reports.facility-reports');
    Route::get('/gallery-reports/loans', [GalleryController::class, 'reportsLoans'])->name('gallery-reports.loans');
    Route::get('/gallery-reports/spaces', [GalleryController::class, 'reportsSpaces'])->name('gallery-reports.spaces');
    Route::get('/gallery-reports/valuations', [GalleryController::class, 'reportsValuations'])->name('gallery-reports.valuations');

    // Artwork CRUD
    Route::get('/gallery/add', [GalleryController::class, 'createArtwork'])->name('gallery.artwork.create');
    Route::post('/gallery/store', [GalleryController::class, 'storeArtwork'])->name('gallery.artwork.store');
    Route::get('/gallery/artists/create', [GalleryController::class, 'createArtist'])->name('gallery.artists.create');
    Route::post('/gallery/artists/store', [GalleryController::class, 'storeArtist'])->name('gallery.artists.store');
    Route::get('/gallery/{slug}/edit', [GalleryController::class, 'editArtwork'])->name('gallery.artwork.edit')->where('slug', '[a-z0-9\-]+');
    Route::put('/gallery/{slug}', [GalleryController::class, 'updateArtwork'])->name('gallery.artwork.update')->where('slug', '[a-z0-9\-]+');
    Route::post('/gallery/{slug}/delete', [GalleryController::class, 'destroyArtwork'])->name('gallery.artwork.destroy')->where('slug', '[a-z0-9\-]+');
});

// Slug catch-all for artworks (must be last)
Route::get('/gallery/{slug}', [GalleryController::class, 'showArtwork'])->name('gallery.artwork.show')->where('slug', '[a-z0-9\-]+');
