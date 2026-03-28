<?php

declare(strict_types=1);

namespace OpenRiC\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenRiC\Core\Contracts\SettingsServiceInterface;

/**
 * Application settings service — adapted from Heratio AhgSettingsService (508 lines).
 *
 * Reads and writes application settings from the PostgreSQL 'settings' table.
 * Settings are type-cast based on the stored 'type' column:
 *   string, integer, boolean, json, text, email, url
 *
 * Results are cached per group to reduce database round-trips. The cache is
 * invalidated automatically whenever a setting is written, deleted, or bulk-updated.
 *
 * Sensitive settings (is_sensitive=true) are masked in audit logs and API responses.
 */
class SettingsService implements SettingsServiceInterface
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Prefix used for all settings cache keys.
     */
    private const CACHE_PREFIX = 'openric_settings_';

    /**
     * In-memory cache for the current request (avoids repeated Cache::get calls).
     *
     * @var array<string, array>
     */
    private array $memoryCache = [];

    /**
     * {@inheritDoc}
     */
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $groupSettings = $this->getGroupRaw($group);

        if (!isset($groupSettings[$key])) {
            return $default;
        }

        $row = $groupSettings[$key];

        return $this->castValue($row->value, $row->type ?? 'string');
    }

    /**
     * {@inheritDoc}
     */
    public function getBool(string $group, string $key, bool $default = false): bool
    {
        $value = $this->getRawValue($group, $key);

        if ($value === null) {
            return $default;
        }

        return in_array($value, ['true', '1', 1, true, 'yes', 'on'], true);
    }

    /**
     * {@inheritDoc}
     */
    public function getInt(string $group, string $key, int $default = 0): int
    {
        $value = $this->getRawValue($group, $key);

        return $value !== null ? (int) $value : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function getString(string $group, string $key, string $default = ''): string
    {
        $value = $this->getRawValue($group, $key);

        return $value !== null ? (string) $value : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(string $group, string $feature): bool
    {
        // Try "{feature}" directly, then "{feature}_enabled", then "{feature}.enabled"
        $candidates = [$feature, $feature . '_enabled', $feature . '.enabled', 'enabled'];

        foreach ($candidates as $key) {
            $value = $this->getRawValue($group, $key);
            if ($value !== null) {
                return in_array($value, ['true', '1', 1, true, 'yes', 'on'], true);
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $group, string $key, mixed $value, ?string $type = null, ?string $description = null): void
    {
        $stringValue = $this->serializeValue($value);

        $data = [
            'value' => $stringValue,
            'updated_at' => now(),
        ];

        if ($type !== null) {
            $data['type'] = $type;
        }

        if ($description !== null) {
            $data['description'] = $description;
        }

        $exists = DB::table('settings')
            ->where('group', $group)
            ->where('key', $key)
            ->exists();

        if ($exists) {
            DB::table('settings')
                ->where('group', $group)
                ->where('key', $key)
                ->update($data);
        } else {
            DB::table('settings')->insert(array_merge($data, [
                'group' => $group,
                'key' => $key,
                'type' => $type ?? 'string',
                'created_at' => now(),
            ]));
        }

        $this->forgetGroupCache($group);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroup(string $group): array
    {
        $raw = $this->getGroupRaw($group);
        $result = [];

        foreach ($raw as $key => $row) {
            $result[$key] = $this->castValue($row->value, $row->type ?? 'string');
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupFull(string $group): array
    {
        $raw = $this->getGroupRaw($group);
        $result = [];

        foreach ($raw as $key => $row) {
            $result[$key] = [
                'value' => $this->castValue($row->value, $row->type ?? 'string'),
                'raw_value' => $row->value,
                'type' => $row->type ?? 'string',
                'description' => $row->description ?? null,
                'is_sensitive' => (bool) ($row->is_sensitive ?? false),
                'validation_rules' => $row->validation_rules ?? null,
            ];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(): array
    {
        $rows = DB::table('settings')
            ->select(['group', 'key', 'value', 'type', 'description', 'is_sensitive'])
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $group = $row->group;
            if (!isset($result[$group])) {
                $result[$group] = [];
            }

            $result[$group][$row->key] = [
                'value' => $this->castValue($row->value, $row->type ?? 'string'),
                'type' => $row->type ?? 'string',
                'description' => $row->description,
                'is_sensitive' => (bool) ($row->is_sensitive ?? false),
            ];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteKey(string $group, string $key): void
    {
        DB::table('settings')
            ->where('group', $group)
            ->where('key', $key)
            ->delete();

        $this->forgetGroupCache($group);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteGroup(string $group): void
    {
        DB::table('settings')
            ->where('group', $group)
            ->delete();

        $this->forgetGroupCache($group);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $group, string $key): bool
    {
        $groupSettings = $this->getGroupRaw($group);

        return isset($groupSettings[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function setMany(string $group, array $settings): void
    {
        DB::transaction(function () use ($group, $settings): void {
            foreach ($settings as $key => $value) {
                if (is_array($value) && isset($value['value'])) {
                    $this->set(
                        $group,
                        $key,
                        $value['value'],
                        $value['type'] ?? null,
                        $value['description'] ?? null
                    );
                } else {
                    $this->set($group, $key, $value);
                }
            }
        });

        $this->forgetGroupCache($group);
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(): void
    {
        $this->memoryCache = [];

        // Clear all known group caches
        $groups = DB::table('settings')
            ->select('group')
            ->distinct()
            ->pluck('group');

        foreach ($groups as $group) {
            Cache::forget(self::CACHE_PREFIX . $group);
        }
    }

    /**
     * Get the count of settings per group.
     *
     * @return array<string, int>
     */
    public function getGroupCounts(): array
    {
        return DB::table('settings')
            ->select('group', DB::raw('COUNT(*) as count'))
            ->groupBy('group')
            ->orderBy('group')
            ->pluck('count', 'group')
            ->toArray();
    }

    /**
     * Search settings by key pattern.
     *
     * @return array<int, object>
     */
    public function search(string $pattern): array
    {
        return DB::table('settings')
            ->where('key', 'ILIKE', '%' . $pattern . '%')
            ->orWhere('description', 'ILIKE', '%' . $pattern . '%')
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->all();
    }

    /**
     * Export all settings as a flat array for backup/migration.
     *
     * @param bool $includeSensitive Whether to include sensitive values
     * @return array<string, mixed>
     */
    public function export(bool $includeSensitive = false): array
    {
        $query = DB::table('settings')->orderBy('group')->orderBy('key');

        if (!$includeSensitive) {
            $query->where('is_sensitive', false);
        }

        $rows = $query->get();
        $result = [];

        foreach ($rows as $row) {
            $result["{$row->group}.{$row->key}"] = [
                'group' => $row->group,
                'key' => $row->key,
                'value' => $row->is_sensitive && !$includeSensitive ? '***' : $row->value,
                'type' => $row->type,
                'description' => $row->description,
                'is_sensitive' => (bool) $row->is_sensitive,
            ];
        }

        return $result;
    }

    /**
     * Import settings from a flat array (e.g. from export or config migration).
     *
     * @param array<string, array> $settings Keyed by "group.key"
     */
    public function import(array $settings): void
    {
        DB::transaction(function () use ($settings): void {
            foreach ($settings as $dotKey => $data) {
                $group = $data['group'] ?? explode('.', $dotKey, 2)[0];
                $key = $data['key'] ?? explode('.', $dotKey, 2)[1] ?? $dotKey;

                $this->set($group, $key, $data['value'], $data['type'] ?? null, $data['description'] ?? null);

                if (isset($data['is_sensitive'])) {
                    DB::table('settings')
                        ->where('group', $group)
                        ->where('key', $key)
                        ->update(['is_sensitive' => (bool) $data['is_sensitive']]);
                }
            }
        });

        $this->clearCache();
    }

    /**
     * Validate a setting value against its stored validation rules.
     *
     * @return array Validation errors (empty if valid)
     */
    public function validate(string $group, string $key, mixed $value): array
    {
        $row = DB::table('settings')
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        if (!$row || empty($row->validation_rules)) {
            return [];
        }

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['value' => $value],
            ['value' => $row->validation_rules]
        );

        return $validator->fails() ? $validator->errors()->all() : [];
    }

    // ========================================================================
    // Private helpers
    // ========================================================================

    /**
     * Get the raw value string for a setting (no type-casting).
     */
    private function getRawValue(string $group, string $key): ?string
    {
        $groupSettings = $this->getGroupRaw($group);

        return isset($groupSettings[$key]) ? $groupSettings[$key]->value : null;
    }

    /**
     * Load all settings for a group as raw row objects, keyed by key.
     *
     * @return array<string, object>
     */
    private function getGroupRaw(string $group): array
    {
        if (isset($this->memoryCache[$group])) {
            return $this->memoryCache[$group];
        }

        $cacheKey = self::CACHE_PREFIX . $group;

        $rows = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($group): array {
            return DB::table('settings')
                ->where('group', $group)
                ->select(['key', 'value', 'type', 'description', 'is_sensitive', 'validation_rules'])
                ->get()
                ->keyBy('key')
                ->all();
        });

        $this->memoryCache[$group] = $rows;

        return $rows;
    }

    /**
     * Cast a raw string value to its appropriate PHP type.
     */
    private function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => in_array($value, ['true', '1', 'yes', 'on'], true),
            'integer' => (int) $value,
            'json' => json_decode($value, true) ?? [],
            'float' => (float) $value,
            default => $value,
        };
    }

    /**
     * Serialize a PHP value to a string for storage.
     */
    private function serializeValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }

    /**
     * Invalidate the cached settings for a given group.
     */
    private function forgetGroupCache(string $group): void
    {
        unset($this->memoryCache[$group]);
        Cache::forget(self::CACHE_PREFIX . $group);
    }
}
