<?php

declare(strict_types=1);

namespace OpenRiC\Export\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Export\Contracts\ExportServiceInterface;
use OpenRiC\Export\Contracts\IiifServiceInterface;
use OpenRiC\Export\Services\ExportService;
use OpenRiC\Export\Services\IiifService;

/**
 * Service provider for the openric-export package.
 *
 * Registers ExportServiceInterface and IiifServiceInterface bindings and
 * loads public (no auth) routes for linked data export and IIIF endpoints.
 */
class ExportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExportServiceInterface::class, ExportService::class);
        $this->app->bind(IiifServiceInterface::class, IiifService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web'])
            ->group(__DIR__ . '/../../routes/web.php');
    }
}
