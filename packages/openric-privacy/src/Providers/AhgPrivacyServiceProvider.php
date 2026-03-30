<?php

namespace OpenRic\Privacy\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRic\PrivacyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'privacy');
    }
}
