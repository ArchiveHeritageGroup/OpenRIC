<?php

namespace OpenRicSemanticSearch\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicSemanticSearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-semantic-search');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }
}
