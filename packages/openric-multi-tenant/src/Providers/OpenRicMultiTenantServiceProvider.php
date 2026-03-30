<?php

namespace OpenRicMultiTenant\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicMultiTenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-multi-tenant');
    }
}
