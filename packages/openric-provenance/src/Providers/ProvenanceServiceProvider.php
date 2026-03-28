<?php

declare(strict_types=1);

namespace OpenRiC\Provenance\Providers;

use Illuminate\Support\ServiceProvider;
use OpenRiC\Provenance\Contracts\ProvenanceServiceInterface;
use OpenRiC\Provenance\Services\ProvenanceService;

class ProvenanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProvenanceServiceInterface::class, ProvenanceService::class);
    }

    public function boot(): void
    {
        //
    }
}
