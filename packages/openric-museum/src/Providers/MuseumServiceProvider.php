<?php
namespace OpenRiC\Museum\Providers;
use Illuminate\Support\ServiceProvider;
class MuseumServiceProvider extends ServiceProvider
{
    public function register(): void { $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'museum'); }
    public function boot(): void { }
}
