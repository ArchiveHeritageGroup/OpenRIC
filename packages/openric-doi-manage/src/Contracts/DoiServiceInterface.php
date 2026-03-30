<?php

declare(strict_types=1);

namespace OpenRiC\DoiManage\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * DOI management service contract -- adapted from Heratio AhgDoiManage\Controllers\DoiController.
 *
 * Integrates with the DataCite REST API to mint, update, deactivate, and
 * resolve DOIs for OpenRiC archival entities.  Configuration is stored in
 * the PostgreSQL 'settings' table (group='doi') and managed via the admin UI.
 */
interface DoiServiceInterface
{
    // =========================================================================
    // Configuration
    // =========================================================================

    /**
     * Load DOI configuration from the settings table with config fallbacks.
     *
     * @return array{
     *     prefix: string,
     *     repository_id: string,
     *     password: string,
     *     url: string,
     *     environment: string,
     *     auto_mint: bool,
     *     publisher: string,
     *     resource_type: string,
     *     suffix_pattern: string,
     *     max_attempts: int,
     * }
     */
    public function getConfig(): array;

    /**
     * Save DOI configuration to the settings table.
     *
     * @param array<string, mixed> $values
     */
    public function saveConfig(array $values): void;

    /**
     * Test the DataCite API connection using current credentials.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array;

    // =========================================================================
    // Dashboard & Stats
    // =========================================================================

    /**
     * Get DOI statistics for the dashboard.
     *
     * @return array{total: int, findable: int, registered: int, draft: int, pending: int, failed: int}
     */
    public function getStats(): array;

    /**
     * Get the most recently minted DOIs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecentDois(int $limit = 10): array;

    // =========================================================================
    // Browse & View
    // =========================================================================

    /**
     * Browse DOIs with status filter and pagination.
     */
    public function browse(array $params = []): LengthAwarePaginator;

    /**
     * Get a single DOI record by primary key with related entity title.
     *
     * @return object|null
     */
    public function find(int $id): ?object;

    /**
     * Get the activity log for a DOI.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActivityLog(int $doiId): array;

    // =========================================================================
    // Minting
    // =========================================================================

    /**
     * Mint a new DOI for an entity via the DataCite API.
     *
     * @return array{success: bool, doi?: string, error?: string}
     */
    public function mintDoi(string $entityIri, string $title, array $metadata = []): array;

    /**
     * Queue multiple entities for batch DOI minting.
     *
     * @param array<int, string> $entityIris
     * @return array{queued: int, skipped: int, errors: array<string, string>}
     */
    public function batchMint(array $entityIris): array;

    // =========================================================================
    // Sync & Deactivate
    // =========================================================================

    /**
     * Synchronise local DOI metadata with DataCite.
     *
     * @return array{success: bool, error?: string}
     */
    public function syncMetadata(int $doiId): array;

    /**
     * Deactivate a DOI (set status to 'registered' at DataCite, mark deleted locally).
     *
     * @return array{success: bool, error?: string}
     */
    public function deactivate(int $doiId, string $reason = ''): array;

    /**
     * Reactivate a previously deactivated DOI.
     *
     * @return array{success: bool, error?: string}
     */
    public function reactivate(int $doiId): array;

    // =========================================================================
    // Queue
    // =========================================================================

    /**
     * Browse the DOI processing queue with status filter and pagination.
     */
    public function browseQueue(array $params = []): LengthAwarePaginator;

    /**
     * Get queue item counts grouped by status.
     *
     * @return array{pending: int, processing: int, failed: int, completed: int}
     */
    public function getQueueCounts(): array;

    /**
     * Retry a failed queue item.
     */
    public function retryQueueItem(int $queueId): bool;

    // =========================================================================
    // Reports & Export
    // =========================================================================

    /**
     * Get monthly minting statistics.
     *
     * @return array<int, array{month: string, minted_count: int, updated_count: int}>
     */
    public function getMonthlyStats(int $months = 24): array;

    /**
     * Get DOI counts grouped by repository.
     *
     * @return array<int, array{repository_name: string, doi_count: int}>
     */
    public function getByRepository(): array;

    /**
     * Export DOIs as an array for CSV/JSON download.
     *
     * @return array<int, array<string, mixed>>
     */
    public function export(array $filters = []): array;
}
