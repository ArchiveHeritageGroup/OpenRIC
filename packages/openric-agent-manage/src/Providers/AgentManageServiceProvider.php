<?php

declare(strict_types=1);

namespace OpenRiC\AgentManage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenRiC\AgentManage\Contracts\PersonServiceInterface;
use OpenRiC\AgentManage\Services\PersonService;
use OpenRiC\AgentManage\Contracts\CorporateBodyServiceInterface;
use OpenRiC\AgentManage\Services\CorporateBodyService;
use OpenRiC\AgentManage\Contracts\FamilyServiceInterface;
use OpenRiC\AgentManage\Services\FamilyService;

class AgentManageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PersonServiceInterface::class, PersonService::class);
        $this->app->bind(CorporateBodyServiceInterface::class, CorporateBodyService::class);
        $this->app->bind(FamilyServiceInterface::class, FamilyService::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth.required', 'acl:read'])
            ->group(__DIR__ . '/../../routes/web.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'agent-manage');
    }
}
