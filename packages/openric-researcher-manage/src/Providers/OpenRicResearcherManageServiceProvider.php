<?php

namespace OpenRicResearcherManage\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicResearcherManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-researcher-manage');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }
}
