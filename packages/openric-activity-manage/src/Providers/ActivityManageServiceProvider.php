<?php

declare(strict_types=1);

namespace OpenRiC\ActivityManage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\ActivityManage\Contracts\ActivityServiceInterface;
use OpenRiC\ActivityManage\Services\ActivityService;
use OpenRiC\ActivityManage\Contracts\MandateServiceInterface;
use OpenRiC\ActivityManage\Services\MandateService;
use OpenRiC\ActivityManage\Contracts\RiCFunctionServiceInterface;
use OpenRiC\ActivityManage\Services\RiCFunctionService;

class ActivityManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ActivityServiceInterface::class, ActivityService::class);
        $this->app->bind(MandateServiceInterface::class, MandateService::class);
        $this->app->bind(RiCFunctionServiceInterface::class, RiCFunctionService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'acl:read'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'activity-manage');
    }
}
