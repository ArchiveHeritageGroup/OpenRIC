<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Contracts;

/**
 * AI Readiness Profile + Bias Register + Derivative Packaging + Readiness Checklist.
 *
 * Covers Modules 1, 5, 6, and 8 of the AI Preparedness Control Framework.
 */
interface AiReadinessServiceInterface
{
    // ── Module 1: Readiness Profiles ────────────────────────────────

    public function getProfile(int $id): ?object;
    public function getProfileByCollection(string $collectionIri): ?object;
    public function listProfiles(array $filters = [], int $limit = 25, int $offset = 0): array;
    public function createProfile(array $data): int;
    public function updateProfile(int $id, array $data): void;
    public function deleteProfile(int $id): void;
    public function getCompletenessOptions(): array;

    // ── Module 5: Bias Register ─────────────────────────────────────

    public function listBiasEntries(array $filters = [], int $limit = 25, int $offset = 0): array;
    public function getBiasEntry(int $id): ?object;
    public function createBiasEntry(array $data): int;
    public function updateBiasEntry(int $id, array $data): void;
    public function resolveBiasEntry(int $id, int $resolvedBy, string $notes): void;
    public function deleteBiasEntry(int $id): void;
    public function getBiasStats(): array;
    public function getRiskTypes(): array;
    public function getSeverityLevels(): array;
    public function getBiasEntriesForEntity(string $entityIri): array;
    public function getAiWarningsForEntity(string $entityIri): array;

    // ── Module 6: Derivative Packaging ──────────────────────────────

    public function listDerivatives(array $filters = [], int $limit = 25, int $offset = 0): array;
    public function getDerivative(int $id): ?object;
    public function createDerivative(array $data): int;
    public function updateDerivative(int $id, array $data): void;
    public function deleteDerivative(int $id): void;
    public function getDerivativesForEntity(string $entityIri): array;
    public function getCurrentDerivative(string $entityIri, string $derivativeType): ?object;
    public function getDerivativeTypes(): array;
    public function getDerivativeFormats(): array;
    public function getDerivativeStats(): array;

    // ── Module 8: Readiness Checklist ───────────────────────────────

    public function listChecklists(array $filters = [], int $limit = 25, int $offset = 0): array;
    public function getChecklist(int $id): ?object;
    public function createChecklist(array $data): int;
    public function updateChecklist(int $id, array $data): void;
    public function deleteChecklist(int $id): void;
    public function approveChecklist(int $id, int $approvedBy): void;
    public function computeChecklistAutoChecks(int $id): array;
    public function getChecklistStatusOptions(): array;
}
