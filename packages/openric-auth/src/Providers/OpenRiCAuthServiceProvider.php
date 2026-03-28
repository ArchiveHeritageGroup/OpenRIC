<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Auth\Auth\OpenRiCUserProvider;
use OpenRiC\Auth\Contracts\AclServiceInterface;
use OpenRiC\Auth\Contracts\SecurityClearanceServiceInterface;
use OpenRiC\Auth\Http\Middleware\CheckAcl;
use OpenRiC\Auth\Http\Middleware\RequireAdmin;
use OpenRiC\Auth\Http\Middleware\RequireAuth;
use OpenRiC\Auth\Http\Middleware\SecurityClearanceMiddleware;
use OpenRiC\Auth\Services\AclService;
use OpenRiC\Auth\Services\SecurityClearanceService;

class OpenRiCAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AclServiceInterface::class, AclService::class);
        $this->app->singleton(SecurityClearanceServiceInterface::class, SecurityClearanceService::class);
    }

    public function boot(): void
    {
        Auth::provider('openric', function ($app, array $config) {
            return new OpenRiCUserProvider();
        });

        $router = $this->app['router'];
        $router->aliasMiddleware('auth.required', RequireAuth::class);
        $router->aliasMiddleware('admin', RequireAdmin::class);
        $router->aliasMiddleware('acl', CheckAcl::class);
        $router->aliasMiddleware('clearance', SecurityClearanceMiddleware::class);

        Route::middleware('web')->group(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-auth');
    }
}
