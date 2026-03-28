<?php

declare(strict_types=1);

namespace OpenRiC\Audit\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Audit\Contracts\AuditServiceInterface;
use OpenRiC\Audit\Services\AuditService;

class OpenRiCAuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditServiceInterface::class, AuditService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'admin'])
            ->prefix('admin/audit')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-audit');
    }
}
