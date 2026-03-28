<?php

declare(strict_types=1);

namespace OpenRiC\LandingPage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\LandingPage\Contracts\LandingPageServiceInterface;
use OpenRiC\LandingPage\Services\LandingPageService;

class LandingPageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LandingPageServiceInterface::class, LandingPageService::class);
    }

    public function boot(): void
    {
        // Public landing page route
        Route::middleware('web')
            ->group(function (): void {
                Route::get('/', [\OpenRiC\LandingPage\Http\Controllers\LandingPageController::class, 'index'])
                    ->name('landing-page.index');
            });

        // Admin routes
        Route::middleware(['web', 'auth.required', 'admin'])
            ->prefix('admin/landing-page')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-landing-page');
    }
}
