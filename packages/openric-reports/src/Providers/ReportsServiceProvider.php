<?php

declare(strict_types=1);

namespace OpenRiC\Reports\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Reports\Contracts\ReportServiceInterface;
use OpenRiC\Reports\Services\ReportService;

class ReportsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReportServiceInterface::class, ReportService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'reports');
    }
}
