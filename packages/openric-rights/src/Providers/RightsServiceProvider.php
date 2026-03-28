<?php

declare(strict_types=1);

namespace OpenRiC\Rights\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Rights\Contracts\RightsServiceInterface;
use OpenRiC\Rights\Services\RightsService;

class RightsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RightsServiceInterface::class, RightsService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'rights');
    }
}
