<?php

namespace OpenRic\Forms\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicFormsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'forms');
    }
}
