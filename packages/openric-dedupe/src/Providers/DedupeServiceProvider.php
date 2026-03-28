<?php

declare(strict_types=1);

namespace OpenRiC\Dedupe\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use OpenRiC\Dedupe\Contracts\DedupeServiceInterface;
use OpenRiC\Dedupe\Services\DedupeService;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Service provider for the openric-dedupe package.
 *
 * Adapted from Heratio AhgDedupeServiceProvider which registers
 * routes and views. OpenRiC additionally registers the service singleton
 * with dependency injection for TriplestoreServiceInterface.
 */
class DedupeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DedupeServiceInterface::class, function ($app) {
            return new DedupeService(
                $app->make(TriplestoreServiceInterface::class)
            );
        });
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-dedupe');
    }
}
