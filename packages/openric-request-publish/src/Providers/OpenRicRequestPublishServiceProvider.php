<?php

namespace OpenRicRequestPublish\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicRequestPublishServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-request-publish');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }
}
