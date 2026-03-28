<?php

declare(strict_types=1);

namespace OpenRiC\Dedupe\Contracts;

/**
 * Contract for duplicate detection and resolution.
 *
 * Adapted from Heratio DedupeController (741 lines) which combined
 * service and controller logic. OpenRiC separates concerns into a
 * proper service interface with SPARQL-based similarity detection.
 */
interface DedupeServiceInterface
{
    /**
     * Find duplicate records using SPARQL-based similarity matching.
     *
     * Compares title, identifier, and dates across rico:RecordSet entities.
     *
     * @param  array{
     *     threshold?: float,
     *     entityType?: string,
     *     limit?: int
     * } $params
     * @return array<int, array{entity_a_iri: string, entity_b_iri: string, similarity_score: float, match_fields: array<string, mixed>}>
     */
    public function findDuplicates(array $params = []): array;

    /**
     * Find duplicate agents using SPARQL-based similarity matching.
     *
     * Compares name, identifier, and dates of existence across rico:Agent entities.
     *
     * @param  array{
     *     threshold?: float,
     *     limit?: int
     * } $params
     * @return array<int, array{entity_a_iri: string, entity_b_iri: string, similarity_score: float, match_fields: array<string, mixed>}>
     */
    public function findDuplicateAgents(array $params = []): array;

    /**
     * Compare a pair of entities side-by-side.
     *
     * @param  string $entityAIri  First entity IRI
     * @param  string $entityBIri  Second entity IRI
     * @return array{
     *     entityA: array<string, mixed>,
     *     entityB: array<string, mixed>,
     *     comparison: array<int, array{label: string, a: string, b: string, match: bool}>,
     *     similarityScore: float
     * }
     */
    public function comparePair(string $entityAIri, string $entityBIri): array;

    /**
     * Merge two records, keeping one as canonical and transferring
     * relationships from the other.
     *
     * @param  string $canonicalIri   IRI of the record to keep
     * @param  string $duplicateIri   IRI of the record to merge into the canonical
     * @param  string $userId         User IRI for provenance
     * @return bool  True if merge succeeded
     */
    public function mergeRecords(string $canonicalIri, string $duplicateIri, string $userId): bool;

    /**
     * Get deduplication statistics.
     *
     * @return array{
     *     totalDetected: int,
     *     pending: int,
     *     merged: int,
     *     notDuplicate: int
     * }
     */
    public function getStats(): array;

    /**
     * Get pending duplicate candidates with pagination.
     *
     * @param  array{
     *     page?: int,
     *     limit?: int,
     *     status?: string,
     *     entityType?: string,
     *     minScore?: float
     * } $params
     * @return array{hits: array<int, array<string, mixed>>, total: int, page: int, limit: int}
     */
    public function getPendingDuplicates(array $params = []): array;

    /**
     * Resolve a duplicate candidate (mark as not-duplicate or trigger merge).
     *
     * @param  int    $candidateId  The duplicate_candidates row ID
     * @param  string $resolution   'not_duplicate' or 'merged'
     * @param  int    $userId       User ID resolving
     */
    public function resolveDuplicate(int $candidateId, string $resolution, int $userId): void;
}
