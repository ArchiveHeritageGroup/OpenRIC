<?php

declare(strict_types=1);

namespace OpenRiC\Accession\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use OpenRiC\Accession\Contracts\AccessionServiceInterface;
use OpenRiC\Accession\Services\AccessionService;

/**
 * Service provider for the openric-accession package.
 *
 * Adapted from Heratio OpenRic\AccessionServiceProvider which registers
 * routes and views. OpenRiC additionally registers the service singleton.
 */
class AccessionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccessionServiceInterface::class, function ($app) {
            return new AccessionService();
        });
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-accession');
    }
}
