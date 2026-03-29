<?php

declare(strict_types=1);

namespace OpenRiC\Translation\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Translation\Contracts\TranslationServiceInterface;
use OpenRiC\Translation\Services\TranslationService;

/**
 * Translation package service provider.
 *
 * Adapted from Heratio AhgTranslationServiceProvider.
 * Registers the TranslationService singleton, loads routes, views, and migrations.
 */
class OpenRiCTranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TranslationServiceInterface::class, TranslationService::class);
    }

    public function boot(): void
    {
        // Admin routes (auth-protected)
        Route::middleware(['web', 'auth'])
            ->prefix('admin/translation')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-translation');

        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');
    }
}
