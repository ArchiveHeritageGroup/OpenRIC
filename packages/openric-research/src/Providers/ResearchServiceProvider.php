<?php

declare(strict_types=1);

namespace OpenRiC\Research\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Research\Contracts\ResearchServiceInterface;
use OpenRiC\Research\Services\ResearchService;

class ResearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ResearchServiceInterface::class, ResearchService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'research');
    }
}
