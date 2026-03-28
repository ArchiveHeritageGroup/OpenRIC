<?php

declare(strict_types=1);

namespace OpenRiC\Heritage\Contracts;

/**
 * Heritage object management via RiC-O triplestore.
 *
 * Adapted from Heratio ahg-heritage-manage (2,956 lines).
 * Heritage objects are rico:Record entities with rico:hasRecordSetType for heritage types.
 */
interface HeritageServiceInterface
{
    /**
     * Browse heritage objects with pagination and optional filters.
     *
     * @param  array{page?: int, limit?: int, type?: string, query?: string, sort?: string} $params
     * @return array{total: int, page: int, pages: int, results: array}
     */
    public function browse(array $params = []): array;

    /**
     * Find a single heritage object by IRI.
     */
    public function find(string $iri): ?array;

    /**
     * Create a new heritage object in the triplestore.
     *
     * @return string  the generated IRI
     */
    public function create(array $data, string $userId): string;

    /**
     * Update an existing heritage object.
     */
    public function update(string $iri, array $data, string $userId): bool;

    /**
     * Delete a heritage object.
     */
    public function delete(string $iri, string $userId): bool;

    /**
     * Get heritage objects by type (e.g. artefact, artwork, document).
     *
     * @return array<int, array>
     */
    public function getByType(string $heritageType, int $limit = 50): array;

    /**
     * Get heritage statistics: counts by type.
     *
     * @return array<string, int>
     */
    public function getStats(): array;

    /**
     * Get custodians (corporate bodies) of heritage objects.
     *
     * @return array<int, array>
     */
    public function getCustodians(): array;

    /**
     * Get analytics data: creation over time, type distribution.
     *
     * @return array<string, mixed>
     */
    public function getAnalytics(): array;
}
