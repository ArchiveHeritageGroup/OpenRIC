<?php

namespace OpenRicRic\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicRicServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-ric');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }
}
