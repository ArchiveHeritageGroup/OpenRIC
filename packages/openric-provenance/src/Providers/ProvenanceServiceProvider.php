<?php

declare(strict_types=1);

namespace OpenRiC\Provenance\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Provenance\Contracts\CertaintyServiceInterface;
use OpenRiC\Provenance\Contracts\DescriptionHistoryServiceInterface;
use OpenRiC\Provenance\Contracts\ProvenanceServiceInterface;
use OpenRiC\Provenance\Services\CertaintyService;
use OpenRiC\Provenance\Services\DescriptionHistoryService;
use OpenRiC\Provenance\Services\ProvenanceService;

class ProvenanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProvenanceServiceInterface::class, ProvenanceService::class);
        $this->app->singleton(DescriptionHistoryServiceInterface::class, DescriptionHistoryService::class);
        $this->app->singleton(CertaintyServiceInterface::class, CertaintyService::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'provenance');
    }
}
