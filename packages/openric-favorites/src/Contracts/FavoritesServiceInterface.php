<?php

declare(strict_types=1);

namespace OpenRiC\Favorites\Contracts;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contract for the Favorites service.
 *
 * Adapted from Heratio AhgFavorites\Services\FavoritesService.
 * Re-modelled for RiC-O: favourites reference entity_iri (string IRI) instead of integer object IDs.
 */
interface FavoritesServiceInterface
{
    /**
     * Add an entity to the user's favorites.
     *
     * @return bool True if added, false if already favorited.
     */
    public function addFavorite(
        int $userId,
        string $entityIri,
        string $entityType,
        string $title,
        ?string $referenceCode = null,
        ?int $folderId = null,
    ): bool;

    /**
     * Remove a specific favorite by its database row ID.
     */
    public function removeFavorite(int $userId, int $favoriteId): bool;

    /**
     * Get a paginated list of the user's favorites.
     *
     * @param array{page?: int, limit?: int, sort?: string, sortDir?: string, query?: string,
     *              entity_type?: string, folder_id?: int, unfiled?: bool, view?: string} $params
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
     * Update notes on a favorite.
     */
    public function updateNotes(int $userId, int $id, string $notes): bool;

    /**
     * Bulk remove favorites by IDs.
     *
     * @return int Number of records deleted.
     */
    public function bulkRemove(int $userId, array $ids): int;

    /**
     * Move favorites to a folder (or unfiled if null).
     *
     * @return int Number of records updated.
     */
    public function moveToFolder(int $userId, array $ids, ?int $folderId): int;

    /**
     * Remove all favorites for a user.
     *
     * @return int Number of records deleted.
     */
    public function clearAll(int $userId): int;

    /**
     * Export favorites as a CSV download.
     */
    public function exportCsv(int $userId, ?int $folderId = null): StreamedResponse;

    /**
     * Export favorites as JSON.
     */
    public function exportJson(int $userId, ?int $folderId = null): JsonResponse;

    /**
     * Import favorites from CSV content (one IRI per line).
     *
     * @return int Number of items imported.
     */
    public function importFromCsv(int $userId, string $content): int;
}
