<?php

declare(strict_types=1);

namespace OpenRiC\Label\Providers;

use Illuminate\Support\ServiceProvider;
use OpenRiC\Label\Contracts\LabelServiceInterface;
use OpenRiC\Label\Services\LabelService;

/**
 * Label service provider — adapted from Heratio AhgLabelServiceProvider.
 *
 * Heratio's provider simply loads routes and views. OpenRiC additionally:
 *   - Binds LabelServiceInterface → LabelService (IoC container)
 *   - Uses 'openric-label' view namespace (Heratio uses 'label')
 *   - Routes are authenticated via 'web' + 'auth' middleware
 */
class OpenRiCLabelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LabelServiceInterface::class, LabelService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-label');
    }
}
