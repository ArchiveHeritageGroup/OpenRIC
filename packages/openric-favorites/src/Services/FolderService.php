<?php

declare(strict_types=1);

namespace OpenRiC\Favorites\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenRiC\Favorites\Contracts\FolderServiceInterface;

/**
 * Folder management service for favorites.
 *
 * Adapted from Heratio AhgFavorites\Services\FolderService.
 * PostgreSQL, full folder CRUD + sharing with token-based public access.
 */
class FolderService implements FolderServiceInterface
{
    /**
     * Get all folders for a user, ordered by sort_order then name, with item counts.
     */
    public function getUserFolders(int $userId): Collection
    {
        return DB::table('favorites_folder')
            ->where('user_id', $userId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (object $folder) use ($userId): object {
                $folder->item_count = DB::table('favorites')
                    ->where('user_id', $userId)
                    ->where('folder_id', $folder->id)
                    ->count();
                return $folder;
            });
    }

    /**
     * Count favorites that are not assigned to any folder.
     */
    public function getUnfiledCount(int $userId): int
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->whereNull('folder_id')
            ->count();
    }

    /**
     * Create a new folder.
     */
    public function createFolder(
        int $userId,
        string $name,
        ?string $description = null,
        ?string $color = null,
    ): int {
        return DB::table('favorites_folder')->insertGetId([
            'user_id'     => $userId,
            'name'        => $name,
            'description' => $description,
            'color'       => $color,
            'visibility'  => 'private',
            'sort_order'  => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * Update a folder's properties (name, description, color, sort_order).
     */
    public function updateFolder(int $userId, int $id, array $data): bool
    {
        $allowed = ['name', 'description', 'color', 'sort_order'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        if (empty($filtered)) {
            return false;
        }

        $filtered['updated_at'] = now();

        return DB::table('favorites_folder')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update($filtered) > 0;
    }

    /**
     * Delete a folder. Moves contained items to unfiled and child folders to root.
     */
    public function deleteFolder(int $userId, int $id): bool
    {
        // Move items in this folder to unfiled
        DB::table('favorites')
            ->where('user_id', $userId)
            ->where('folder_id', $id)
            ->update(['folder_id' => null, 'updated_at' => now()]);

        // Move child folders to root
        DB::table('favorites_folder')
            ->where('user_id', $userId)
            ->where('parent_id', $id)
            ->update(['parent_id' => null, 'updated_at' => now()]);

        // Delete share records
        DB::table('favorites_share')
            ->where('folder_id', $id)
            ->delete();

        return DB::table('favorites_folder')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Fetch a single folder owned by the user.
     */
    public function getFolder(int $userId, int $id): ?object
    {
        return DB::table('favorites_folder')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Generate a share token for a folder, valid for the given number of days.
     */
    public function shareFolder(int $userId, int $id, int $days = 30): ?string
    {
        $token     = Str::random(64);
        $expiresAt = now()->addDays($days);

        $updated = DB::table('favorites_folder')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update([
                'share_token'      => $token,
                'share_expires_at' => $expiresAt,
                'visibility'       => 'shared',
                'updated_at'       => now(),
            ]);

        if (!$updated) {
            return null;
        }

        DB::table('favorites_share')->insert([
            'folder_id'    => $id,
            'shared_via'   => 'link',
            'token'        => $token,
            'expires_at'   => $expiresAt,
            'access_count' => 0,
            'created_at'   => now(),
        ]);

        return $token;
    }

    /**
     * Revoke sharing for a folder: remove token and all share records.
     */
    public function revokeSharing(int $userId, int $id): bool
    {
        DB::table('favorites_share')
            ->where('folder_id', $id)
            ->delete();

        return DB::table('favorites_folder')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update([
                'share_token'      => null,
                'share_expires_at' => null,
                'visibility'       => 'private',
                'updated_at'       => now(),
            ]) > 0;
    }

    /**
     * Retrieve a folder by its share token (public access). Tracks access count.
     * Returns null if token is invalid or expired.
     */
    public function getSharedFolder(string $token): ?object
    {
        $folder = DB::table('favorites_folder')
            ->where('share_token', $token)
            ->where(function ($q): void {
                $q->whereNull('share_expires_at')
                    ->orWhere('share_expires_at', '>', now());
            })
            ->first();

        if ($folder) {
            DB::table('favorites_share')
                ->where('token', $token)
                ->update([
                    'accessed_at'  => now(),
                    'access_count' => DB::raw('access_count + 1'),
                ]);
        }

        return $folder;
    }

    /**
     * Get all items in a shared folder.
     */
    public function getSharedFolderItems(int $folderId, int $userId): Collection
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->where('folder_id', $folderId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
