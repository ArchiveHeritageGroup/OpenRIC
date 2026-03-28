<?php

declare(strict_types=1);

namespace OpenRiC\SettingsManage\Contracts;

/**
 * Settings management service interface.
 *
 * Adapted from Heratio ahg-settings SettingsService (268 lines).
 * Provides read/write access to all OpenRiC settings: global, scoped, grouped, email, system info.
 */
interface SettingsManageServiceInterface
{
    // ─── Core setting CRUD (setting + setting_i18n tables) ────────────

    /**
     * Get a single setting value by name and optional scope.
     */
    public function getSetting(string $name, ?string $scope = null, string $culture = 'en'): ?string;

    /**
     * Save a setting value.
     */
    public function saveSetting(string $name, ?string $scope, string $value, string $culture = 'en'): void;

    /**
     * Get all settings for a scope, keyed by name.
     *
     * @return array<string, object>
     */
    public function getSettingsByScope(?string $scope, string $culture = 'en'): array;

    // ─── OpenRiC settings (openric_settings table) ───────────────────

    /**
     * Get a single OpenRiC setting by key.
     */
    public function getOpenRiCSetting(string $key): ?string;

    /**
     * Save a single OpenRiC setting by key.
     */
    public function saveOpenRiCSetting(string $key, ?string $value): void;

    /**
     * Get all settings in a group.
     */
    public function getOpenRiCSettingsByGroup(string $group): \Illuminate\Support\Collection;

    /**
     * Batch-save OpenRiC settings.
     *
     * @param  array<string, string> $settings  key => value pairs
     */
    public function saveOpenRiCSettings(array $settings): void;

    // ─── Domain-specific settings retrievers ─────────────────────────

    /**
     * Get global settings (20+ keys).
     *
     * @return array<string, ?string>
     */
    public function getGlobalSettings(string $culture = 'en'): array;

    /**
     * Save global settings.
     */
    public function saveGlobalSettings(array $data, string $culture = 'en'): void;

    /**
     * Get site information (title, description, base URL).
     *
     * @return array{siteTitle: string, siteDescription: string, siteBaseUrl: string}
     */
    public function getSiteInformation(string $culture = 'en'): array;

    /**
     * Get security settings.
     *
     * @return array<string, string>
     */
    public function getSecuritySettings(string $culture = 'en'): array;

    /**
     * Get identifier/mask settings.
     *
     * @return array<string, string>
     */
    public function getIdentifierSettings(string $culture = 'en'): array;

    /**
     * Get treeview settings.
     *
     * @return array<string, string>
     */
    public function getTreeviewSettings(string $culture = 'en'): array;

    /**
     * Get OAI repository settings.
     *
     * @return array<string, string>
     */
    public function getOaiSettings(string $culture = 'en'): array;

    /**
     * Get digital object derivative settings.
     *
     * @return array<string, string>
     */
    public function getDigitalObjectSettings(string $culture = 'en'): array;

    /**
     * Get interface label settings (ui_label scope).
     *
     * @return array<string, object>
     */
    public function getInterfaceLabelSettings(string $culture = 'en'): array;

    /**
     * Get language settings (i18n_languages scope).
     *
     * @return array<string, object>
     */
    public function getLanguageSettings(string $culture = 'en'): array;

    /**
     * Get email settings (SMTP, notification, template, toggles).
     *
     * @return array{smtp: \Illuminate\Support\Collection, notification: \Illuminate\Support\Collection, template: \Illuminate\Support\Collection, toggles: array}
     */
    public function getEmailSettings(): array;

    /**
     * Save email settings.
     */
    public function saveEmailSettings(array $settings, array $toggles = []): void;

    /**
     * Get system information (PHP version, Laravel version, disk, extensions, DB size).
     *
     * @return array<string, mixed>
     */
    public function getSystemInfo(): array;
}
