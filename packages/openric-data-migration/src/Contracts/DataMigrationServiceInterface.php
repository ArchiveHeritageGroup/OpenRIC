<?php

declare(strict_types=1);

namespace OpenRiC\DataMigration\Contracts;

/**
 * Data migration service interface.
 *
 * Adapted from Heratio ahg-data-migration (1,542 lines).
 * Handles CSV import, column mapping, row transformation, validation, batch import, and rollback.
 */
interface DataMigrationServiceInterface
{
    /**
     * Analyze a CSV file: return headers, row count, preview rows, and detected column types.
     *
     * @return array{headers: string[], totalRows: int, rows: array[], columnTypes: array<string, string>}
     */
    public function analyzeCsv(string $filePath, int $previewRows = 10): array;

    /**
     * Map source CSV columns to target entity fields using a mapping array.
     *
     * @param  array<string, string> $columnMapping source_column => target_field
     * @param  array<int, array<string, string>> $rows raw CSV rows
     * @return array<int, array<string, mixed>> mapped rows
     */
    public function mapColumns(array $columnMapping, array $rows): array;

    /**
     * Transform a single row: apply transform rules (trim, date format, regex, default values).
     *
     * @param  array<string, mixed> $row mapped field => value
     * @param  array<string, array<string, mixed>> $transformRules field => {type, params}
     * @return array<string, mixed> transformed row
     */
    public function transformRow(array $row, array $transformRules): array;

    /**
     * Validate a single row against the target entity type requirements.
     *
     * @return array{valid: bool, errors: string[]}
     */
    public function validateRow(array $row, string $targetEntityType): array;

    /**
     * Import a batch of rows into the database, creating entities of the target type.
     *
     * @param  array<int, array<string, mixed>> $rows
     * @return array{imported: int, updated: int, skipped: int, errors: array[]}
     */
    public function importBatch(array $rows, string $targetEntityType, int $jobId): array;

    /**
     * Get all saved field mapping presets.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFieldMappingPresets(): array;

    /**
     * Save a field mapping preset.
     */
    public function saveFieldMappingPreset(string $name, string $entityType, array $columnMapping, array $transformRules): int;

    /**
     * Get import job history with pagination.
     *
     * @return array{jobs: array[], total: int}
     */
    public function getImportHistory(int $limit = 50, int $offset = 0): array;

    /**
     * Rollback a completed import job, deleting all entities created by it.
     */
    public function rollbackImport(int $jobId): array;
}
