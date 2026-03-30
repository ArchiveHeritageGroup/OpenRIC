<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for AI/NER entity extraction and review operations.
 * Adapted from Heratio AiNerService.
 */
interface AiNerServiceInterface
{
    public function getEntitiesForRecord(int $recordId): Collection;
    public function getPendingExtractions(): Collection;
    public function getExtraction(int $id): ?object;
    public function getEntityLinks(int $recordId): Collection;
    public function getUsageStats(): object;
    public function approveEntity(int $entityId, ?int $reviewedBy = null): bool;
    public function rejectEntity(int $entityId, ?int $reviewedBy = null): bool;
    public function getExtractionHistory(int $recordId): Collection;
    public function getPendingCount(): int;
    public function findMatchingAgents(string $entityValue): array;
}
