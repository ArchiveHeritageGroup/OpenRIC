<?php

declare(strict_types=1);

namespace OpenRiC\Display\Services;

use Illuminate\Support\Facades\DB;

/**
 * GLAM domain type detector.
 *
 * Adapted from Heratio AhgDisplay\Services\DisplayTypeDetector (237 lines).
 * Detects whether an information object belongs to archive, museum, gallery,
 * library, DAM, or universal domain based on level, parent, events, media type.
 * Uses PostgreSQL ILIKE for case-insensitive matching.
 */
class DisplayTypeDetector
{
    /**
     * Mapping from level-of-description names to GLAM domains.
     *
     * @var array<string, string>
     */
    protected static array $levelToDomain = [
        // Archive (ISAD)
        'fonds' => 'archive',
        'subfonds' => 'archive',
        'series' => 'archive',
        'subseries' => 'archive',
        'file' => 'archive',
        'item' => 'archive',
        'piece' => 'archive',
        'record group' => 'archive',
        'document' => 'archive',
        'part' => 'archive',
        'travel and exploration' => 'archive',

        // Museum (Spectrum)
        'object' => 'museum',
        'specimen' => 'museum',
        'artefact' => 'museum',
        'artifact' => 'museum',
        '3d model' => 'museum',

        // Gallery
        'artwork' => 'gallery',
        'painting' => 'gallery',
        'sculpture' => 'gallery',
        'drawing' => 'gallery',
        'print' => 'gallery',
        'installation' => 'gallery',

        // Library
        'book' => 'library',
        'periodical' => 'library',
        'volume' => 'library',
        'pamphlet' => 'library',
        'monograph' => 'library',
        'article' => 'library',
        'manuscript' => 'library',
        'journal' => 'library',

        // DAM
        'photograph' => 'dam',
        'photo' => 'dam',
        'image' => 'dam',
        'negative' => 'dam',
        'album' => 'dam',
        'slide' => 'dam',
        'video' => 'dam',
        'audio' => 'dam',
        'film' => 'dam',
        'map' => 'dam',
        'poster' => 'dam',

        // Universal
        'collection' => 'universal',
    ];

    /**
     * Detect the GLAM domain type for an object (cached or computed).
     */
    public static function detect(int $objectId): string
    {
        if ($objectId <= 1) {
            return 'archive';
        }

        $existing = DB::table('display_object_config')
            ->where('object_id', $objectId)
            ->value('object_type');

        if ($existing) {
            return $existing;
        }

        return self::detectAndSave($objectId);
    }

    /**
     * Detect and persist the GLAM domain type. Optionally force re-detection.
     */
    public static function detectAndSave(int $objectId, bool $force = false): string
    {
        if ($objectId <= 1) {
            return 'archive';
        }

        if ($force) {
            DB::table('display_object_config')->where('object_id', $objectId)->delete();
        }

        $culture = app()->getLocale();
        $object = DB::table('information_object as io')
            ->leftJoin('term_i18n as level', function ($j) use ($culture) {
                $j->on('io.level_of_description_id', '=', 'level.id')->where('level.culture', '=', $culture);
            })
            ->where('io.id', $objectId)
            ->select('io.*', 'level.name as level_name')
            ->first();

        if (!$object) {
            return 'archive';
        }

        $type = self::detectByLevel($object->level_name)
            ?? self::detectByParent($object->parent_id)
            ?? self::detectByEvents($objectId, $culture)
            ?? self::detectByMediaType($objectId, $culture)
            ?? 'archive';

        self::saveType($objectId, $type);

        return $type;
    }

    /**
     * Detect domain from level-of-description name.
     */
    protected static function detectByLevel(?string $levelName): ?string
    {
        if (!$levelName) {
            return null;
        }
        $level = strtolower(trim($levelName));
        return self::$levelToDomain[$level] ?? null;
    }

    /**
     * Detect domain from parent object's cached type (up to grandparent).
     */
    protected static function detectByParent(?int $parentId): ?string
    {
        if (!$parentId || $parentId <= 1) {
            return null;
        }

        $parentType = DB::table('display_object_config')
            ->where('object_id', $parentId)
            ->value('object_type');

        if ($parentType && $parentType !== 'universal') {
            return $parentType;
        }

        $grandparentId = DB::table('information_object')
            ->where('id', $parentId)
            ->value('parent_id');

        if ($grandparentId && $grandparentId > 1) {
            return DB::table('display_object_config')
                ->where('object_id', $grandparentId)
                ->value('object_type');
        }

        return null;
    }

    /**
     * Detect domain from event types (photographer -> dam, artist -> gallery, etc.).
     */
    protected static function detectByEvents(int $objectId, string $culture): ?string
    {
        $events = DB::table('event as e')
            ->join('term_i18n as t', function ($j) use ($culture) {
                $j->on('e.type_id', '=', 't.id')->where('t.culture', '=', $culture);
            })
            ->where('e.object_id', $objectId)
            ->pluck('t.name')
            ->map(fn($n) => strtolower($n))
            ->toArray();

        if (in_array('photographer', $events) || in_array('photography', $events)) {
            return 'dam';
        }
        if (in_array('artist', $events) || in_array('painter', $events)) {
            return 'gallery';
        }
        if (in_array('author', $events) || in_array('writer', $events)) {
            return 'library';
        }
        if (in_array('production', $events) || in_array('manufacturer', $events)) {
            return 'museum';
        }

        return null;
    }

    /**
     * Detect domain from digital object media type.
     */
    protected static function detectByMediaType(int $objectId, string $culture): ?string
    {
        $mediaType = DB::table('digital_object as dobj')
            ->join('term_i18n as t', function ($j) use ($culture) {
                $j->on('dobj.media_type_id', '=', 't.id')->where('t.culture', '=', $culture);
            })
            ->where('dobj.object_id', $objectId)
            ->value('t.name');

        if (!$mediaType) {
            return null;
        }

        $mediaToDomain = [
            'image' => 'dam',
            'video' => 'dam',
            'audio' => 'dam',
        ];

        return $mediaToDomain[strtolower($mediaType)] ?? null;
    }

    /**
     * Persist a detected type to the display_object_config table.
     */
    protected static function saveType(int $objectId, string $type): void
    {
        DB::table('display_object_config')->updateOrInsert(
            ['object_id' => $objectId],
            [
                'object_type' => $type,
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
    }

    /**
     * Get display profile for an object (object-specific or domain default).
     */
    public static function getProfile(int $objectId): ?object
    {
        $type = self::detect($objectId);
        $culture = app()->getLocale();

        // Try object-specific profile first
        $profile = DB::table('display_object_profile as dop')
            ->join('display_profile as dp', 'dop.profile_id', '=', 'dp.id')
            ->join('display_profile_i18n as dpi', function ($j) use ($culture) {
                $j->on('dp.id', '=', 'dpi.id')->where('dpi.culture', '=', $culture);
            })
            ->where('dop.object_id', $objectId)
            ->select('dp.*', 'dpi.name', 'dpi.description')
            ->first();

        if (!$profile) {
            // Fall back to domain default profile
            $profile = DB::table('display_profile as dp')
                ->join('display_profile_i18n as dpi', function ($j) use ($culture) {
                    $j->on('dp.id', '=', 'dpi.id')->where('dpi.culture', '=', $culture);
                })
                ->where('dp.domain', $type)
                ->where('dp.is_default', true)
                ->select('dp.*', 'dpi.name', 'dpi.description')
                ->first();
        }

        return $profile;
    }

    /**
     * Alias for detect().
     */
    public static function getType(int $objectId): string
    {
        return self::detect($objectId);
    }
}
