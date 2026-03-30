<?php

declare(strict_types=1);

namespace OpenRiC\DoiManage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\DoiManage\Contracts\DoiServiceInterface;
use OpenRiC\DoiManage\Services\DoiService;

/**
 * DOI management service provider -- adapted from Heratio AhgDoiManageServiceProvider.
 *
 * Registers the DoiService singleton, loads routes, views, and publishes
 * configuration for the DataCite DOI integration package.
 */
class DoiManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DoiServiceInterface::class, DoiService::class);

        $this->mergeConfigFrom(__DIR__ . '/../../config/doi.php', 'openric-doi');
    }

    public function boot(): void
    {
        // Web routes (admin panel)
        Route::middleware(['web', 'auth.required', 'admin'])
            ->group(__DIR__ . '/../../routes/web.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-doi-manage');

        // Publishable config
        $this->publishes([
            __DIR__ . '/../../config/doi.php' => config_path('openric-doi.php'),
        ], 'openric-doi-config');
    }
}
