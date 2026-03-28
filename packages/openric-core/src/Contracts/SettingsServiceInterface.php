<?php

declare(strict_types=1);

namespace OpenRiC\Core\Contracts;

/**
 * Application settings stored in PostgreSQL 'settings' table.
 *
 * Adapted from Heratio AhgSettingsService (508 lines).
 * Settings are organised by group (general, fuseki, theme, security, etc.)
 * with individual keys within each group. Values are type-cast based on
 * the 'type' column (string, integer, boolean, json, text, email, url).
 */
interface SettingsServiceInterface
{
    /**
     * Retrieve a single setting value, type-cast according to the stored type.
     */
    public function get(string $group, string $key, mixed $default = null): mixed;

    /**
     * Retrieve a setting as a boolean.
     */
    public function getBool(string $group, string $key, bool $default = false): bool;

    /**
     * Retrieve a setting as an integer.
     */
    public function getInt(string $group, string $key, int $default = 0): int;

    /**
     * Retrieve a setting as a string.
     */
    public function getString(string $group, string $key, string $default = ''): string;

    /**
     * Check if a feature is enabled. Looks for '{feature}.enabled' or '{feature}_enabled' key.
     */
    public function isEnabled(string $group, string $feature): bool;

    /**
     * Store or update a single setting value.
     */
    public function set(string $group, string $key, mixed $value, ?string $type = null, ?string $description = null): void;

    /**
     * Retrieve all settings within a group as key => value array.
     */
    public function getGroup(string $group): array;

    /**
     * Retrieve all settings within a group as key => full row (value, type, description, etc.).
     */
    public function getGroupFull(string $group): array;

    /**
     * Retrieve all setting groups and their keys.
     */
    public function getAll(): array;

    /**
     * Delete a single setting.
     */
    public function deleteKey(string $group, string $key): void;

    /**
     * Delete all settings in a group.
     */
    public function deleteGroup(string $group): void;

    /**
     * Check if a setting exists.
     */
    public function has(string $group, string $key): bool;

    /**
     * Bulk set multiple settings at once.
     */
    public function setMany(string $group, array $settings): void;

    /**
     * Clear all cached settings.
     */
    public function clearCache(): void;
}
