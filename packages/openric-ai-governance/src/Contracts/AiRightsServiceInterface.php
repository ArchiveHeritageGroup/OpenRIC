<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Contracts;

/**
 * AI Rights & Restrictions Matrix — Module 2.
 *
 * Machine-readable per-entity and per-collection AI use policies.
 */
interface AiRightsServiceInterface
{
    public function getRestriction(int $id): ?object;
    public function getRestrictionForEntity(string $entityIri): ?object;
    public function getEffectiveRestriction(string $entityIri): object;
    public function listRestrictions(array $filters = [], int $limit = 25, int $offset = 0): array;
    public function createRestriction(array $data): int;
    public function updateRestriction(int $id, array $data): void;
    public function deleteRestriction(int $id): void;
    public function bulkApply(array $entityIris, array $restrictions): int;

    /**
     * Check if a specific AI operation is allowed for an entity.
     */
    public function isAllowed(string $entityIri, string $operation): bool;

    /**
     * Get all entities that block a specific AI operation.
     */
    public function getBlockedEntities(string $operation, int $limit = 100): array;

    /**
     * Get the global default restriction (restriction_scope = 'global').
     */
    public function getGlobalDefault(): ?object;

    /**
     * Set or update the global default restriction.
     */
    public function setGlobalDefault(array $data): void;

    public function getOperationTypes(): array;
    public function getScopeTypes(): array;
}
