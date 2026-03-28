<?php

declare(strict_types=1);

namespace OpenRiC\Search\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Search\Contracts\SearchServiceInterface;
use OpenRiC\Search\Services\SearchService;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SearchServiceInterface::class, SearchService::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'search');
    }
}
