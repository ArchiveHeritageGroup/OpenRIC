<?php

namespace OpenRic\Discovery\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicDiscoveryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'discovery');
    }
}
