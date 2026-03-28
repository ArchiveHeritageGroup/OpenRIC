<?php

declare(strict_types=1);

namespace OpenRiC\Core\Contracts;

/**
 * Interface for application settings stored in the PostgreSQL 'settings' table.
 *
 * Settings are organised by group and key, allowing logical grouping
 * (e.g. 'ui', 'search', 'triplestore') with individual keys within each group.
 */
interface SettingsServiceInterface
{
    /**
     * Retrieve a single setting value.
     *
     * @param  string  $group    The settings group (e.g. 'ui', 'search').
     * @param  string  $key      The setting key within the group.
     * @param  mixed   $default  Value returned when the setting does not exist.
     * @return mixed
     */
    public function get(string $group, string $key, mixed $default = null): mixed;

    /**
     * Store or update a single setting value.
     *
     * @param  string  $group  The settings group.
     * @param  string  $key    The setting key within the group.
     * @param  mixed   $value  The value to persist (will be cast to string for storage).
     */
    public function set(string $group, string $key, mixed $value): void;

    /**
     * Retrieve all settings within a group as a key => value associative array.
     *
     * @param  string  $group  The settings group.
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array;

    /**
     * Delete a single setting.
     *
     * @param  string  $group  The settings group.
     * @param  string  $key    The setting key to remove.
     */
    public function deleteKey(string $group, string $key): void;
}
