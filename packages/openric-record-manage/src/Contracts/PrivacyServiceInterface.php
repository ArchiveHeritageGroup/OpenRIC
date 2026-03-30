<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for privacy / PII / POPIA / GDPR operations.
 * Adapted from Heratio PrivacyService.
 */
interface PrivacyServiceInterface
{
    public function getRedactions(int $recordId): Collection;
    public function saveRedaction(array $data): int;
    public function deleteRedaction(int $id): bool;
    public function getDsarRequests(): Collection;
    public function getProcessingActivities(): Collection;
    public function getDashboardStats(): object;
}
