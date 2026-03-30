<?php

declare(strict_types=1);

namespace OpenRic\AccessRequest\Providers;

use Illuminate\Support\ServiceProvider;
use OpenRic\AccessRequest\Contracts\AccessRequestServiceInterface;
use OpenRic\AccessRequest\Services\AccessRequestService;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class AccessRequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the interface to the implementation
        $this->app->singleton(AccessRequestServiceInterface::class, function ($app) {
            return new AccessRequestService(
                $app->make(TriplestoreServiceInterface::class)
            );
        });

        // Also bind the concrete class for direct access
        $this->app->singleton(AccessRequestService::class, function ($app) {
            return new AccessRequestService(
                $app->make(TriplestoreServiceInterface::class)
            );
        });
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-access-request');
        
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/openric-access-request.php' => config_path('openric-access-request.php'),
        ], 'openric-access-request-config');
    }
}
