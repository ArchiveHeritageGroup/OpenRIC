<?php

namespace OpenRic\RadManage\Providers;

use Illuminate\Support\ServiceProvider;

class RadManageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-rad-manage');
    }
}
