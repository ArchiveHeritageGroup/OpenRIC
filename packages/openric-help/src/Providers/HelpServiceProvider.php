<?php

declare(strict_types=1);

namespace OpenRiC\Help\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Help\Contracts\HelpServiceInterface;
use OpenRiC\Help\Services\HelpService;

class HelpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HelpServiceInterface::class, HelpService::class);
        $this->app->alias(HelpServiceInterface::class, HelpService::class);
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-help');
    }
}
