<?php

declare(strict_types=1);

namespace OpenRiC\Favorites\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Favorites\Contracts\FavoritesServiceInterface;
use OpenRiC\Favorites\Services\FavoritesService;

class FavoritesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FavoritesServiceInterface::class, FavoritesService::class);
        $this->app->alias(FavoritesServiceInterface::class, FavoritesService::class);
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-favorites');
    }
}
