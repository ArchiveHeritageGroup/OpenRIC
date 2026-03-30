<?php

namespace OpenRicOai\Providers;

use Illuminate\Support\ServiceProvider;

class OpenRicOaiServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        \Illuminate\Support\Facades\Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');
    }
}
