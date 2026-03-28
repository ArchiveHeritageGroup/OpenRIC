<?php

declare(strict_types=1);

namespace OpenRiC\ResearchRequest\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\ResearchRequest\Contracts\ResearchRequestServiceInterface;
use OpenRiC\ResearchRequest\Services\ResearchRequestService;

class ResearchRequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ResearchRequestServiceInterface::class, ResearchRequestService::class);
        $this->app->alias(ResearchRequestServiceInterface::class, ResearchRequestService::class);
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-research-request');
    }
}
