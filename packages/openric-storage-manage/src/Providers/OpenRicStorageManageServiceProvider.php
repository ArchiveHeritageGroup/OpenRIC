<?php

namespace OpenRicStorageManage\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicStorageManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-storage-manage');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }
}
