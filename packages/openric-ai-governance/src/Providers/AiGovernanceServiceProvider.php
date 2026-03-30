<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\AiGovernance\Services\AiGovernanceService;
use OpenRiC\AiGovernance\Contracts\AiGovernanceServiceInterface;

class AiGovernanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(AiGovernanceServiceInterface::class, AiGovernanceService::class);
        $this->app->bind(AiGovernanceService::class, function ($app) {
            return new AiGovernanceService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes with middleware
        Route::middleware(['web', 'auth.required'])
            ->prefix('admin/ai-governance')
            ->group(__DIR__ . '/../../routes/web.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'ai-governance');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
