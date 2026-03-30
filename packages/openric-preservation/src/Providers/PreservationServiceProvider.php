<?php

namespace OpenRiC\Preservation\Providers;

use Illuminate\Support\ServiceProvider;

class PreservationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'preservation');
    }

    public function boot(): void
    {
        //
    }
}
