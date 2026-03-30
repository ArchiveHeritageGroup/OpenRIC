<?php
namespace OpenRiC\Registry\Providers;
use Illuminate\Support\ServiceProvider;
class RegistryServiceProvider extends ServiceProvider
{
    public function register(): void { $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'registry'); }
    public function boot(): void { }
}
