<?php

declare(strict_types=1);

namespace OpenRiC\DoiManage\Contracts;

use Illuminate\Support\Collection;

/**
 * DOI management service interface -- adapted from Heratio AhgDoiManage\Controllers\DoiController (360 lines).
 *
 * Integrates with the DataCite REST API to mint, resolve, and manage DOIs for archival entities.
 * Configuration stored in the settings table (group='doi').
 */
interface DoiServiceInterface
{
    /**
     * Mint a new DOI for an entity IRI via the DataCite API.
     *
     * @return array{success: bool, doi?: string, error?: string}
     */
    public function mintDoi(string $entityIri, string $title, array $metadata = []): array;

    /**
     * Resolve a DOI to its entity IRI.
     */
    public function resolveDoi(string $doi): ?string;

    /**
     * Get the DOI assigned to an entity, if any.
     */
    public function getDoiForEntity(string $entityIri): ?object;

    /**
     * Browse all entities that have DOIs assigned.
     *
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function getEntitiesWithDoi(array $params = []): array;

    /**
     * Update DOI metadata at DataCite.
     *
     * @return array{success: bool, error?: string}
     */
    public function updateDoiMetadata(string $doi, array $metadata): array;

    /**
     * Get DOI statistics.
     *
     * @return array{total: int, findable: int, registered: int, draft: int}
     */
    public function getStats(): array;
}
