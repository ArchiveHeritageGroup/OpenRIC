<?php

namespace OpenRic3dModel\Providers;

use OpenRic3dModel\Services\ThreeDThumbnailService;
use Illuminate\Support\ServiceProvider;

class OpenRic3dModelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThreeDThumbnailService::class, function () {
            return new ThreeDThumbnailService();
        });
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-3d-model');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \OpenRic3dModel\Commands\ThreeDDerivativesCommand::class,
                \OpenRic3dModel\Commands\ThreeDMultiangleCommand::class,
                \OpenRic3dModel\Commands\TriposrGenerateCommand::class,
                \OpenRic3dModel\Commands\TriposrHealthCommand::class,
                \OpenRic3dModel\Commands\TriposrPreloadCommand::class,
            ]);
        }
    }
}
