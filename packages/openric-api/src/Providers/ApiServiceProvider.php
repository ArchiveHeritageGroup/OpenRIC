<?php

declare(strict_types=1);

namespace OpenRic\Api\Providers;

use Illuminate\Support\ServiceProvider;
use OpenRic\Api\Contracts\ApiKeyServiceInterface;
use OpenRic\Api\Contracts\WebhookServiceInterface;
use OpenRic\Api\Middleware\ApiAuthenticate;
use OpenRic\Api\Middleware\ApiCors;
use OpenRic\Api\Middleware\ApiLogger;
use OpenRic\Api\Middleware\ApiRateLimit;
use OpenRic\Api\Services\ApiKeyService;
use OpenRic\Api\Services\WebhookService;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->singleton(ApiKeyServiceInterface::class, function ($app) {
            return new ApiKeyService(
                $app->make(TriplestoreServiceInterface::class)
            );
        });

        $this->app->singleton(WebhookServiceInterface::class, function ($app) {
            return new WebhookService(
                $app->make(TriplestoreServiceInterface::class)
            );
        });

        // Also bind concrete classes for direct access
        $this->app->singleton(ApiKeyService::class, function ($app) {
            return new ApiKeyService(
                $app->make(TriplestoreServiceInterface::class)
            );
        });

        $this->app->singleton(WebhookService::class, function ($app) {
            return new WebhookService(
                $app->make(TriplestoreServiceInterface::class)
            );
        });
    }

    public function boot(): void
    {
        // Load API routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');

        // Register API middleware aliases
        $router = $this->app['router'];
        $router->aliasMiddleware('api.auth', ApiAuthenticate::class);
        $router->aliasMiddleware('api.ratelimit', ApiRateLimit::class);
        $router->aliasMiddleware('api.log', ApiLogger::class);
        $router->aliasMiddleware('api.cors', ApiCors::class);
        
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/openric-api.php' => config_path('openric-api.php'),
        ], 'openric-api-config');
    }
}
