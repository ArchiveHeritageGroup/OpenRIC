<?php

declare(strict_types=1);

namespace OpenRiC\Repository\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Repository\Contracts\RepositoryServiceInterface;
use OpenRiC\Repository\Services\RepositoryService;

/**
 * Service provider for the openric-repository package.
 *
 * Adapted from Heratio AhgRepositoryManageServiceProvider.
 * Registers the interface binding, routes, and views.
 */
class OpenRiCRepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RepositoryServiceInterface::class, RepositoryService::class);
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'repository');
    }
}
