<?php

declare(strict_types=1);

namespace OpenRic\Cart\Providers;

use Illuminate\Support\ServiceProvider;
use OpenRic\Cart\Contracts\CartServiceInterface;
use OpenRic\Cart\Services\CartService;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the interface to the implementation
        $this->app->singleton(CartServiceInterface::class, function ($app) {
            return new CartService(
                $app->make(TriplestoreServiceInterface::class)
            );
        });

        // Also bind the concrete class for direct access
        $this->app->singleton(CartService::class, function ($app) {
            return new CartService(
                $app->make(TriplestoreServiceInterface::class)
            );
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-cart');
        
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/openric-cart.php' => config_path('openric-cart.php'),
        ], 'openric-cart-config');
    }
}
