<?php

declare(strict_types=1);

namespace OpenRiC\Rights\Contracts;

/**
 * Rights statement, embargo, and TK Label management.
 *
 * Adapted from Heratio ahg-extended-rights ExtendedRightsService + EmbargoService (1,700+ lines).
 */
interface RightsServiceInterface
{
    /** @return array<int, object> */
    public function getRightsForEntity(string $entityIri): array;

    public function createRightsStatement(array $data): int;

    public function updateRightsStatement(int $id, array $data): bool;

    public function deleteRightsStatement(int $id): bool;

    /** @return array<int, object> */
    public function getEmbargoes(string $entityIri): array;

    public function createEmbargo(array $data): int;

    public function liftEmbargo(int $id, int $userId, ?string $reason = null): bool;

    public function isEmbargoed(string $entityIri): bool;

    /** @return array<int, object> */
    public function getTkLabels(string $entityIri): array;

    public function assignTkLabel(array $data): int;

    public function removeTkLabel(int $id): bool;

    /** @return array<string, int> */
    public function getRightsStats(): array;
}
