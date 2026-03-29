<?php

declare(strict_types=1);

namespace OpenRiC\Backup\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Backup\Contracts\BackupServiceInterface;
use OpenRiC\Backup\Services\BackupService;

/**
 * Service provider -- adapted from Heratio AhgBackup\Providers\AhgBackupServiceProvider.
 */
class BackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BackupServiceInterface::class, BackupService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'admin'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-backup');
    }
}
