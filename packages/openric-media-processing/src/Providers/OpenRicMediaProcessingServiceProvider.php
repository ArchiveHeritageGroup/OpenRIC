<?php

namespace OpenRicMediaProcessing\Providers;

use OpenRicMediaProcessing\Services\DerivativeService;
use OpenRicMediaProcessing\Services\WatermarkService;
use Illuminate\Support\ServiceProvider;

class OpenRicMediaProcessingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DerivativeService::class);
        $this->app->singleton(WatermarkService::class);
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-media-processing');
    }
}
