<?php

declare(strict_types=1);

namespace OpenRiC\Triplestore\Providers;

use Illuminate\Support\ServiceProvider;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;
use OpenRiC\Triplestore\Services\FusekiTriplestoreService;

class TriplestoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            TriplestoreServiceInterface::class,
            FusekiTriplestoreService::class
        );
    }

    public function boot(): void
    {
        //
    }
}
