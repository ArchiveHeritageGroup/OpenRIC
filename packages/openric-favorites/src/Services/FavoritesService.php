<?php

declare(strict_types=1);

namespace OpenRiC\Favorites\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenRiC\Favorites\Contracts\FavoritesServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Favorites service -- adapted from Heratio AhgFavorites\Services\FavoritesService.
 *
 * Re-modelled for RiC-O: favourites reference entity_iri (string IRI) instead of integer object IDs.
 * PostgreSQL with ILIKE for case-insensitive search.
 */
class FavoritesService implements FavoritesServiceInterface
{
    /**
     * Browse/paginate the user's favorites with full filtering support.
     *
     * Supports folder filtering, unfiled filter, text search, sorting, and pagination.
     */
    public function getFavorites(int $userId, array $params = []): array
    {
        $page    = max(1, (int) ($params['page'] ?? 1));
        $limit   = min(100, max(1, (int) ($params['limit'] ?? 20)));
        $sort    = $params['sort'] ?? 'created_at';
        $sortDir = strtolower($params['sortDir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query   = $params['query'] ?? null;
        $type    = $params['entity_type'] ?? null;
        $folderId = $params['folder_id'] ?? null;
        $unfiled  = !empty($params['unfiled']);

        $q = DB::table('favorites')->where('user_id', $userId);

        // Folder filtering
        if ($unfiled) {
            $q->whereNull('folder_id');
        } elseif ($folderId) {
            $q->where('folder_id', (int) $folderId);
        }

        // Entity type filtering
        if ($type) {
            $q->where('entity_type', $type);
        }

        // Text search (PostgreSQL ILIKE)
        if ($query) {
            $q->where(function ($sub) use ($query): void {
                $sub->where('title', 'ILIKE', "%{$query}%")
                    ->orWhere('entity_iri', 'ILIKE', "%{$query}%")
                    ->orWhere('reference_code', 'ILIKE', "%{$query}%")
                    ->orWhere('notes', 'ILIKE', "%{$query}%");
            });
        }

        $sortCol = match ($sort) {
            'title'          => 'title',
            'reference_code' => 'reference_code',
            'type'           => 'entity_type',
            'updated_at'     => 'updated_at',
            default          => 'created_at',
        };

        $total = $q->count();

        $results = $q->orderBy($sortCol, $sortDir)
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        return [
            'results'  => $results,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => max(1, (int) ceil($total / $limit)),
            'limit'    => $limit,
        ];
    }

    /**
     * Add an entity to the user's favorites.
     */
    public function addFavorite(
        int $userId,
        string $entityIri,
        string $entityType,
        string $title,
        ?string $referenceCode = null,
        ?int $folderId = null,
    ): bool {
        if ($this->isFavorite($userId, $entityIri)) {
            return false;
        }

        DB::table('favorites')->insert([
            'user_id'        => $userId,
            'entity_iri'     => $entityIri,
            'entity_type'    => $entityType,
            'title'          => $title,
            'reference_code' => $referenceCode,
            'folder_id'      => $folderId,
            'notes'          => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return true;
    }

    /**
     * Remove a single favorite by its row ID.
     */
    public function removeFavorite(int $userId, int $favoriteId): bool
    {
        return DB::table('favorites')
            ->where('id', $favoriteId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Toggle favorite on/off. Returns true if now favorited, false if removed.
     */
    public function toggle(int $userId, string $entityIri, string $entityType, string $title): bool
    {
        if ($this->isFavorite($userId, $entityIri)) {
            DB::table('favorites')
                ->where('user_id', $userId)
                ->where('entity_iri', $entityIri)
                ->delete();
            return false; // Removed
        }

        $this->addFavorite($userId, $entityIri, $entityType, $title);
        return true; // Added
    }

    /**
     * Check whether an entity IRI is already favorited.
     */
    public function isFavorite(int $userId, string $entityIri): bool
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->where('entity_iri', $entityIri)
            ->exists();
    }

    /**
     * Count total favorites for a user.
     */
    public function getFavoriteCount(int $userId): int
    {
        return DB::table('favorites')->where('user_id', $userId)->count();
    }

    /**
     * Update notes on a specific favorite.
     */
    public function updateNotes(int $userId, int $id, string $notes): bool
    {
        return DB::table('favorites')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update([
                'notes'      => $notes,
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Bulk remove multiple favorites by their IDs.
     */
    public function bulkRemove(int $userId, array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        return DB::table('favorites')
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->delete();
    }

    /**
     * Move multiple favorites to a folder (or unfiled if null).
     */
    public function moveToFolder(int $userId, array $ids, ?int $folderId): int
    {
        if (empty($ids)) {
            return 0;
        }

        return DB::table('favorites')
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->update([
                'folder_id'  => $folderId,
                'updated_at' => now(),
            ]);
    }

    /**
     * Clear all favorites for a user.
     */
    public function clearAll(int $userId): int
    {
        return DB::table('favorites')->where('user_id', $userId)->delete();
    }

    /**
     * Export favorites as CSV download, optionally filtered by folder.
     */
    public function exportCsv(int $userId, ?int $folderId = null): StreamedResponse
    {
        $query = DB::table('favorites')->where('user_id', $userId);
        if ($folderId) {
            $query->where('folder_id', $folderId);
        }
        $items = $query->orderBy('created_at', 'desc')->get();

        return response()->streamDownload(function () use ($items): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Title', 'Entity IRI', 'Entity Type', 'Reference Code', 'Notes', 'Date Added']);
            foreach ($items as $item) {
                fputcsv($out, [
                    $item->title,
                    $item->entity_iri,
                    $item->entity_type ?? '',
                    $item->reference_code ?? '',
                    $item->notes ?? '',
                    $item->created_at,
                ]);
            }
            fclose($out);
        }, 'favorites-' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Export favorites as JSON, optionally filtered by folder.
     */
    public function exportJson(int $userId, ?int $folderId = null): JsonResponse
    {
        $query = DB::table('favorites')->where('user_id', $userId);
        if ($folderId) {
            $query->where('folder_id', $folderId);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    /**
     * Import favorites from newline-separated content (entity IRIs or titles).
     *
     * Each non-empty line is treated as an entity_iri. If found in the triplestore
     * entity index, the title is resolved; otherwise the IRI itself is used as title.
     */
    public function importFromCsv(int $userId, string $content): int
    {
        $lines = explode("\n", trim($content));
        $count = 0;

        foreach ($lines as $line) {
            $iri = trim($line);
            if ($iri === '' || $iri === 'entity_iri' || $iri === 'slug') {
                continue;
            }

            // Look up entity in the local entity index
            $entity = DB::table('entity_index')
                ->where('entity_iri', $iri)
                ->first();

            $title = $entity->title ?? $iri;
            $type  = $entity->entity_type ?? 'RecordResource';
            $ref   = $entity->reference_code ?? null;

            if ($this->addFavorite($userId, $iri, $type, $title, $ref)) {
                $count++;
            }
        }

        return $count;
    }
}
