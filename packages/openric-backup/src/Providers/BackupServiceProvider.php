<?php

declare(strict_types=1);

namespace OpenRiC\Backup\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Backup\Contracts\BackupServiceInterface;
use OpenRiC\Backup\Services\BackupService;

class BackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BackupServiceInterface::class, BackupService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'admin'])
            ->prefix('admin/backups')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-backup');
    }
}
