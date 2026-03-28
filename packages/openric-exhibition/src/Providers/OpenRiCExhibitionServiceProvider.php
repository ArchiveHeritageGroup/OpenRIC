<?php

declare(strict_types=1);

namespace OpenRiC\Exhibition\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Exhibition\Contracts\ExhibitionServiceInterface;
use OpenRiC\Exhibition\Services\ExhibitionService;

class OpenRiCExhibitionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExhibitionServiceInterface::class, ExhibitionService::class);
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-exhibition');

        $this->loadMigrationsFrom(database_path('migrations'));
    }
}
