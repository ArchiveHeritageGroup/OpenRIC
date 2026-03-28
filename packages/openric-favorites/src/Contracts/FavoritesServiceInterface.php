<?php

declare(strict_types=1);

namespace OpenRiC\Favorites\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contract for the Favorites service.
 *
 * Adapted from Heratio AhgFavorites\Services\FavoritesService (201 lines).
 * Re-modelled for RiC-O: favourites reference entity_iri (string IRI) instead of integer object IDs.
 */
interface FavoritesServiceInterface
{
    /**
     * Add an entity to the user's favorites.
     *
     * @return bool True if added, false if already favorited.
     */
    public function addFavorite(int $userId, string $entityIri, string $entityType, string $title): bool;

    /**
     * Remove a specific favorite by its database row ID.
     */
    public function removeFavorite(int $userId, int $favoriteId): bool;

    /**
     * Get a paginated list of the user's favorites.
     *
     * @param array{page?: int, limit?: int, sort?: string, sortDir?: string, query?: string, entity_type?: string} $params
     * @return array{results: \Illuminate\Support\Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function getFavorites(int $userId, array $params = []): array;

    /**
     * Check whether an entity IRI is already favorited by the user.
     */
    public function isFavorite(int $userId, string $entityIri): bool;

    /**
     * Count total favorites for a user.
     */
    public function getFavoriteCount(int $userId): int;

    /**
     * Toggle an entity's favorite status. Returns true if now favorited, false if removed.
     */
    public function toggle(int $userId, string $entityIri, string $entityType, string $title): bool;

    /**
     * Remove all favorites for a user.
     *
     * @return int Number of records deleted.
     */
    public function clearAll(int $userId): int;

    /**
     * Export all favorites as a CSV download.
     */
    public function exportCsv(int $userId): StreamedResponse;
}
