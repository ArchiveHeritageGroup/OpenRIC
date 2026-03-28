<?php

declare(strict_types=1);

namespace OpenRiC\Audit\Contracts;

use Illuminate\Support\Collection;

/**
 * Audit service interface — adapted from Heratio AuditTrailController (489 lines).
 *
 * All Heratio audit actions mapped to service methods:
 * - Logging: log, logCreate, logUpdate, logDelete, logAuth, logWithContext, logError, logAccessDenied, logDownload
 * - Browsing: browse, find, show
 * - Statistics: getStatistics, getDailyActionCounts, getActionBreakdown
 * - History: getEntityHistory, getUserActivity
 * - Authentication: getAuthenticationLogs
 * - Comparison: compareData
 * - Export: export
 * - Settings: getSettings, saveSettings
 */
interface AuditServiceInterface
{
    // Core logging
    public function log(string $action, array $data): void;
    public function logCreate(string $entityType, string $entityId, ?string $entityTitle = null, ?array $newValues = null): void;
    public function logUpdate(string $entityType, string $entityId, ?string $entityTitle = null, ?array $oldValues = null, ?array $newValues = null): void;
    public function logDelete(string $entityType, string $entityId, ?string $entityTitle = null, ?array $oldValues = null): void;
    public function logAuth(string $action, ?string $description = null): void;
    public function logWithContext(string $action, array $data, ?float $durationMs = null): void;
    public function logError(string $action, string $errorMessage, array $data = []): void;
    public function logAccessDenied(string $entityType, string $entityId, ?string $reason = null): void;
    public function logDownload(string $entityType, string $entityId, ?string $entityTitle = null, ?string $format = null): void;

    // Browse & search
    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array;
    public function find(int $id): ?array;

    // Statistics
    public function getStatistics(int $days = 30): array;
    public function getDailyActionCounts(int $days = 30): array;
    public function getActionBreakdown(int $days = 30): array;

    // History
    public function getEntityHistory(string $entityId, ?string $entityType = null, int $limit = 200): Collection;
    public function getUserActivity(int $userId, int $limit = 200): array;
    public function getAuthenticationLogs(string $type = 'login', int $limit = 50): array;

    // Comparison
    public function compareData(int $id): ?array;

    // Export
    public function export(array $filters = [], int $limit = 10000): Collection;

    // Settings
    public function getSettings(): array;
    public function saveSettings(array $data): void;
}
