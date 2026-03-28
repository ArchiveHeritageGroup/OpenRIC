<?php

declare(strict_types=1);

namespace OpenRiC\Gallery\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Gallery\Contracts\GalleryServiceInterface;
use OpenRiC\Gallery\Services\GalleryService;

class OpenRiCGalleryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GalleryServiceInterface::class, GalleryService::class);
    }

    public function boot(): void
    {
        // Public routes
        Route::middleware(['web'])
            ->prefix('galleries')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-gallery');

        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');
    }
}
