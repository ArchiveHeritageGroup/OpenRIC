<?php

declare(strict_types=1);

namespace OpenRiC\Ingest\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Ingest\Contracts\IngestServiceInterface;
use OpenRiC\Ingest\Services\IngestService;

class IngestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IngestServiceInterface::class, IngestService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'admin'])
            ->prefix('admin/ingest')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-ingest');
    }
}
