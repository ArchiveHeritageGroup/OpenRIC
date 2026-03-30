<?php

namespace OpenRicNaz\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicNazServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'naz');
    }
}
