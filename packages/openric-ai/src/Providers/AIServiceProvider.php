<?php

declare(strict_types=1);

namespace OpenRiC\AI\Providers;

use Illuminate\Support\ServiceProvider;
use OpenRiC\AI\Contracts\EmbeddingServiceInterface;
use OpenRiC\AI\Services\OllamaEmbeddingService;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmbeddingServiceInterface::class, OllamaEmbeddingService::class);
    }

    public function boot(): void
    {
        //
    }
}
