<?php

namespace OpenRicMarketplace\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicMarketplaceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-marketplace');
    }
}
