<?php

declare(strict_types=1);

namespace OpenRiC\LandingPage\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\LandingPage\Contracts\LandingPageServiceInterface;

/**
 * Landing page service -- adapted from Heratio AhgLandingPage\Services\LandingPageService (208 lines).
 *
 * Stores content configuration in the settings table (group='landing_page') rather than
 * Heratio's dedicated ahg_landing_page / ahg_landing_block tables.
 */
class LandingPageService implements LandingPageServiceInterface
{
    private const SETTING_GROUP = 'landing_page';

    private const DEFAULT_WIDGETS = [
        ['key' => 'hero',            'label' => 'Hero Banner',          'enabled' => true,  'position' => 0],
        ['key' => 'stats',           'label' => 'Statistics',           'enabled' => true,  'position' => 1],
        ['key' => 'recent',          'label' => 'Recent Additions',    'enabled' => true,  'position' => 2],
        ['key' => 'featured',        'label' => 'Featured Records',    'enabled' => false, 'position' => 3],
        ['key' => 'search',          'label' => 'Quick Search',        'enabled' => true,  'position' => 4],
        ['key' => 'about',           'label' => 'About Section',       'enabled' => false, 'position' => 5],
    ];

    public function getPageContent(): array
    {
        $rows = DB::table('settings')
            ->where('setting_group', self::SETTING_GROUP)
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        return array_merge([
            'title'            => config('app.name', 'OpenRiC'),
            'subtitle'         => 'Records in Contexts — Archival Management System',
            'hero_image'       => '',
            'about_text'       => '',
            'footer_text'      => '',
            'meta_description' => '',
        ], $rows);
    }

    public function updatePageContent(array $data): void
    {
        $allowedKeys = [
            'title', 'subtitle', 'hero_image', 'about_text',
            'footer_text', 'meta_description',
        ];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedKeys, true)) {
                continue;
            }

            DB::table('settings')->updateOrInsert(
                ['setting_group' => self::SETTING_GROUP, 'setting_key' => $key],
                ['setting_value' => (string) $value, 'updated_at' => now()],
            );
        }
    }

    public function getWidgets(): array
    {
        $stored = DB::table('settings')
            ->where('setting_group', self::SETTING_GROUP)
            ->where('setting_key', 'widgets')
            ->value('setting_value');

        if ($stored) {
            $decoded = json_decode($stored, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }

        return self::DEFAULT_WIDGETS;
    }

    public function reorderWidgets(array $orderedKeys): void
    {
        $widgets = $this->getWidgets();
        $indexed = [];
        foreach ($widgets as $widget) {
            $indexed[$widget['key']] = $widget;
        }

        $reordered = [];
        $position  = 0;
        foreach ($orderedKeys as $key) {
            if (isset($indexed[$key])) {
                $widget             = $indexed[$key];
                $widget['position'] = $position++;
                $reordered[]        = $widget;
                unset($indexed[$key]);
            }
        }

        // Append any widgets not in the provided order
        foreach ($indexed as $widget) {
            $widget['position'] = $position++;
            $reordered[]        = $widget;
        }

        DB::table('settings')->updateOrInsert(
            ['setting_group' => self::SETTING_GROUP, 'setting_key' => 'widgets'],
            ['setting_value' => json_encode($reordered, JSON_THROW_ON_ERROR), 'updated_at' => now()],
        );
    }

    public function getStats(): array
    {
        // Count RiC-O entity types from the triplestore index tables
        $counts = [];

        $entityTables = [
            'RecordResource' => 'record_resources',
            'Agent'          => 'agents',
            'Place'          => 'places',
            'Activity'       => 'activities',
            'Instantiation'  => 'instantiations',
        ];

        foreach ($entityTables as $label => $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                $counts[$label] = DB::table($table)->count();
            } else {
                $counts[$label] = 0;
            }
        }

        // Fallback: count from audit log if entity tables don't exist
        if (array_sum($counts) === 0 && \Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
            $counts['Total Entities'] = DB::table('audit_logs')
                ->where('action', 'create')
                ->distinct('entity_iri')
                ->count('entity_iri');
        }

        // Recent additions (last 10 created entities from audit log)
        $recent = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
            $recent = DB::table('audit_logs')
                ->where('action', 'create')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['entity_iri', 'entity_type', 'summary', 'created_at'])
                ->toArray();
        }

        return [
            'counts' => $counts,
            'recent' => $recent,
        ];
    }
}
