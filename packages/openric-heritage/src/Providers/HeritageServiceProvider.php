<?php

declare(strict_types=1);

namespace OpenRiC\Heritage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Heritage\Contracts\HeritageServiceInterface;
use OpenRiC\Heritage\Services\HeritageService;

class HeritageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HeritageServiceInterface::class, HeritageService::class);
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'heritage');
    }
}
