<?php

declare(strict_types=1);

namespace OpenRiC\Workflow\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Workflow\Contracts\WorkflowServiceInterface;
use OpenRiC\Workflow\Services\WorkflowService;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorkflowServiceInterface::class, WorkflowService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required'])
            ->prefix('workflow')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-workflow');
    }
}
