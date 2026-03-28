<?php

declare(strict_types=1);

namespace OpenRiC\Donor\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use OpenRiC\Donor\Contracts\DonorServiceInterface;
use OpenRiC\Donor\Services\DonorService;

/**
 * Service provider for the openric-donor package.
 *
 * Adapted from Heratio AhgDonorManageServiceProvider which registers
 * routes and views. OpenRiC additionally registers the service singleton.
 */
class DonorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DonorServiceInterface::class, function ($app) {
            return new DonorService();
        });
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-donor');
    }
}
