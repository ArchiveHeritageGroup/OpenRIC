<?php

declare(strict_types=1);

namespace OpenRiC\CustomFields\Contracts;

use Illuminate\Support\Collection;

/**
 * Custom fields service interface -- adapted from Heratio AhgCustomFields\Services\CustomFieldService (84 lines).
 *
 * Allows institutions to define additional fields for any entity type,
 * stored as key-value pairs linked by entity IRI.
 */
interface CustomFieldsServiceInterface
{
    /**
     * Get all field definitions for a given entity type.
     */
    public function getFieldsForEntity(string $entityType): Collection;

    /**
     * Get a single field definition by ID.
     */
    public function getField(int $fieldId): ?object;

    /**
     * Create a new custom field definition.
     */
    public function createField(array $data): int;

    /**
     * Update an existing custom field definition.
     */
    public function updateField(int $fieldId, array $data): bool;

    /**
     * Delete a custom field definition and all its values.
     */
    public function deleteField(int $fieldId): bool;

    /**
     * Get the stored value for a specific field on a specific entity.
     */
    public function getFieldValue(int $fieldId, string $entityIri): ?string;

    /**
     * Set the value for a specific field on a specific entity.
     */
    public function setFieldValue(int $fieldId, string $entityIri, ?string $value): void;

    /**
     * Get all custom field values for a specific entity IRI.
     */
    public function getEntityValues(string $entityIri): Collection;
}
