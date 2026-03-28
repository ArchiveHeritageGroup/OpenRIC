<?php

declare(strict_types=1);

namespace OpenRiC\AI\Providers;

use Illuminate\Support\ServiceProvider;
use OpenRiC\AI\Contracts\EmbeddingServiceInterface;
use OpenRiC\AI\Services\HtrService;
use OpenRiC\AI\Services\LlmService;
use OpenRiC\AI\Services\NerService;
use OpenRiC\AI\Services\OllamaEmbeddingService;

/**
 * AI Service Provider — registers LLM, NER, HTR, and embedding services.
 * Adapted from Heratio AhgAiServicesServiceProvider (44 lines).
 */
class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LlmService::class);
        $this->app->singleton(NerService::class);
        $this->app->singleton(HtrService::class);
        $this->app->singleton(EmbeddingServiceInterface::class, OllamaEmbeddingService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-ai');
    }
}
