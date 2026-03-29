<?php

declare(strict_types=1);

namespace OpenRiC\Display\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\Display\Contracts\DisplayServiceInterface;

/**
 * Display service implementation.
 *
 * Adapted from Heratio AhgDisplay\Services\DisplayService (142 lines)
 * + AhgDisplay\Services\DisplayTypeDetector (237 lines)
 * + AhgDisplay\Repositories\UserBrowseSettingsRepository (117 lines).
 *
 * Unified into a single service implementing DisplayServiceInterface.
 * Uses PostgreSQL ILIKE for case-insensitive matching.
 * Uses PostgreSQL array_position() instead of MySQL FIELD() for ordered results.
 */
class DisplayService implements DisplayServiceInterface
{
    // =========================================================================
    // DisplayService methods (from Heratio DisplayService)
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function getObjectDisplay(int $objectId): array
    {
        $type = DisplayTypeDetector::detect($objectId);
        $profile = DisplayTypeDetector::getProfile($objectId);
        $object = $this->getObjectData($objectId);

        return [
            'object' => $object,
            'type' => $type,
            'profile' => $profile,
            'fields' => $this->getFieldsForProfile($profile),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getObjectData(int $objectId): ?object
    {
        $culture = app()->getLocale();
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as level', function ($j) use ($culture) {
                $j->on('io.level_of_description_id', '=', 'level.id')->where('level.culture', '=', $culture);
            })
            ->where('io.id', $objectId)
            ->select('io.*', 'i18n.title', 'i18n.scope_and_content', 'i18n.extent_and_medium',
                'i18n.archival_history', 'i18n.acquisition', 'i18n.arrangement',
                'i18n.access_conditions', 'i18n.reproduction_conditions',
                'level.name as level_name')
            ->first();
    }

