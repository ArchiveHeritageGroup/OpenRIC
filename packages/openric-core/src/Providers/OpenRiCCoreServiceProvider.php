<?php

declare(strict_types=1);

namespace OpenRiC\Core\Providers;

use Illuminate\Support\ServiceProvider;
use OpenRiC\Core\Contracts\SettingsServiceInterface;
use OpenRiC\Core\Contracts\RelationshipServiceInterface;
use OpenRiC\Core\Contracts\StandardsMappingServiceInterface;
use OpenRiC\Core\Services\RelationshipService;
use OpenRiC\Core\Services\SettingsService;
use OpenRiC\Core\Services\StandardsMappingService;

/**
 * Service provider for the openric-core package.
 *
 * Registers core bindings (settings service) and publishes
 * the package configuration file.
 */
class OpenRiCCoreServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'openric-core');

        $this->app->singleton(SettingsServiceInterface::class, SettingsService::class);
        $this->app->singleton(StandardsMappingServiceInterface::class, StandardsMappingService::class);
        $this->app->singleton(RelationshipServiceInterface::class, RelationshipService::class);
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->configPath() => config_path('openric-core.php'),
            ], 'openric-core-config');
        }

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openric-core');

        $this->loadSettingsIntoConfig();
    }

    /**
     * Hydrate application config values from the settings table
     * once the application has fully booted.
     */
    private function loadSettingsIntoConfig(): void
    {
        $this->app->booted(function (): void {
            /** @var SettingsServiceInterface $settings */
            $settings = $this->app->make(SettingsServiceInterface::class);

            $mappings = config('openric-core.settings_config_map', []);

            foreach ($mappings as $mapping) {
                $group = $mapping['group'] ?? '';
                $key = $mapping['key'] ?? '';
                $configKey = $mapping['config_key'] ?? '';

                if ($group === '' || $key === '' || $configKey === '') {
                    continue;
                }

                $value = $settings->get($group, $key);

                if ($value !== null) {
                    config([$configKey => $value]);
                }
            }
        });
    }

    /**
     * Return the path to the package configuration file.
     */
    private function configPath(): string
    {
        return __DIR__ . '/../../config/openric-core.php';
    }
}
