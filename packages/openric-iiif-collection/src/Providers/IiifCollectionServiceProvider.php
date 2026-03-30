<?php

namespace OpenRiC\IiifCollection\Providers;

use Illuminate\Support\ServiceProvider;

class IiifCollectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'iiif-collection');
    }

    public function boot(): void
    {
        // Routes loaded via package routes file
    }
}
