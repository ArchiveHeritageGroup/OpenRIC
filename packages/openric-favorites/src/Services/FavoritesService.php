<?php

declare(strict_types=1);

namespace OpenRiC\Favorites\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\Favorites\Contracts\FavoritesServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Favorites service -- adapted from Heratio AhgFavorites\Services\FavoritesService (201 lines).
 *
 * Re-modelled for RiC-O: favourites reference entity_iri (string IRI) instead of integer object IDs.
 */
class FavoritesService implements FavoritesServiceInterface
{
    public function addFavorite(int $userId, string $entityIri, string $entityType, string $title): bool
    {
        if ($this->isFavorite($userId, $entityIri)) {
            return false;
        }

        DB::table('favorites')->insert([
            'user_id'     => $userId,
            'entity_iri'  => $entityIri,
            'entity_type' => $entityType,
            'title'       => $title,
            'added_at'    => now(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return true;
    }

    public function removeFavorite(int $userId, int $favoriteId): bool
    {
        return DB::table('favorites')
            ->where('id', $favoriteId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    public function getFavorites(int $userId, array $params = []): array
    {
        $page    = max(1, (int) ($params['page'] ?? 1));
        $limit   = min(100, max(1, (int) ($params['limit'] ?? 20)));
        $sort    = $params['sort'] ?? 'added_at';
        $sortDir = strtolower($params['sortDir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query   = $params['query'] ?? null;
        $type    = $params['entity_type'] ?? null;

        $q = DB::table('favorites')->where('user_id', $userId);

        if ($type) {
            $q->where('entity_type', $type);
        }

        if ($query) {
            $q->where(function ($sub) use ($query) {
                $sub->where('title', 'ILIKE', "%{$query}%")
                    ->orWhere('entity_iri', 'ILIKE', "%{$query}%");
            });
        }

        $sortCol = match ($sort) {
            'title'      => 'title',
            'type'       => 'entity_type',
            'updated_at' => 'updated_at',
            default      => 'added_at',
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

    public function isFavorite(int $userId, string $entityIri): bool
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->where('entity_iri', $entityIri)
            ->exists();
    }

    public function getFavoriteCount(int $userId): int
    {
        return DB::table('favorites')->where('user_id', $userId)->count();
    }

    public function toggle(int $userId, string $entityIri, string $entityType, string $title): bool
    {
        if ($this->isFavorite($userId, $entityIri)) {
            DB::table('favorites')
                ->where('user_id', $userId)
                ->where('entity_iri', $entityIri)
                ->delete();
            return false;
        }

        $this->addFavorite($userId, $entityIri, $entityType, $title);
        return true;
    }

    public function clearAll(int $userId): int
    {
        return DB::table('favorites')->where('user_id', $userId)->delete();
    }

    public function exportCsv(int $userId): StreamedResponse
    {
        $items = DB::table('favorites')
            ->where('user_id', $userId)
            ->orderBy('added_at', 'desc')
            ->get();

        return response()->streamDownload(function () use ($items): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Title', 'Entity IRI', 'Entity Type', 'Date Added']);
            foreach ($items as $item) {
                fputcsv($out, [
                    $item->title,
                    $item->entity_iri,
                    $item->entity_type,
                    $item->added_at,
                ]);
            }
            fclose($out);
        }, 'favorites-' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
