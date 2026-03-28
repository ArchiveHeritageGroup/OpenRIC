<?php

declare(strict_types=1);

namespace OpenRiC\Theme\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use OpenRiC\Theme\Contracts\ThemeServiceInterface;
use OpenRiC\Theme\Http\Middleware\ViewSwitchMiddleware;
use OpenRiC\Theme\Services\ThemeService;

class OpenRiCThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeServiceInterface::class, ThemeService::class);
    }

    public function boot(): void
    {
        $viewPath = __DIR__ . '/../../resources/views';

        $this->loadViewsFrom($viewPath, 'theme');

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', ViewSwitchMiddleware::class);

        View::composer('theme::layouts.*', function ($view) {
            /** @var ThemeServiceInterface $themeService */
            $themeService = app(ThemeServiceInterface::class);
            $view->with('themeData', $themeService->getLayoutData());
            $view->with('navigationItems', $themeService->getNavigationItems());
            $view->with('currentViewMode', $themeService->getCurrentViewMode());
        });
    }
}
