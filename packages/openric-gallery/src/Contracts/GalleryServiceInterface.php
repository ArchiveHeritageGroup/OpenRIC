<?php

declare(strict_types=1);

namespace OpenRiC\Gallery\Contracts;

/**
 * Gallery service interface.
 *
 * Adapted from Heratio ahg-gallery GalleryService (837 lines).
 * Full CCO artwork cataloguing: artworks, artists, loans, valuations, venues,
 * facility reports, gallery collections, and reporting.
 */
interface GalleryServiceInterface
{
    // =========================================================================
    // Gallery Collections (curated entity groups)
    // =========================================================================

    /**
     * Get all gallery collections with optional filtering.
     *
     * @return array{galleries: array[], total: int}
     */
    public function getGalleries(array $params = []): array;

    /**
     * Get a single gallery collection by ID with items.
     */
    public function getGallery(int $id): ?array;

    /**
     * Create a new gallery collection.
     */
    public function createGallery(array $data): int;

    /**
     * Update a gallery collection.
     */
    public function updateGallery(int $id, array $data): void;

    /**
     * Delete a gallery collection and its items.
     */
    public function deleteGallery(int $id): void;

    /**
     * Get items in a gallery collection ordered by sort_order.
     *
     * @return array[]
     */
    public function getGalleryItems(int $galleryId): array;

    /**
     * Add an entity to a gallery collection.
     */
    public function addItem(int $galleryId, string $entityIri, string $entityType, string $title, ?string $thumbnail = null): int;

    /**
     * Remove an item from a gallery collection.
     */
    public function removeItem(int $itemId): void;

    /**
     * Reorder items in a gallery collection.
     *
     * @param  array<int, int> $itemOrder itemId => sort_order
     */
    public function reorderItems(int $galleryId, array $itemOrder): void;

    /**
     * Get featured gallery collections for public display.
     *
     * @return array[]
     */
    public function getFeaturedGalleries(int $limit = 10): array;

    // =========================================================================
    // Artwork CRUD (CCO cataloguing via information_object + museum_metadata)
    // =========================================================================

    /**
     * Get a single gallery artwork by its slug.
     * Joins information_object + i18n + slug + museum_metadata + display_object_config.
     */
    public function getBySlug(string $slug, string $culture = 'en'): ?object;

    /**
     * Browse gallery artworks with pagination, search, sort, filters.
     *
     * @return array{results: array[], total: int, page: int, limit: int, repositoryNames: array}
     */
    public function browse(array $params, string $culture = 'en'): array;

    /**
     * Create a new gallery artwork: IO + i18n + museum_metadata + display_object_config + slug.
     */
    public function createArtwork(array $data, string $culture = 'en'): string;

    /**
     * Update a gallery artwork.
     */
    public function updateArtwork(string $slug, array $data, string $culture = 'en'): void;

    /**
     * Delete a gallery artwork and all associated records.
     */
    public function deleteArtwork(string $slug): void;

    // =========================================================================
    // Artists
    // =========================================================================

    /**
     * Get all gallery artists with pagination/search.
     *
     * @return array{results: array[], total: int, page: int, limit: int}
     */
    public function getArtists(array $params = []): array;

    /**
     * Get a single artist by ID with related artworks.
     */
    public function getArtist(int $id): ?object;

    /**
     * Create a new gallery artist.
     */
    public function createArtist(array $data): int;

    /**
     * Update a gallery artist.
     */
    public function updateArtist(int $id, array $data): void;

    /**
     * Delete a gallery artist.
     */
    public function deleteArtist(int $id): void;

    // =========================================================================
    // Form Helpers
    // =========================================================================

    /**
     * Get dropdown choices for gallery artwork forms.
     *
     * @return array{levels: \Illuminate\Support\Collection, repositories: \Illuminate\Support\Collection, workTypes: string[], creatorRoles: string[], artistTypes: string[]}
     */
    public function getFormChoices(string $culture = 'en'): array;

    /**
     * Get extra data needed for the edit form: physical location, display standards.
     */
    public function getEditExtras(?int $objectId, string $culture): array;

    // =========================================================================
    // Dashboard Stats
    // =========================================================================

    /**
     * Get dashboard statistics.
     *
     * @return array{totalItems: int, itemsWithMedia: int, totalArtists: int, activeLoans: int, recentItems: \Illuminate\Support\Collection}
     */
    public function getDashboardStats(): array;

    /**
     * Get reporting statistics.
     *
     * @return array{exhibitions: array, loans: array, valuations: array}
     */
    public function getReportStats(): array;
}
