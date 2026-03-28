<?php

declare(strict_types=1);

namespace OpenRiC\DigitalObject\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use OpenRiC\DigitalObject\Contracts\DigitalObjectServiceInterface;
use OpenRiC\DigitalObject\Services\DigitalObjectService;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Service provider for the openric-digital-object package.
 *
 * Adapted from Heratio AhgDamServiceProvider which registers routes and views.
 * OpenRiC additionally registers the service singleton with DI.
 */
class DigitalObjectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DigitalObjectServiceInterface::class, function ($app) {
            return new DigitalObjectService(
                $app->make(TriplestoreServiceInterface::class),
                config('filesystems.digital_objects_disk', 'public')
            );
        });
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-digital-object');
    }
}
