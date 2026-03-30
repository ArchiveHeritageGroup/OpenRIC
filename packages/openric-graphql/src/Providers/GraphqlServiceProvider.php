<?php

namespace OpenRic\Graphql\Providers;

use Illuminate\Support\ServiceProvider;

class GraphqlServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'graphql');
    }
}
