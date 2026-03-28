<?php

declare(strict_types=1);

namespace OpenRiC\DataMigration\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\DataMigration\Contracts\DataMigrationServiceInterface;
use OpenRiC\DataMigration\Services\DataMigrationService;

class OpenRiCDataMigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DataMigrationServiceInterface::class, DataMigrationService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('admin/data-migration')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-data-migration');

        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');
    }
}
