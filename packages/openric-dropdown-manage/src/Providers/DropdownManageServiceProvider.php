<?php

declare(strict_types=1);

namespace OpenRiC\DropdownManage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DropdownManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No additional bindings; uses OpenRiC\Core\Services\DropdownService from openric-core.
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'admin'])
            ->prefix('admin/dropdowns')
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-dropdown-manage');
    }
}
