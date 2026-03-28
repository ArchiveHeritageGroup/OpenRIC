<?php

declare(strict_types=1);

namespace OpenRiC\Core\Contracts;

/**
 * Controlled vocabulary / dropdown management service.
 *
 * Adapted from Heratio AhgSettingsService dropdown methods (lines 129-508).
 * In OpenRiC, dropdowns can optionally reference SKOS concepts in Fuseki
 * but are cached in PostgreSQL 'dropdowns' table for fast form rendering.
 */
interface DropdownServiceInterface
{
    /**
     * Get the taxonomy name mapped to an entity field.
     */
    public function getTaxonomy(string $entityType, string $fieldName): ?string;

    /**
     * Check if a field is mapped to a dropdown taxonomy.
     */
    public function isMapped(string $entityType, string $fieldName): bool;

    /**
     * Check if a field mapping is strict (only dropdown values allowed).
     */
    public function isStrict(string $entityType, string $fieldName): bool;

    /**
     * Get all field mappings for an entity type.
     *
     * @return array<string, object>
     */
    public function getMappingsForEntity(string $entityType): array;

    /**
     * Validate a value against the dropdown for an entity.field.
     */
    public function isValid(string $entityType, string $fieldName, ?string $value): bool;

    /**
     * Validate a value directly against a taxonomy.
     */
    public function isValidForTaxonomy(string $taxonomy, ?string $value): bool;

    /**
     * Get all valid value codes for a taxonomy.
     *
     * @return string[]
     */
    public function getValidValues(string $taxonomy): array;

    /**
     * Validate multiple field values for an entity.
     *
     * @return array Invalid fields with details (empty if all valid)
     */
    public function validateRow(string $entityType, array $row): array;

    /**
     * Get dropdown choices as [code => label] for an entity.field.
     *
     * @return array<string, string>
     */
    public function getChoicesForField(string $entityType, string $fieldName, bool $includeEmpty = true): array;

    /**
     * Get dropdown choices as [code => label] for a taxonomy.
     *
     * @return array<string, string>
     */
    public function getChoices(string $taxonomy, bool $includeEmpty = true): array;

    /**
     * Get dropdown choices with full attributes (code, label, color, icon, etc.).
     *
     * @return array<string, object>
     */
    public function getChoicesWithAttributes(string $taxonomy): array;

    /**
     * Resolve a dropdown code to its display label for an entity.field.
     */
    public function resolveLabel(string $entityType, string $fieldName, ?string $code): ?string;

    /**
     * Resolve a dropdown code to its display label for a taxonomy.
     */
    public function resolveLabelForTaxonomy(string $taxonomy, ?string $code): ?string;

    /**
     * Resolve a dropdown code to its color for a taxonomy.
     */
    public function resolveColor(string $taxonomy, string $code): ?string;

    /**
     * Get the default value code for an entity.field.
     */
    public function getDefault(string $entityType, string $fieldName): ?string;

    /**
     * Get the default value code for a taxonomy.
     */
    public function getDefaultForTaxonomy(string $taxonomy): ?string;

    /**
     * Get statistics about dropdown coverage.
     *
     * @return array{taxonomies: int, total_values: int, active_values: int, mapped_fields: int, strict_fields: int}
     */
    public function getStats(): array;

    /**
     * Clear all dropdown caches.
     */
    public function clearCache(): void;
}
