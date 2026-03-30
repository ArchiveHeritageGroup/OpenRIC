<?php

namespace OpenRicDacsManage\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicDacsManageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-dacs-manage');
    }
}
