<?php

declare(strict_types=1);

namespace OpenRiC\Condition\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Condition\Contracts\ConditionServiceInterface;
use OpenRiC\Condition\Services\ConditionService;

class ConditionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConditionServiceInterface::class, ConditionService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required'])->group(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'condition');
    }
}
