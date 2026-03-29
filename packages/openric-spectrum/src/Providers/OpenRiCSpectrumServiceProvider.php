<?php

declare(strict_types=1);

namespace OpenRiC\Spectrum\Providers;

use Illuminate\Support\ServiceProvider;
use OpenRiC\Spectrum\Contracts\SpectrumServiceInterface;
use OpenRiC\Spectrum\Services\SpectrumService;

/**
 * Service provider for the OpenRiC Spectrum package.
 *
 * Registers the SpectrumService singleton, loads routes and views.
 * Adapted from Heratio AhgSpectrumServiceProvider.
 */
class OpenRiCSpectrumServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SpectrumServiceInterface::class, SpectrumService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'spectrum');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/spectrum'),
            ], 'spectrum-views');
        }
    }
}
