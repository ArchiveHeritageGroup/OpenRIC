<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OpenRiC\StaticPage\Http\Controllers\StaticPageController;

/*
|--------------------------------------------------------------------------
| Static Page Routes -- adapted from Heratio ahg-static-page/routes/web.php (24 lines)
|--------------------------------------------------------------------------
|
| Public routes for viewing static pages by slug, plus convenience aliases
| for common pages (about, privacy, terms). Admin routes require the 'admin'
| middleware for CRUD operations.
|
*/

// -- Public routes --
Route::get('/pages/{slug}', [StaticPageController::class, 'show'])
    ->name('staticpage.show');

Route::get('/about', [StaticPageController::class, 'show'])
    ->defaults('slug', 'about')
    ->name('staticpage.about');

Route::get('/privacy', [StaticPageController::class, 'show'])
    ->defaults('slug', 'privacy')
    ->name('staticpage.privacy');

Route::get('/terms', [StaticPageController::class, 'show'])
    ->defaults('slug', 'terms')
    ->name('staticpage.terms');

// Legacy URL alias (mirrors Heratio redirect)
Route::get('/admin/static-pages', fn () => redirect('/staticpage/browse', 301));

// -- Admin routes --
Route::middleware('admin')->group(function (): void {
    Route::get('/staticpage/browse', [StaticPageController::class, 'browse'])
        ->name('staticpage.browse');

    Route::get('/staticpage/list', [StaticPageController::class, 'list'])
        ->name('staticpage.list');

    Route::get('/staticpage/create', [StaticPageController::class, 'create'])
        ->name('staticpage.create');

    Route::get('/staticpage/add', [StaticPageController::class, 'create'])
        ->name('staticpage.add');

    Route::post('/staticpage/store', [StaticPageController::class, 'store'])
        ->name('staticpage.store');

    Route::get('/pages/{slug}/edit', [StaticPageController::class, 'edit'])
        ->name('staticpage.edit');

    Route::put('/pages/{slug}', [StaticPageController::class, 'update'])
        ->name('staticpage.update');

    Route::get('/pages/{slug}/delete', [StaticPageController::class, 'confirmDelete'])
        ->name('staticpage.delete');

    Route::delete('/pages/{slug}', [StaticPageController::class, 'destroy'])
        ->name('staticpage.destroy');
});
