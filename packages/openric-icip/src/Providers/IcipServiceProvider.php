<?php

namespace OpenRic\Icip\Providers;

use Illuminate\Support\ServiceProvider;

class IcipServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'icip');
    }
}
