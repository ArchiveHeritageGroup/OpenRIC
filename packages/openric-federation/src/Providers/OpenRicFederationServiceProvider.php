<?php

namespace OpenRic\Federation\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicFederationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'federation');
    }
}
