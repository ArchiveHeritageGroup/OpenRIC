<?php

declare(strict_types=1);

namespace OpenRiC\Reports\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reporting service providing SPARQL-based stats and CSV export.
 *
 * Adapted from Heratio ahg-reports ReportService (483 lines).
 */
interface ReportServiceInterface
{
    /**
     * Get dashboard statistics: counts by RiC-O type.
     *
     * @return array<string, int>
     */
    public function getDashboardStats(): array;

    /**
     * Get creation statistics by period.
     *
     * @return array<string, mixed>
     */
    public function getCreationStats(string $period = 'month'): array;

    /**
     * Get access/embargo statistics.
     *
     * @return array<string, mixed>
     */
    public function getAccessStats(): array;

    /**
     * Get user activity statistics.
     *
     * @return array<string, mixed>
     */
    public function getUserStats(): array;

    /**
     * Get collection-level statistics.
     *
     * @return array<string, mixed>
     */
    public function getCollectionStats(): array;

    /**
     * Get search analytics.
     *
     * @return array<string, mixed>
     */
    public function getSearchStats(): array;

    /**
     * Export a report as CSV.
     */
    public function exportReport(string $type, array $data, string $filename): StreamedResponse;
}
