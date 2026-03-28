<?php

declare(strict_types=1);

namespace OpenRiC\InstantiationManage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\InstantiationManage\Contracts\InstantiationServiceInterface;
use OpenRiC\InstantiationManage\Services\InstantiationService;

class InstantiationManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InstantiationServiceInterface::class, InstantiationService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'acl:read'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'instantiation-manage');
    }
}
