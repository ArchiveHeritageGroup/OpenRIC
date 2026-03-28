<?php

declare(strict_types=1);

namespace OpenRiC\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenRiC\Core\Contracts\SettingsServiceInterface;

/**
 * Concrete implementation of SettingsServiceInterface.
 *
 * Reads and writes application settings from the PostgreSQL 'settings' table.
 * Results are cached per group to reduce database round-trips; the cache is
 * invalidated automatically whenever a setting is written or deleted.
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
     * {@inheritDoc}
     */
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $groupSettings = $this->getGroup($group);

        return array_key_exists($key, $groupSettings) ? $groupSettings[$key] : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $group, string $key, mixed $value): void
    {
        $exists = DB::table('settings')
            ->where('group', $group)
            ->where('key', $key)
            ->exists();

        if ($exists) {
            DB::table('settings')
                ->where('group', $group)
                ->where('key', $key)
                ->update([
                    'value' => is_string($value) ? $value : json_encode($value),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('settings')->insert([
                'group' => $group,
                'key' => $key,
                'value' => is_string($value) ? $value : json_encode($value),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->forgetGroupCache($group);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroup(string $group): array
    {
        $cacheKey = self::CACHE_PREFIX . $group;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($group): array {
            $rows = DB::table('settings')
                ->where('group', $group)
                ->select(['key', 'value'])
                ->get();

            $settings = [];

            foreach ($rows as $row) {
                $decoded = json_decode((string) $row->value, true);
                $settings[$row->key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $row->value;
            }

            return $settings;
        });
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
     * Invalidate the cached settings for a given group.
     */
    private function forgetGroupCache(string $group): void
    {
        Cache::forget(self::CACHE_PREFIX . $group);
    }
}
