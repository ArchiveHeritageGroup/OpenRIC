<?php

declare(strict_types=1);

namespace OpenRiC\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenRiC\Core\Contracts\DropdownServiceInterface;

/**
 * Controlled vocabulary / dropdown management service.
 *
 * Adapted from Heratio AhgSettingsService dropdown section (lines 129-508).
 *
 * Provides:
 *   - Field-to-taxonomy mapping: which form fields use which dropdown
 *   - Validation: ensure submitted values are in the allowed set
 *   - Choices: get [code => label] pairs for <select> rendering
 *   - Label resolution: convert codes to human-readable labels
 *   - Defaults: get the default value for a field
 *   - Statistics: coverage metrics for dropdown management
 *
 * Dropdowns are stored in PostgreSQL 'dropdowns' table. Each dropdown value
 * may optionally reference a SKOS concept IRI in Fuseki (skos_iri column).
 * The 'dropdown_column_map' table maps entity fields to taxonomy names.
 */
class DropdownService implements DropdownServiceInterface
{
    /**
     * Cache TTL in seconds (10 minutes — dropdowns change rarely).
     */
    private const CACHE_TTL = 600;

    /**
     * Cache key prefixes.
     */
    private const CACHE_MAP_PREFIX = 'openric_dropdown_map_';
    private const CACHE_VALUES_PREFIX = 'openric_dropdown_values_';
    private const CACHE_CHOICES_PREFIX = 'openric_dropdown_choices_';

    /**
     * In-memory caches for the current request.
     *
     * @var array<string, ?string>
     */
    private array $mapCache = [];

    /**
     * @var array<string, string[]>
     */
    private array $valuesCache = [];

    /**
     * {@inheritDoc}
     */
    public function getTaxonomy(string $entityType, string $fieldName): ?string
    {
        $cacheKey = "{$entityType}.{$fieldName}";

        if (array_key_exists($cacheKey, $this->mapCache)) {
            return $this->mapCache[$cacheKey];
        }

        $taxonomy = Cache::remember(
            self::CACHE_MAP_PREFIX . $cacheKey,
            self::CACHE_TTL,
            function () use ($entityType, $fieldName): ?string {
                $map = DB::table('dropdown_column_map')
                    ->where('entity_type', $entityType)
                    ->where('field_name', $fieldName)
                    ->first();

                return $map->taxonomy ?? null;
            }
        );

        $this->mapCache[$cacheKey] = $taxonomy;

        return $taxonomy;
    }

