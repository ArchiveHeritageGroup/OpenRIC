<?php

namespace OpenRicSecurityClearance\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicSecurityClearanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-security-clearance');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }
}
