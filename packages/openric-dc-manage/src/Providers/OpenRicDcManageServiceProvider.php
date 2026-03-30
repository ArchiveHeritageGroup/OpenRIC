<?php

namespace OpenRic\DcManage\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicDcManageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'dc-manage');
    }
}
