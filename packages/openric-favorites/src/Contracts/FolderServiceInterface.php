<?php

declare(strict_types=1);

namespace OpenRiC\Favorites\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for the Favorites Folder service.
 *
 * Adapted from Heratio AhgFavorites\Services\FolderService.
 */
interface FolderServiceInterface
{
    /**
     * Get all folders belonging to a user, with item counts.
     */
    public function getUserFolders(int $userId): Collection;

    /**
     * Count favorites not assigned to any folder.
     */
    public function getUnfiledCount(int $userId): int;

    /**
     * Create a new folder.
     *
     * @return int The new folder's ID.
     */
    public function createFolder(
        int $userId,
        string $name,
        ?string $description = null,
        ?string $color = null,
    ): int;

    /**
     * Update a folder's properties.
     */
    public function updateFolder(int $userId, int $id, array $data): bool;

    /**
     * Delete a folder and move its items to unfiled.
     */
    public function deleteFolder(int $userId, int $id): bool;

    /**
     * Get a single folder by ID (owned by user).
     */
    public function getFolder(int $userId, int $id): ?object;

    /**
     * Generate a share token for a folder.
     *
     * @return string|null The share token, or null on failure.
     */
    public function shareFolder(int $userId, int $id, int $days = 30): ?string;

    /**
     * Revoke sharing for a folder.
     */
    public function revokeSharing(int $userId, int $id): bool;

    /**
     * Get a folder by its share token (public access).
     */
    public function getSharedFolder(string $token): ?object;

    /**
     * Get items in a shared folder.
     */
    public function getSharedFolderItems(int $folderId, int $userId): Collection;
}
