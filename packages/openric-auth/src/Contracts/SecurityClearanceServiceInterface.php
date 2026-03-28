<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Contracts;

use Illuminate\Support\Collection;

/**
 * Security clearance service interface.
 * Adapted from Heratio SecurityClearanceService (867 lines).
 *
 * Covers: classification levels, user clearances, object classification,
 * compartments, access requests, dashboard statistics, compliance, and audit.
 */
interface SecurityClearanceServiceInterface
{
    // Classification levels
    public function getClassificationLevels(): Collection;
    public function getClassification(int $id): ?object;
    public static function getAllClassifications(): array;

    // User clearance
    public function getUserClearance(int $userId): ?object;
    public function getUserClearanceRecord(int $userId): ?object;
    public function getUserClearanceLevel(int $userId): int;
    public function getAllUsersWithClearances(): array;
    public function grantClearance(int $userId, int $classificationId, int $grantedBy, ?string $expiresAt = null, ?string $notes = null): bool;
    public function revokeClearance(int $userId, int $revokedBy, ?string $notes = null): bool;
    public function bulkGrant(array $userIds, int $classificationId, int $grantedBy, ?string $notes = null): int;
    public function getClearanceHistory(int $userId): array;

    // Object classification (IRI-based for RiC-O)
    public function getObjectClassification(string $objectIri): ?object;
    public function classifyObject(string $objectIri, int $classificationId, int $userId, ?string $reason = null, ?array $compartmentIds = null): bool;
    public function declassifyObject(string $objectIri, int $userId, ?int $newClassificationId = null, ?string $reason = null): bool;
    public function canAccessObject(int $userId, string $objectIri): bool;

    // Compartments
    public function getCompartments(): Collection;
    public function getCompartmentUserCounts(): array;
    public function getCompartmentAccessGrants(): Collection;
    public function grantCompartmentAccess(int $userId, int $compartmentId, int $grantedBy): bool;
    public function revokeCompartmentAccess(int $userId, int $compartmentId): bool;

    // Access requests
    public function getAccessRequests(?string $status = 'pending'): Collection;
    public function submitAccessRequest(int $userId, string $objectIri, string $requestType, string $justification, string $priority = 'normal', ?int $durationHours = 24): bool;
    public function reviewAccessRequest(int $requestId, string $decision, int $reviewerId, ?string $notes = null): bool;
    public function getUserAccessGrants(int $userId): array;
    public function revokeObjectAccess(int $grantId, int $revokedBy): bool;

    // Dashboard & reports
    public function getDashboardStatistics(): array;
    public function getPendingRequests(int $limit = 10): array;
    public function getExpiringClearances(int $limit = 10): array;
    public function getDueDeclassifications(int $limit = 10): array;
    public function getReportStats(string $period = '30 days'): array;
    public function getComplianceStats(): array;
    public function getRecentComplianceLogs(int $limit = 10): array;

    // Audit
    public function logSecurityAudit(int $userId, ?string $objectIri, string $action, array $details = []): void;
    public function getAuditLog(array $filters = [], int $limit = 50, int $offset = 0): array;
    public function exportAuditLog(int $limit = 10000): Collection;
}
