<?php

namespace OpenRic\ExtendedRights\Providers;

use Illuminate\Support\ServiceProvider;

class ExtendedRightsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\OpenRic\ExtendedRights\Services\ExtendedRightsService::class);
        $this->app->singleton(\OpenRic\ExtendedRights\Services\EmbargoService::class);
        $this->app->singleton(\OpenRic\ExtendedRights\Services\EmbargoNotificationService::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                \OpenRic\ExtendedRights\Commands\EmbargoProcessCommand::class,
                \OpenRic\ExtendedRights\Commands\EmbargoReportCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-extended-rights');
    }
}
