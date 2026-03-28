<?php

declare(strict_types=1);

namespace OpenRiC\DoiManage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\DoiManage\Contracts\DoiServiceInterface;
use OpenRiC\DoiManage\Services\DoiService;

class DoiManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DoiServiceInterface::class, DoiService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'admin'])
            ->prefix('admin/doi')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-doi-manage');
    }
}
