<?php

declare(strict_types=1);

namespace OpenRiC\Statistics\Contracts;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Statistics service — usage tracking, dashboard stats, geographic, CSV export.
 *
 * Adapted from Heratio ahg-statistics StatisticsService (187 lines).
 * IRI-based entity references, PostgreSQL with ILIKE, bot filtering.
 */
interface StatisticsServiceInterface
{
    /**
     * Record a usage event (view, download, search).
     *
     * @param array{entity_iri: string, entity_type: string, event_type: string, user_id?: int|null, ip_address?: string, user_agent?: string} $data
     */
    public function recordEvent(array $data): int;

    /**
     * Get dashboard summary: views, downloads, unique visitors, searches.
     *
     * @return array{views: int, downloads: int, searches: int, unique_visitors: int}
     */
    public function getDashboardStats(string $startDate, string $endDate): array;

    /**
     * Get top items by event type (view or download).
     */
    public function getTopItems(string $eventType, int $limit, string $startDate, string $endDate): Collection;

    /**
     * Get geographic breakdown of usage events.
     *
     * @return array<int, object>
     */
    public function getGeographicStats(string $startDate, string $endDate): array;

    /**
     * Get event counts over time, grouped by day/week/month.
     *
     * @return array<int, object>
     */
    public function getEventsOverTime(string $eventType, string $startDate, string $endDate, string $groupBy = 'day'): array;

    /**
     * Get per-entity statistics.
     *
     * @return array{views: int, downloads: int, searches: int, unique_visitors: int, daily: array}
     */
    public function getEntityStats(string $entityIri, string $startDate, string $endDate): array;

    /**
     * Aggregate raw events into daily/monthly summary tables.
     */
    public function aggregateStats(): void;

    /**
     * Export statistics data as CSV.
     */
    public function exportCsv(string $type, string $startDate, string $endDate): StreamedResponse;

    /**
     * Check if a user-agent matches a known bot pattern.
     */
    public function isBot(string $userAgent): bool;

    /**
     * Get the bot pattern list.
     */
    public function getBotList(): Collection;

    /**
     * Add a bot pattern.
     */
    public function addBot(array $data): int;

    /**
     * Delete a bot pattern.
     */
    public function deleteBot(int $id): void;

    /**
     * Get a statistics configuration value.
     */
    public function getConfig(string $key, mixed $default = null): mixed;

    /**
     * Set a statistics configuration value.
     */
    public function setConfig(string $key, mixed $value): void;

    /**
     * Purge raw events older than given number of days.
     */
    public function purgeOldEvents(int $days): int;
}
