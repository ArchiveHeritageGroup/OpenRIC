<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Contracts;

/**
 * AI Evaluation Dashboard — Module 4.
 *
 * Computes and tracks AI performance metrics.
 */
interface AiEvaluationServiceInterface
{
    /**
     * Compute metrics for a use case over a date range.
     */
    public function computeMetrics(string $useCase, string $periodStart, string $periodEnd): array;

    /**
     * Save computed metrics snapshot.
     */
    public function saveMetrics(array $data): int;

    /**
     * Get stored metrics snapshots.
     */
    public function listMetrics(array $filters = [], int $limit = 25, int $offset = 0): array;

    /**
     * Get the latest metrics for each use case.
     */
    public function getLatestMetrics(): array;

    /**
     * Get metrics trend over time for a use case.
     */
    public function getMetricsTrend(string $useCase, int $months = 12): array;

    /**
     * Get dashboard summary (all use cases, current period).
     */
    public function getDashboardSummary(): array;

    /**
     * Export metrics as CSV.
     */
    public function exportMetricsCsv(array $filters = []): string;

    /**
     * Get per-model performance breakdown.
     */
    public function getModelPerformance(?string $useCase = null): array;

    // ── Module 7: Multilingual AI Control ───────────────────────────

    public function listLanguageConfigs(): array;
    public function getLanguageConfig(string $languageCode): ?object;
    public function saveLanguageConfig(string $languageCode, array $data): void;
    public function deleteLanguageConfig(string $languageCode): void;
    public function getReviewersByLanguage(string $languageCode): array;
    public function assignReviewer(string $languageCode, int $userId): void;
    public function removeReviewer(string $languageCode, int $userId): void;
}
