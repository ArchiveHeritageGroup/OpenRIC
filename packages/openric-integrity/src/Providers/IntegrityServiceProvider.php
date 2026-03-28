<?php

declare(strict_types=1);

namespace OpenRiC\Integrity\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Integrity\Contracts\IntegrityServiceInterface;
use OpenRiC\Integrity\Services\IntegrityService;

class IntegrityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IntegrityServiceInterface::class, IntegrityService::class);
        $this->app->alias(IntegrityServiceInterface::class, IntegrityService::class);
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-integrity');
    }
}
