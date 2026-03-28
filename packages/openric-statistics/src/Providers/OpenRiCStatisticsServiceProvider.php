<?php

declare(strict_types=1);

namespace OpenRiC\Statistics\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Statistics\Contracts\StatisticsServiceInterface;
use OpenRiC\Statistics\Services\StatisticsService;

/**
 * OpenRiCStatisticsServiceProvider — registers statistics service, routes, views.
 *
 * Adapted from Heratio ahg-statistics AhgStatisticsServiceProvider.
 */
class OpenRiCStatisticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StatisticsServiceInterface::class, StatisticsService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'statistics');
    }
}
