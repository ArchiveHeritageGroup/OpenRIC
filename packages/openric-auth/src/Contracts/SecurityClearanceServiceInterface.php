<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Contracts;

use OpenRiC\Auth\Models\SecurityClassification;

interface SecurityClearanceServiceInterface
{
    public function getUserClearance(int $userId): ?SecurityClassification;

    public function getUserClearanceLevel(int $userId): int;

    public function canAccessObject(int $userId, string $objectIri): bool;

    public function grantClearance(int $userId, int $classificationId, int $grantedBy, ?string $expiresAt = null, ?string $notes = null): bool;

    public function revokeClearance(int $userId, int $revokedBy, ?string $notes = null): bool;

    public function classifyObject(string $objectIri, int $classificationId, int $userId, ?string $reason = null): bool;

    public function getObjectClassification(string $objectIri): ?SecurityClassification;
}
