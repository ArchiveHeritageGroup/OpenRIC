<?php

declare(strict_types=1);

namespace OpenRiC\Display\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Display\Contracts\DisplayServiceInterface;
use OpenRiC\Display\Services\DisplayService;

/**
 * Display package service provider.
 *
 * Adapted from Heratio AhgDisplayServiceProvider.
 * Registers the DisplayServiceInterface binding, loads routes and views.
 */
class OpenRiCDisplayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DisplayServiceInterface::class, DisplayService::class);
    }

    public function boot(): void
    {
        // Routes: public browse + admin management
        Route::middleware(['web'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-display');
    }
}