    /**
     * {@inheritDoc}
     *
     * Uses PostgreSQL array_position() instead of MySQL FIELD() for ordered results.
     */
    public function getFieldsForProfile(?object $profile): array
    {
        if (!$profile) {
            return [];
        }

        $fieldCodes = array_merge(
            json_decode($profile->identity_fields ?? '[]', true) ?: [],
            json_decode($profile->description_fields ?? '[]', true) ?: [],
            json_decode($profile->context_fields ?? '[]', true) ?: [],
            json_decode($profile->access_fields ?? '[]', true) ?: []
        );

        if (empty($fieldCodes)) {
            return [];
        }

        $culture = app()->getLocale();

        // PostgreSQL: use array_position() for ordered results instead of MySQL FIELD()
        $placeholders = implode(',', array_fill(0, count($fieldCodes), '?'));

        return DB::table('display_field as df')
            ->leftJoin('display_field_i18n as dfi', function ($j) use ($culture) {
                $j->on('df.id', '=', 'dfi.id')->where('dfi.culture', '=', $culture);
            })
            ->whereIn('df.code', $fieldCodes)
            ->select('df.*', 'dfi.name', 'dfi.help_text')
            ->orderByRaw(
                'array_position(ARRAY[' . $placeholders . ']::text[], df.code)',
                $fieldCodes
            )
            ->get()
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getLevels(?string $domain = null): array
    {
        $culture = app()->getLocale();
        $query = DB::table('display_level as dl')
            ->leftJoin('display_level_i18n as dli', function ($j) use ($culture) {
                $j->on('dl.id', '=', 'dli.id')->where('dli.culture', '=', $culture);
            })
            ->select('dl.*', 'dli.name', 'dli.description')
            ->orderBy('dl.sort_order');

        if ($domain) {
            $query->where('dl.domain', $domain);
        }

        return $query->get()->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getCollectionTypes(): array
    {
        $culture = app()->getLocale();
        return DB::table('display_collection_type as dct')
            ->leftJoin('display_collection_type_i18n as dcti', function ($j) use ($culture) {
                $j->on('dct.id', '=', 'dcti.id')->where('dcti.culture', '=', $culture);
            })
            ->select('dct.*', 'dcti.name', 'dcti.description')
            ->orderBy('dct.sort_order')
            ->get()
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function setObjectType(int $objectId, string $type): void
    {
        DB::table('display_object_config')->updateOrInsert(
            ['object_id' => $objectId],
            ['object_type' => $type, 'updated_at' => now()]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setObjectTypeRecursive(int $parentId, string $type): int
    {
        $children = DB::table('information_object')
            ->where('parent_id', $parentId)
            ->pluck('id')
            ->toArray();

        $count = 0;
        foreach ($children as $childId) {
            $this->setObjectType($childId, $type);
            $count++;
            $count += $this->setObjectTypeRecursive($childId, $type);
        }

        return $count;
    }

    /**
     * {@inheritDoc}
     */
    public function assignProfile(int $objectId, int $profileId, string $context = 'default', bool $primary = false): void
    {
        DB::table('display_object_profile')->updateOrInsert(
            ['object_id' => $objectId, 'profile_id' => $profileId, 'context' => $context],
            ['is_primary' => $primary]
        );
    }

    // =========================================================================
    // DisplayTypeDetector methods (delegated to static class)
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function detectType(int $objectId): string
    {
        return DisplayTypeDetector::detect($objectId);
    }

    /**
     * {@inheritDoc}
     */
    public function detectAndSaveType(int $objectId, bool $force = false): string
    {
        return DisplayTypeDetector::detectAndSave($objectId, $force);
    }

    /**
     * {@inheritDoc}
     */
    public function getProfile(int $objectId): ?object
    {
        return DisplayTypeDetector::getProfile($objectId);
    }

    /**
     * {@inheritDoc}
     */
    public function getType(int $objectId): string
    {
        return DisplayTypeDetector::getType($objectId);
    }

    // =========================================================================
    // UserBrowseSettings methods (from Heratio UserBrowseSettingsRepository)
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function getBrowseSettings(int $userId): array
    {
        $settings = DB::table('user_browse_settings')
            ->where('user_id', $userId)
            ->first();

        if ($settings) {
            $settings = (array) $settings;
            $settings['last_filters'] = $settings['last_filters']
                ? json_decode($settings['last_filters'], true)
                : [];
            return $settings;
        }

        return $this->getDefaultBrowseSettings($userId);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultBrowseSettings(int $userId = 0): array
    {
        return [
            'id' => null,
            'user_id' => $userId,
            'use_glam_browse' => false,
            'default_sort_field' => 'updated_at',
            'default_sort_direction' => 'desc',
            'default_view' => 'list',
            'items_per_page' => 30,
            'show_facets' => true,
            'remember_filters' => true,
            'last_filters' => [],
            'created_at' => null,
            'updated_at' => null,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function useGlamBrowse(int $userId): bool
    {
        $settings = DB::table('user_browse_settings')
            ->where('user_id', $userId)
            ->first();

        return $settings ? (bool) $settings->use_glam_browse : false;
    }

    /**
     * {@inheritDoc}
     */
    public function setGlamBrowse(int $userId, bool $enabled): bool
    {
        return $this->saveBrowseSettings($userId, ['use_glam_browse' => $enabled]);
    }

    /**
     * {@inheritDoc}
     */
    public function saveBrowseSettings(int $userId, array $data): bool
    {
        $existing = DB::table('user_browse_settings')
            ->where('user_id', $userId)
            ->first();

        $saveData = [
            'updated_at' => now(),
        ];

        $allowedFields = [
            'use_glam_browse', 'default_sort_field', 'default_sort_direction',
            'default_view', 'items_per_page', 'show_facets', 'remember_filters',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $saveData[$field] = $data[$field];
            }
        }

        if (isset($data['last_filters'])) {
            $saveData['last_filters'] = is_array($data['last_filters'])
                ? json_encode($data['last_filters'])
                : $data['last_filters'];
        }

        if ($existing) {
            return DB::table('user_browse_settings')
                ->where('id', $existing->id)
                ->update($saveData) >= 0;
        }

        $saveData['user_id'] = $userId;
        $saveData['created_at'] = now();
        return DB::table('user_browse_settings')->insert($saveData);
    }

    /**
     * {@inheritDoc}
     */
    public function saveLastFilters(int $userId, array $filters): bool
    {
        return $this->saveBrowseSettings($userId, ['last_filters' => $filters]);
    }

    /**
     * {@inheritDoc}
     */
    public function getLastFilters(int $userId): array
    {
        $settings = $this->getBrowseSettings($userId);
        return $settings['last_filters'] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function resetBrowseSettings(int $userId): bool
    {
        return DB::table('user_browse_settings')
            ->where('user_id', $userId)
            ->delete() > 0;
    }
}
