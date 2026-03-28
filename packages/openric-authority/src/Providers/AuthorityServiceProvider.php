<?php

declare(strict_types=1);

namespace OpenRiC\Authority\Providers;

use Illuminate\Support\ServiceProvider;
use OpenRiC\Authority\Contracts\AuthorityServiceInterface;
use OpenRiC\Authority\Services\AuthorityService;

class AuthorityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthorityServiceInterface::class, AuthorityService::class);
    }

    public function boot(): void
    {
        //
    }
}
