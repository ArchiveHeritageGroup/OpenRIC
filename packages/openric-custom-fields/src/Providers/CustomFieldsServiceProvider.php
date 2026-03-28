<?php

declare(strict_types=1);

namespace OpenRiC\CustomFields\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\CustomFields\Contracts\CustomFieldsServiceInterface;
use OpenRiC\CustomFields\Services\CustomFieldsService;

class CustomFieldsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CustomFieldsServiceInterface::class, CustomFieldsService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'admin'])
            ->prefix('admin/custom-fields')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-custom-fields');
    }
}