    /**
     * {@inheritDoc}
     */
    public function isMapped(string $entityType, string $fieldName): bool
    {
        return $this->getTaxonomy($entityType, $fieldName) !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function isStrict(string $entityType, string $fieldName): bool
    {
        $map = DB::table('dropdown_column_map')
            ->where('entity_type', $entityType)
            ->where('field_name', $fieldName)
            ->first();

        return (bool) ($map->is_strict ?? true);
    }

    /**
     * {@inheritDoc}
     */
    public function getMappingsForEntity(string $entityType): array
    {
        return DB::table('dropdown_column_map')
            ->where('entity_type', $entityType)
            ->get()
            ->keyBy('field_name')
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function isValid(string $entityType, string $fieldName, ?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $taxonomy = $this->getTaxonomy($entityType, $fieldName);
        if ($taxonomy === null) {
            return true; // Unmapped fields accept anything
        }

        $values = $this->getValidValues($taxonomy);

        if (in_array($value, $values, true)) {
            return true;
        }

        // Non-strict fields accept values not in the dropdown
        if (!$this->isStrict($entityType, $fieldName)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isValidForTaxonomy(string $taxonomy, ?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return in_array($value, $this->getValidValues($taxonomy), true);
    }

    /**
     * {@inheritDoc}
     */
    public function getValidValues(string $taxonomy): array
    {
        if (isset($this->valuesCache[$taxonomy])) {
            return $this->valuesCache[$taxonomy];
        }

        $values = Cache::remember(
            self::CACHE_VALUES_PREFIX . $taxonomy,
            self::CACHE_TTL,
            function () use ($taxonomy): array {
                return DB::table('dropdowns')
                    ->where('taxonomy', $taxonomy)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->pluck('code')
                    ->all();
            }
        );

        $this->valuesCache[$taxonomy] = $values;

        return $values;
    }

    /**
     * {@inheritDoc}
     */
    public function validateRow(string $entityType, array $row): array
    {
        $errors = [];
        $mappings = $this->getMappingsForEntity($entityType);

        foreach ($mappings as $fieldName => $map) {
            if (!isset($row[$fieldName]) || $row[$fieldName] === null || $row[$fieldName] === '') {
                continue;
            }

            $value = $row[$fieldName];
            $validValues = $this->getValidValues($map->taxonomy);

            if (!in_array($value, $validValues, true) && $map->is_strict) {
                $errors[$fieldName] = [
                    'value' => $value,
                    'taxonomy' => $map->taxonomy,
                    'valid_values' => $validValues,
                ];
            }
        }

        return $errors;
    }

    /**
     * {@inheritDoc}
     */
    public function getChoicesForField(string $entityType, string $fieldName, bool $includeEmpty = true): array
    {
        $taxonomy = $this->getTaxonomy($entityType, $fieldName);
        if ($taxonomy === null) {
            return [];
        }

        return $this->getChoices($taxonomy, $includeEmpty);
    }

    /**
     * {@inheritDoc}
     */
    public function getChoices(string $taxonomy, bool $includeEmpty = true): array
    {
        $cacheKey = self::CACHE_CHOICES_PREFIX . $taxonomy . ($includeEmpty ? '_e' : '_n');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($taxonomy, $includeEmpty): array {
            $choices = $includeEmpty ? ['' => ''] : [];

            $terms = DB::table('dropdowns')
                ->where('taxonomy', $taxonomy)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('label')
                ->select(['code', 'label'])
                ->get();

            foreach ($terms as $term) {
                $choices[$term->code] = $term->label;
            }

            return $choices;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getChoicesWithAttributes(string $taxonomy): array
    {
        return DB::table('dropdowns')
            ->where('taxonomy', $taxonomy)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->select(['id', 'code', 'label', 'color', 'icon', 'sort_order', 'is_default', 'skos_iri', 'metadata'])
            ->get()
            ->keyBy('code')
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function resolveLabel(string $entityType, string $fieldName, ?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        $taxonomy = $this->getTaxonomy($entityType, $fieldName);
        if ($taxonomy === null) {
            return $code;
        }

        return $this->resolveLabelForTaxonomy($taxonomy, $code);
    }

    /**
     * {@inheritDoc}
     */
    public function resolveLabelForTaxonomy(string $taxonomy, ?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        $label = DB::table('dropdowns')
            ->where('taxonomy', $taxonomy)
            ->where('code', $code)
            ->value('label');

        return $label ?? $code;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveColor(string $taxonomy, string $code): ?string
    {
        return DB::table('dropdowns')
            ->where('taxonomy', $taxonomy)
            ->where('code', $code)
            ->value('color');
    }

    /**
     * {@inheritDoc}
     */
    public function getDefault(string $entityType, string $fieldName): ?string
    {
        $taxonomy = $this->getTaxonomy($entityType, $fieldName);
        if ($taxonomy === null) {
            return null;
        }

        return $this->getDefaultForTaxonomy($taxonomy);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultForTaxonomy(string $taxonomy): ?string
    {
        // First try the explicit default
        $default = DB::table('dropdowns')
            ->where('taxonomy', $taxonomy)
            ->where('is_active', true)
            ->where('is_default', true)
            ->value('code');

        if ($default !== null) {
            return $default;
        }

        // Fall back to the first active value by sort order
        return DB::table('dropdowns')
            ->where('taxonomy', $taxonomy)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->value('code');
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        $totalTaxonomies = DB::table('dropdowns')
            ->select('taxonomy')
            ->distinct()
            ->count('taxonomy');

        $totalValues = DB::table('dropdowns')->count();

        $activeValues = DB::table('dropdowns')
            ->where('is_active', true)
            ->count();

        $mappedFields = DB::table('dropdown_column_map')->count();

        $strictFields = DB::table('dropdown_column_map')
            ->where('is_strict', true)
            ->count();

        return [
            'taxonomies' => $totalTaxonomies,
            'total_values' => $totalValues,
            'active_values' => $activeValues,
            'mapped_fields' => $mappedFields,
            'strict_fields' => $strictFields,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(): void
    {
        $this->mapCache = [];
        $this->valuesCache = [];

        // Clear all taxonomy caches
        $taxonomies = DB::table('dropdowns')
            ->select('taxonomy')
            ->distinct()
            ->pluck('taxonomy');

        foreach ($taxonomies as $taxonomy) {
            Cache::forget(self::CACHE_VALUES_PREFIX . $taxonomy);
            Cache::forget(self::CACHE_CHOICES_PREFIX . $taxonomy . '_e');
            Cache::forget(self::CACHE_CHOICES_PREFIX . $taxonomy . '_n');
        }

        // Clear all map caches
        $mappings = DB::table('dropdown_column_map')->get();
        foreach ($mappings as $map) {
            Cache::forget(self::CACHE_MAP_PREFIX . "{$map->entity_type}.{$map->field_name}");
        }
    }

    // ========================================================================
    // Admin methods — for settings UI
    // ========================================================================

    /**
     * Add a new dropdown value to a taxonomy.
     */
    public function addValue(
        string $taxonomy,
        string $code,
        string $label,
        ?string $color = null,
        ?string $icon = null,
        int $sortOrder = 0,
        bool $isDefault = false,
        ?string $skosIri = null,
        ?array $metadata = null,
    ): int {
        $id = DB::table('dropdowns')->insertGetId([
            'taxonomy' => $taxonomy,
            'code' => $code,
            'label' => $label,
            'color' => $color,
            'icon' => $icon,
            'sort_order' => $sortOrder,
            'is_default' => $isDefault,
            'is_active' => true,
            'skos_iri' => $skosIri,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->clearTaxonomyCache($taxonomy);

        return $id;
    }

    /**
     * Update an existing dropdown value.
     */
    public function updateValue(int $id, array $data): void
    {
        $existing = DB::table('dropdowns')->find($id);
        if (!$existing) {
            return;
        }

        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata']);
        }

        $data['updated_at'] = now();

        DB::table('dropdowns')->where('id', $id)->update($data);

        $this->clearTaxonomyCache($existing->taxonomy);
    }

    /**
     * Deactivate a dropdown value (soft delete).
     */
    public function deactivateValue(int $id): void
    {
        $existing = DB::table('dropdowns')->find($id);
        if (!$existing) {
            return;
        }

        DB::table('dropdowns')
            ->where('id', $id)
            ->update(['is_active' => false, 'updated_at' => now()]);

        $this->clearTaxonomyCache($existing->taxonomy);
    }

    /**
     * Add or update a field-to-taxonomy mapping.
     */
    public function mapField(string $entityType, string $fieldName, string $taxonomy, bool $isStrict = true): void
    {
        DB::table('dropdown_column_map')->updateOrInsert(
            ['entity_type' => $entityType, 'field_name' => $fieldName],
            [
                'taxonomy' => $taxonomy,
                'is_strict' => $isStrict,
                'updated_at' => now(),
            ]
        );

        unset($this->mapCache["{$entityType}.{$fieldName}"]);
        Cache::forget(self::CACHE_MAP_PREFIX . "{$entityType}.{$fieldName}");
    }

    /**
     * Remove a field-to-taxonomy mapping.
     */
    public function unmapField(string $entityType, string $fieldName): void
    {
        DB::table('dropdown_column_map')
            ->where('entity_type', $entityType)
            ->where('field_name', $fieldName)
            ->delete();

        unset($this->mapCache["{$entityType}.{$fieldName}"]);
        Cache::forget(self::CACHE_MAP_PREFIX . "{$entityType}.{$fieldName}");
    }

    /**
     * Get all taxonomies with their value counts.
     *
     * @return array<string, int>
     */
    public function getTaxonomies(): array
    {
        return DB::table('dropdowns')
            ->select('taxonomy', DB::raw('COUNT(*) as count'))
            ->where('is_active', true)
            ->groupBy('taxonomy')
            ->orderBy('taxonomy')
            ->pluck('count', 'taxonomy')
            ->toArray();
    }

    /**
     * Clear cache for a specific taxonomy.
     */
    private function clearTaxonomyCache(string $taxonomy): void
    {
        unset($this->valuesCache[$taxonomy]);
        Cache::forget(self::CACHE_VALUES_PREFIX . $taxonomy);
        Cache::forget(self::CACHE_CHOICES_PREFIX . $taxonomy . '_e');
        Cache::forget(self::CACHE_CHOICES_PREFIX . $taxonomy . '_n');
    }
}
