<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for extended rights, embargo, TK labels, Creative Commons licenses.
 * Adapted from Heratio ExtendedRightsService.
 */
interface ExtendedRightsServiceInterface
{
    public function getRightsForRecord(int $recordId): Collection;
    public function getExtendedRights(int $recordId): Collection;
    public function getTkLabelsForRights(int $extendedRightsId): Collection;
    public function saveExtendedRight(int $recordId, array $data, ?int $userId = null): int;
    public function updateExtendedRight(int $rightsId, array $data, ?int $userId = null): void;
    public function deleteExtendedRight(int $rightsId): void;
    public function getActiveEmbargo(int $recordId): ?object;
    public function getAllEmbargoes(int $recordId): Collection;
    public function createEmbargo(array $data): int;
    public function liftEmbargo(int $id, int $userId, string $reason): bool;
    public function createEmbargoWithPropagation(array $data, bool $applyToChildren = false): array;
    public function getDescendantCount(int $recordId): int;
    public function getRightsStatements(): Collection;
    public function getCreativeCommonsLicenses(): Collection;
    public function getTkLabels(): Collection;
    public function getDonors(int $limit = 200): Collection;
    public function exportJsonLd(int $recordId): array;
}
