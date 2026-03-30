<?php

declare(strict_types=1);

namespace OpenRiC\Favorites\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Favorites\Contracts\FavoritesServiceInterface;
use OpenRiC\Favorites\Contracts\FolderServiceInterface;
use OpenRiC\Favorites\Services\FavoritesService;
use OpenRiC\Favorites\Services\FolderService;

class FavoritesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FavoritesServiceInterface::class, FavoritesService::class);
        $this->app->alias(FavoritesServiceInterface::class, FavoritesService::class);

        $this->app->singleton(FolderServiceInterface::class, FolderService::class);
        $this->app->alias(FolderServiceInterface::class, FolderService::class);
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-favorites');
    }
}
