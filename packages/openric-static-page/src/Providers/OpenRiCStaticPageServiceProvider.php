<?php

declare(strict_types=1);

namespace OpenRiC\StaticPage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\StaticPage\Contracts\StaticPageServiceInterface;
use OpenRiC\StaticPage\Services\StaticPageService;

/**
 * Static page service provider -- adapted from Heratio AhgStaticPage\Providers\AhgStaticPageServiceProvider (20 lines).
 *
 * Registers the StaticPageService singleton, loads routes with web middleware,
 * loads views from the package resources directory, and publishes the migration.
 */
class OpenRiCStaticPageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StaticPageServiceInterface::class, StaticPageService::class);
        $this->app->alias(StaticPageServiceInterface::class, StaticPageService::class);
    }

    public function boot(): void
    {
        // Routes
        Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-static-page');

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Publishable migration (for host app)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'openric-static-page-migrations');
        }
    }
}
