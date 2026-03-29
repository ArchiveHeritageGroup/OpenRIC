<?php

declare(strict_types=1);

namespace OpenRiC\Display\Contracts;

/**
 * Display service interface.
 *
 * Adapted from Heratio ahg-display (DisplayService + DisplayTypeDetector).
 * Manages GLAM type detection, display profiles, browse settings, faceted search,
 * CSV export, and object display configuration.
 */
interface DisplayServiceInterface
{
    // =========================================================================
    // DisplayService methods
    // =========================================================================

    /**
     * Get full display data for a single object (type, profile, fields).
     *
     * @return array{object: ?object, type: string, profile: ?object, fields: array}
     */
    public function getObjectDisplay(int $objectId): array;

    /**
     * Get raw object data with i18n joins.
     */
    public function getObjectData(int $objectId): ?object;

    /**
     * Get display fields for a given profile.
     *
     * @return array
     */
    public function getFieldsForProfile(?object $profile): array;

    /**
     * Get levels of description, optionally filtered by domain.
     *
     * @return array
     */
    public function getLevels(?string $domain = null): array;

    /**
     * Get collection types.
     *
     * @return array
     */
    public function getCollectionTypes(): array;

    /**
     * Set the GLAM type for a single object.
     */
    public function setObjectType(int $objectId, string $type): void;

    /**
     * Recursively set GLAM type for all children of a parent object.
     *
     * @return int Number of children updated
     */
    public function setObjectTypeRecursive(int $parentId, string $type): int;

    /**
     * Assign a display profile to an object.
     */
    public function assignProfile(int $objectId, int $profileId, string $context = 'default', bool $primary = false): void;

    // =========================================================================
    // DisplayTypeDetector methods
    // =========================================================================

    /**
     * Detect the GLAM domain type for an object (cached or computed).
     */
    public function detectType(int $objectId): string;

    /**
     * Detect and save the GLAM domain type, optionally forcing re-detection.
     */
    public function detectAndSaveType(int $objectId, bool $force = false): string;

    /**
     * Get the display profile for an object (object-specific or domain default).
     */
    public function getProfile(int $objectId): ?object;

    /**
     * Get the GLAM type for an object (alias for detectType).
     */
    public function getType(int $objectId): string;

    // =========================================================================
    // UserBrowseSettings methods
    // =========================================================================

    /**
     * Get browse settings for a user.
     */
    public function getBrowseSettings(int $userId): array;

    /**
     * Get default browse settings.
     */
    public function getDefaultBrowseSettings(int $userId = 0): array;

    /**
     * Check if user has GLAM browse enabled.
     */
    public function useGlamBrowse(int $userId): bool;

    /**
     * Toggle GLAM browse for a user.
     */
    public function setGlamBrowse(int $userId, bool $enabled): bool;

    /**
     * Save browse settings for a user.
     */
    public function saveBrowseSettings(int $userId, array $data): bool;

    /**
     * Save last-used filter state for a user.
     */
    public function saveLastFilters(int $userId, array $filters): bool;

    /**
     * Get last-used filter state for a user.
     */
    public function getLastFilters(int $userId): array;

    /**
     * Reset browse settings to defaults for a user.
     */
    public function resetBrowseSettings(int $userId): bool;
}
