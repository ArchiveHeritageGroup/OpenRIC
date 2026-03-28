<?php

declare(strict_types=1);

namespace OpenRiC\Graph\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Graph\Contracts\GraphServiceInterface;
use OpenRiC\Graph\Services\GraphService;

class GraphServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GraphServiceInterface::class, GraphService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required'])->group(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'graph');
    }
}
