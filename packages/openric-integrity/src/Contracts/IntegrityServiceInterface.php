<?php

declare(strict_types=1);

namespace OpenRiC\Integrity\Contracts;

/**
 * Integrity service interface -- adapted from Heratio AhgIntegrity\Controllers\IntegrityController (173 lines).
 *
 * Validates RDF integrity in the Fuseki triplestore:
 * - Entities without titles
 * - Orphan relationships (relations pointing to non-existent entities)
 * - Duplicate IRIs
 * - Entities without rdf:type
 * - Consistency between triplestore and PostgreSQL index
 */
interface IntegrityServiceInterface
{
    /**
     * Run a full integrity check suite. Returns structured results.
     *
     * @return array{run_id: string, started_at: string, completed_at: string, checks: array<string, array{passed: bool, count: int, details: array}>}
     */
    public function runChecks(): array;

    /**
     * Get results from a previous check run, or the most recent run if no ID given.
     *
     * @return array{run_id: string, started_at: string, completed_at: ?string, checks: array}|null
     */
    public function getResults(?string $runId = null): ?array;

    /**
     * Get aggregate stats across all runs.
     *
     * @return array{total_runs: int, last_run: ?string, pass_rate: float, open_issues: int}
     */
    public function getStats(): array;
}
