<?php

declare(strict_types=1);

namespace OpenRiC\PlaceManage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\PlaceManage\Contracts\PlaceServiceInterface;
use OpenRiC\PlaceManage\Services\PlaceService;

class PlaceManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PlaceServiceInterface::class, PlaceService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'acl:read'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'place-manage');
    }
}
