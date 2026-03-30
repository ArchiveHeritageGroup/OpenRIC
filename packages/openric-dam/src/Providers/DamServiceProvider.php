<?php

namespace OpenRiC\Dam\Providers;

use Illuminate\Support\ServiceProvider;

class DamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'dam');
    }

    public function boot(): void
    {
        // Routes loaded via package routes file
    }
}
