<?php

namespace OpenRicMetadataExtraction\Providers;

use OpenRicMetadataExtraction\Services\MetadataExtractionService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class OpenRicMetadataExtractionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MetadataExtractionService::class, function () {
            return new MetadataExtractionService();
        });
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-metadata-extraction');
    }
}
