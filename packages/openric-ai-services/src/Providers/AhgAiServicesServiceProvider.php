<?php

namespace OpenRic\AiServices\Providers;

use OpenRic\AiServices\Services\LlmService;
use OpenRic\AiServices\Services\NerService;
use Illuminate\Support\ServiceProvider;

class OpenRic\AiServicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LlmService::class);
        $this->app->singleton(NerService::class);
        $this->app->singleton(\OpenRic\AiServices\Services\HtrService::class);
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Route::middleware('web')
            ->group(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'ahg-ai-services');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \OpenRic\AiServices\Commands\AiNerExtractCommand::class,
                \OpenRic\AiServices\Commands\AiTranslateCommand::class,
                \OpenRic\AiServices\Commands\AiProcessPendingCommand::class,
                \OpenRic\AiServices\Commands\AiSuggestDescriptionCommand::class,
                \OpenRic\AiServices\Commands\AiHtrCommand::class,
                \OpenRic\AiServices\Commands\AiSpellcheckCommand::class,
                \OpenRic\AiServices\Commands\AiNerSyncCommand::class,
                \OpenRic\AiServices\Commands\AiSyncEntityCacheCommand::class,
                \OpenRic\AiServices\Commands\AiInstallCommand::class,
                \OpenRic\AiServices\Commands\AiConditionScanCommand::class,
                \OpenRic\AiServices\Commands\AiConditionStatusCommand::class,
                \OpenRic\AiServices\Commands\AiSummarizeCommand::class,
                \OpenRic\AiServices\Commands\QdrantIndexCommand::class,
                \OpenRic\AiServices\Commands\QdrantImageIndexCommand::class,
                \OpenRic\AiServices\Commands\LlmHealthCheckCommand::class,
            ]);
        }
    }
}
