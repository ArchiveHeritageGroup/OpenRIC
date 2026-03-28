<?php

declare(strict_types=1);

namespace OpenRiC\UserManage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\UserManage\Contracts\UserManageServiceInterface;
use OpenRiC\UserManage\Services\UserManageService;

class OpenRiCUserManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserManageServiceInterface::class, UserManageService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('admin/users')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-user-manage');
    }
}
