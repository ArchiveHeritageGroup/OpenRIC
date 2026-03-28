<?php

declare(strict_types=1);

namespace OpenRiC\Accession\Contracts;

/**
 * Contract for accession management.
 *
 * Adapted from Heratio AccessionService + AccessionBrowseService (combined ~720 lines).
 * Accessions are operational data stored in PostgreSQL, not RDF entities.
 * Links to archival records use IRIs referencing Fuseki entities.
 */
interface AccessionServiceInterface
{
    /**
     * Browse accessions with filters, sorting, and pagination.
     *
     * @param  array{
     *     page?: int,
     *     limit?: int,
     *     sort?: string,
     *     sortDir?: string,
     *     subquery?: string,
     *     status?: string
     * } $params
     * @return array{hits: array<int, array<string, mixed>>, total: int, page: int, limit: int}
     */
    public function browse(array $params): array;

    /**
     * Find an accession by ID.
     *
     * @param  int $id  The accession primary key
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array;

    /**
     * Create a new accession.
     *
     * @param  array<string, mixed> $data    Accession field values
     * @param  int                  $userId  Creating user ID
     * @return int  The new accession ID
     */
    public function create(array $data, int $userId): int;

    /**
     * Update an existing accession.
     *
     * @param  int                  $id    The accession ID
     * @param  array<string, mixed> $data  Updated field values
     */
    public function update(int $id, array $data): void;

    /**
     * Delete an accession and its items.
     *
     * @param  int $id  The accession ID
     */
    public function delete(int $id): void;

    /**
     * Get recent accessions for dashboard display.
     *
     * @param  int $limit  Maximum number of results
     * @return array<int, array<string, mixed>>
     */
    public function getRecentAccessions(int $limit = 10): array;

    /**
     * Get accession statistics for dashboard.
     *
     * @return array{
     *     total: int,
     *     byStatus: array<string, int>,
     *     recentCount: int,
     *     totalItems: int
     * }
     */
    public function getAccessionStats(): array;

    /**
     * Link an accession to a record IRI in the triplestore.
     *
     * @param  int    $accessionId  The accession ID
     * @param  string $recordIri    The record IRI to link to
     */
    public function linkToRecord(int $accessionId, string $recordIri): void;

    /**
     * Get all record IRIs linked to an accession.
     *
     * @param  int $accessionId  The accession ID
     * @return array<int, array{iri: string, description: string}>
     */
    public function getLinkedRecords(int $accessionId): array;

    /**
     * Generate the next accession number.
     *
     * @param  string $prefix  Optional prefix (default: current year)
     * @return string  Generated accession number like "2026-001"
     */
    public function generateAccessionNumber(string $prefix = ''): string;
}
