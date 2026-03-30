<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for provenance chain operations.
 * Adapted from Heratio ProvenanceService.
 */
interface ProvenanceServiceInterface
{
    public function getChain(int $recordId): Collection;
    public function getEntry(int $id): ?object;
    public function createEntry(array $data): int;
    public function updateEntry(int $id, array $data): bool;
    public function deleteEntry(int $id): bool;
    public function getTimelineData(int $recordId): string;
    public function getTransferTypes(): array;
    public function getOwnerTypes(): array;
    public function getCertaintyLevels(): array;
}
