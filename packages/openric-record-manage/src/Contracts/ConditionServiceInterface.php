<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for condition report and assessment operations.
 * Adapted from Heratio ConditionService.
 */
interface ConditionServiceInterface
{
    public function getReportsForRecord(int $recordId): Collection;
    public function getLatestReport(int $recordId): ?object;
    public function getReport(int $reportId): ?object;
    public function getDamages(int $reportId): Collection;
    public function createReport(array $data): int;
    public function updateReport(int $id, array $data): bool;
    public function deleteReport(int $id): bool;
    public function addDamage(int $reportId, array $data): int;
    public function getRatingOptions(): array;
    public function getContextOptions(): array;
    public function getPriorityOptions(): array;
    public function getDamageTypeOptions(): array;
    public function getSeverityOptions(): array;
}
