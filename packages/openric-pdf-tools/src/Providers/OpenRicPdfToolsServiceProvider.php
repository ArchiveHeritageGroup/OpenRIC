<?php

namespace OpenRicPdfTools\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRicPdfTools\Services\PdfTextExtractService;
use OpenRicPdfTools\Services\TiffPdfMergeService;

class OpenRicPdfToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PdfTextExtractService::class, function () {
            return new PdfTextExtractService();
        });

        $this->app->singleton(TiffPdfMergeService::class, function () {
            return new TiffPdfMergeService();
        });
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-pdf-tools');
    }
}
