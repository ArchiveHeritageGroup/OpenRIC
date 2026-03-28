<?php

declare(strict_types=1);

namespace OpenRiC\Ingest\Contracts;

use Illuminate\Support\Collection;

/**
 * Ingest service interface -- adapted from Heratio AhgIngest\Services\IngestService (130 lines).
 *
 * Supports CSV import (with column-to-RiC-O mapping) and XML import (EAD3, EAC-CPF).
 */
interface IngestServiceInterface
{
    /**
     * Import a CSV file: parse rows, apply column mapping, create entities.
     *
     * @return array{job_id: int, total_rows: int, message: string}
     */
    public function importCsv(string $filepath, array $columnMapping, int $createdBy, array $options = []): array;

    /**
     * Import an XML file (EAD3 or EAC-CPF).
     *
     * @return array{job_id: int, total_rows: int, message: string}
     */
    public function importXml(string $filepath, string $format, int $createdBy, array $options = []): array;

    /**
     * Validate an import before committing: check required fields, IRI conflicts, etc.
     *
     * @return array{valid: bool, errors: array, warnings: array, row_count: int}
     */
    public function validateImport(int $jobId): array;

    /**
     * Get import history with pagination.
     *
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function getImportHistory(int $page = 1, int $limit = 20): array;

    /**
     * Get aggregate import stats.
     *
     * @return array{total_jobs: int, total_imported: int, total_failed: int, formats: array}
     */
    public function getImportStats(): array;
}
