<?php

declare(strict_types=1);

namespace OpenRiC\Dedupe\Contracts;

/**
 * Contract for duplicate detection and resolution.
 *
 * Adapted from Heratio DedupeController (741 lines) which combined
 * service and controller logic. OpenRiC separates concerns into a
 * proper service interface with SPARQL-based similarity detection
 * and PostgreSQL tracking tables (duplicate_candidates, duplicate_rules,
 * dedupe_scans).
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
     *     confirmed: int,
     *     merged: int,
     *     dismissed: int,
     *     notDuplicate: int,
     *     activeRules: int
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
     * Resolve a duplicate candidate (mark as not-duplicate, confirmed, dismissed, or trigger merge).
     *
     * @param  int    $candidateId  The duplicate_candidates row ID
     * @param  string $resolution   'not_duplicate', 'confirmed', 'dismissed', or 'merged'
     * @param  int    $userId       User ID resolving
     */
    public function resolveDuplicate(int $candidateId, string $resolution, int $userId): void;

    /**
     * Check if all required dedupe tables exist.
     */
    public function tablesExist(): bool;

    /**
     * Get all detection rules ordered by priority.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRules(): array;

    /**
     * Get a single detection rule by ID.
     *
     * @return array<string, mixed>|null
     */
    public function getRule(int $id): ?array;

    /**
     * Store a new detection rule.
     *
     * @param  array<string, mixed> $data
     * @return int  The new rule ID
     */
    public function storeRule(array $data): int;

    /**
     * Update an existing detection rule.
     *
     * @param  int                  $id
     * @param  array<string, mixed> $data
     */
    public function updateRule(int $id, array $data): void;

    /**
     * Delete a detection rule.
     */
    public function deleteRule(int $id): void;

    /**
     * Get available rule types with labels.
     *
     * @return array<string, string>
     */
    public function getRuleTypes(): array;

    /**
     * Get all scans ordered by most recent.
     *
     * @param  int $limit
     * @return array<int, array<string, mixed>>
     */
    public function getRecentScans(int $limit = 10): array;

    /**
     * Create a new scan job record.
     *
     * @param  array<string, mixed> $data
     * @return int  The new scan ID
     */
    public function createScan(array $data): int;

    /**
     * Get detection method counts for stats display.
     *
     * @return array<int, array{detection_method: string, total: int}>
     */
    public function getMethodCounts(): array;

    /**
     * Get distinct detection methods.
     *
     * @return array<int, string>
     */
    public function getDistinctMethods(): array;

    /**
     * Get report data: monthly stats, method breakdown, efficiency, top clusters.
     *
     * @return array{
     *     monthlyStats: array<int, array<string, mixed>>,
     *     methodBreakdown: array<int, array<string, mixed>>,
     *     efficiency: array<string, mixed>,
     *     topClusters: array<int, array<string, mixed>>
     * }
     */
    public function getReportData(): array;

    /**
     * Real-time duplicate check for a given title string.
     *
     * @param  string $title     The title to search for
     * @param  int    $maxResults  Maximum matches to return
     * @return array<int, array{iri: string, title: string, identifier: string, similarity_score: float}>
     */
    public function realtimeCheck(string $title, int $maxResults = 10): array;

    /**
     * Get authority records for dashboard stats.
     *
     * @return array{totalCount: int, dupeCount: int, mergedCount: int, avgCompleteness: float}
     */
    public function getAuthorityStats(): array;

    /**
     * Get work queue items (authority issues needing review).
     *
     * @param  array{page?: int, limit?: int, status?: string} $params
     * @return array{hits: array<int, array<string, mixed>>, total: int, page: int, limit: int}
     */
    public function getWorkQueue(array $params = []): array;

    /**
     * Get authority identifiers (external IDs linked to agents).
     *
     * @param  array{page?: int, limit?: int, query?: string} $params
     * @return array{hits: array<int, array<string, mixed>>, total: int, page: int, limit: int}
     */
    public function getAuthorityIdentifiers(array $params = []): array;

    /**
     * Get authority occupations from the triplestore.
     *
     * @param  array{page?: int, limit?: int, query?: string} $params
     * @return array{hits: array<int, array<string, mixed>>, total: int, page: int, limit: int}
     */
    public function getAuthorityOccupations(array $params = []): array;

    /**
     * Get functions (activities/mandates) linked to agents.
     *
     * @param  int $agentId  The agent DB id
     * @return array<int, array<string, mixed>>
     */
    public function getAgentFunctions(int $agentId): array;

    /**
     * Browse all functions across authorities.
     *
     * @param  array{page?: int, limit?: int, query?: string} $params
     * @return array{hits: array<int, array<string, mixed>>, total: int, page: int, limit: int}
     */
    public function browseFunctions(array $params = []): array;

    /**
     * Get a single authority/agent record by ID.
     *
     * @return array<string, mixed>|null
     */
    public function getAuthority(int $id): ?array;

    /**
     * Split an authority record into multiple agents.
     *
     * @param  int                  $authorityId
     * @param  array<int, string>   $splitNames  Names for each resulting agent
     * @param  int                  $userId
     * @return array<int, int>  IDs of newly created agents
     */
    public function splitAuthority(int $authorityId, array $splitNames, int $userId): array;
}
