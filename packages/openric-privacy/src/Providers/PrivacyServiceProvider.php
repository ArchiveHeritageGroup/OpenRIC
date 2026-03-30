<?php
namespace OpenRiC\Privacy\Providers;
use Illuminate\Support\ServiceProvider;
class PrivacyServiceProvider extends ServiceProvider
{
    public function register(): void { $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'privacy'); }
    public function boot(): void { }
}
