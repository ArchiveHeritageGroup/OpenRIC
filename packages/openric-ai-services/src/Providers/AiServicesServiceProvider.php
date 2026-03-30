<?php
namespace OpenRiC\AiServices\Providers;
use Illuminate\Support\ServiceProvider;
class AiServicesServiceProvider extends ServiceProvider
{
    public function register(): void { $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'ai-services'); }
    public function boot(): void { }
}
