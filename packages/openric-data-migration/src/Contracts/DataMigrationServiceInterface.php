<?php

declare(strict_types=1);

namespace OpenRiC\DataMigration\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Data migration service interface.
 *
 * Adapted from Heratio ahg-data-migration DataMigrationService (1,169 lines).
 * Handles CSV analysis, column mapping, row transformation, validation,
 * batch import, export, preset management, import history, and rollback.
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
    public function importBatch(array $rows, string $targetEntityType, int $jobId, array $options = []): array;

    /**
     * Get all saved field mapping presets.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFieldMappingPresets(): array;

    /**
     * Get a single mapping preset by ID.
     */
    public function getPreset(int $id): ?array;

    /**
     * Save a field mapping preset.
     */
    public function saveFieldMappingPreset(array $data): int;

    /**
     * Delete a mapping preset.
     */
    public function deletePreset(int $id): bool;

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

    /**
     * Get available target fields for a given entity type.
     *
     * @return array<string, string> field_key => label
     */
    public function getTargetFields(string $entityType): array;

    /**
     * Create a migration job record.
     */
    public function createJob(string $sourceFile, string $sourceFormat, string $targetEntityType, array $columnMapping, array $transformRules, int $totalRows): int;

    /**
     * Get a single job by ID.
     */
    public function getJob(int $jobId): ?array;

    /**
     * Update job progress fields.
     */
    public function updateJobProgress(int $jobId, array $data): void;

    /**
     * Cancel a running or pending job.
     */
    public function cancelJob(int $jobId): void;

    /**
     * Get job results (the created entity IDs and metadata).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getJobResults(int $jobId): array;

    /**
     * Export records of a given entity type as a streamed CSV response.
     */
    public function batchExportCsv(string $entityType, array $filters = []): StreamedResponse;

    /**
     * Get record counts for all entity types.
     *
     * @return array<string, int>
     */
    public function getRecordCounts(): array;

    /**
     * Get export column definitions for a given entity type.
     *
     * @return array<string, string> column_key => header_label
     */
    public function getExportColumns(string $entityType): array;

    /**
     * Validate an import file and mappings without executing.
     *
     * @return array{valid: bool, errors: array[], warnings: array[], totalRows: int}
     */
    public function validateImportFile(string $filePath, string $targetType, array $mappings): array;

    /**
     * Get migration statistics (total imports, successful, failed, records migrated).
     *
     * @return array<string, int>
     */
    public function getStats(): array;
}
