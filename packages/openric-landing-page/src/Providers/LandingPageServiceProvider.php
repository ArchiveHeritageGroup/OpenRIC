<?php

declare(strict_types=1);

namespace OpenRiC\LandingPage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\LandingPage\Contracts\LandingPageServiceInterface;
use OpenRiC\LandingPage\Http\Controllers\LandingPageController;
use OpenRiC\LandingPage\Services\LandingPageService;

class LandingPageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LandingPageServiceInterface::class, LandingPageService::class);
    }

    public function boot(): void
    {
        // Public landing page route (slug-based or default)
        Route::middleware('web')->group(function (): void {
            Route::get('/landing/{slug?}', [LandingPageController::class, 'index'])
                ->name('landing-page.show');
        });

        // Authenticated user dashboard routes
        Route::middleware(['web', 'auth'])->prefix('landing-page')->group(function (): void {
            Route::get('/my-dashboard', [LandingPageController::class, 'myDashboard'])
                ->name('landing-page.myDashboard');
            Route::get('/my-dashboard/list', [LandingPageController::class, 'myDashboardList'])
                ->name('landing-page.myDashboard.list');
            Route::match(['get', 'post'], '/my-dashboard/create', [LandingPageController::class, 'myDashboardCreate'])
                ->name('landing-page.myDashboard.create');
        });

        // Admin routes
        Route::middleware(['web', 'auth', 'admin'])->prefix('landing-page/admin')->group(function (): void {
            Route::get('/', [LandingPageController::class, 'list'])
                ->name('landing-page.list');
            Route::match(['get', 'post'], '/create', [LandingPageController::class, 'create'])
                ->name('landing-page.create');
            Route::get('/{id}/edit', [LandingPageController::class, 'edit'])
                ->name('landing-page.edit')->where('id', '[0-9]+');
            Route::post('/post', [LandingPageController::class, 'post'])
                ->name('landing-page.post');

            // AJAX endpoints for page settings and versioning
            Route::post('/{id}/settings', [LandingPageController::class, 'updateSettings'])
                ->name('landing-page.updateSettings')->where('id', '[0-9]+');
            Route::post('/{id}/delete', [LandingPageController::class, 'deletePage'])
                ->name('landing-page.delete')->where('id', '[0-9]+');
            Route::post('/{id}/version', [LandingPageController::class, 'saveVersion'])
                ->name('landing-page.saveVersion')->where('id', '[0-9]+');

            // AJAX endpoints for block management
            Route::post('/block/add', [LandingPageController::class, 'addBlock'])
                ->name('landing-page.block.add');
            Route::post('/block/{blockId}/update', [LandingPageController::class, 'updateBlock'])
                ->name('landing-page.block.update')->where('blockId', '[0-9]+');
            Route::post('/block/{blockId}/delete', [LandingPageController::class, 'deleteBlock'])
                ->name('landing-page.block.delete')->where('blockId', '[0-9]+');
            Route::post('/blocks/reorder', [LandingPageController::class, 'reorderBlocks'])
                ->name('landing-page.blocks.reorder');
            Route::post('/block/{blockId}/duplicate', [LandingPageController::class, 'duplicateBlock'])
                ->name('landing-page.block.duplicate')->where('blockId', '[0-9]+');
            Route::post('/block/{blockId}/toggle-visibility', [LandingPageController::class, 'toggleVisibility'])
                ->name('landing-page.block.toggleVisibility')->where('blockId', '[0-9]+');
        });

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-landing-page');
    }
}
