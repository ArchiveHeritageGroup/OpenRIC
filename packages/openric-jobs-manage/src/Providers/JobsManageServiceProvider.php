<?php

declare(strict_types=1);

namespace OpenRiC\JobsManage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\JobsManage\Contracts\JobsManageServiceInterface;
use OpenRiC\JobsManage\Services\JobsManageService;

class JobsManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JobsManageServiceInterface::class, JobsManageService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'admin'])
            ->prefix('admin/jobs')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-jobs-manage');
    }
}
