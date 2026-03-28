<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\RecordManage\Contracts\HierarchyServiceInterface;
use OpenRiC\RecordManage\Contracts\RecordPartServiceInterface;
use OpenRiC\RecordManage\Contracts\RecordServiceInterface;
use OpenRiC\RecordManage\Contracts\RecordSetServiceInterface;
use OpenRiC\RecordManage\Services\HierarchyService;
use OpenRiC\RecordManage\Services\RecordPartService;
use OpenRiC\RecordManage\Services\RecordService;
use OpenRiC\RecordManage\Services\RecordSetService;

class RecordManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RecordSetServiceInterface::class, RecordSetService::class);
        $this->app->bind(RecordServiceInterface::class, RecordService::class);
        $this->app->bind(RecordPartServiceInterface::class, RecordPartService::class);
        $this->app->bind(HierarchyServiceInterface::class, HierarchyService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'acl:read'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'record-manage');
    }
}
