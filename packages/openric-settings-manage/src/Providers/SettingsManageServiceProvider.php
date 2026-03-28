<?php

declare(strict_types=1);

namespace OpenRiC\SettingsManage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\SettingsManage\Contracts\SettingsManageServiceInterface;
use OpenRiC\SettingsManage\Services\SettingsManageService;

class SettingsManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsManageServiceInterface::class, SettingsManageService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'settings-manage');
    }
}
