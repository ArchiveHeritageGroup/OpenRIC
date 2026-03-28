<?php

declare(strict_types=1);

namespace OpenRiC\Gallery\Contracts;

/**
 * Gallery service interface.
 *
 * Adapted from Heratio ahg-gallery (1,468 lines).
 * Manages curated galleries of RiC entities with ordering, thumbnails, and featured status.
 */
interface GalleryServiceInterface
{
    /**
     * Get all galleries with optional filtering.
     *
     * @return array{galleries: array[], total: int}
     */
    public function getGalleries(array $params = []): array;

    /**
     * Get a single gallery by ID with items.
     */
    public function getGallery(int $id): ?array;

    /**
     * Create a new gallery.
     */
    public function createGallery(array $data): int;

    /**
     * Update a gallery.
     */
    public function updateGallery(int $id, array $data): void;

    /**
     * Delete a gallery and its items.
     */
    public function deleteGallery(int $id): void;

    /**
     * Get items in a gallery ordered by sort_order.
     *
     * @return array[]
     */
    public function getGalleryItems(int $galleryId): array;

    /**
     * Add an entity to a gallery.
     */
    public function addItem(int $galleryId, string $entityIri, string $entityType, string $title, ?string $thumbnail = null): int;

    /**
     * Remove an item from a gallery.
     */
    public function removeItem(int $itemId): void;

    /**
     * Reorder items in a gallery.
     *
     * @param  array<int, int> $itemOrder itemId => sort_order
     */
    public function reorderItems(int $galleryId, array $itemOrder): void;

    /**
     * Get featured galleries for public display.
     *
     * @return array[]
     */
    public function getFeaturedGalleries(int $limit = 10): array;
}
