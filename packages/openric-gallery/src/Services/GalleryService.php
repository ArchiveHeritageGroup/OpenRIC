<?php

declare(strict_types=1);

namespace OpenRiC\Gallery\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenRiC\Gallery\Contracts\GalleryServiceInterface;

/**
 * Gallery service — adapted from Heratio ahg-gallery GalleryService + GalleryController.
 *
 * OpenRiC galleries are standalone curated collections of RiC entities,
 * unlike Heratio which wraps AtoM's information_object + display_object_config.
 */
class GalleryService implements GalleryServiceInterface
{
    public function getGalleries(array $params = []): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;
        $search = trim($params['search'] ?? '');
        $publicOnly = (bool) ($params['public_only'] ?? false);
        $featuredOnly = (bool) ($params['featured_only'] ?? false);

        $query = DB::table('galleries')
            ->leftJoin('users', 'galleries.created_by', '=', 'users.id')
            ->select(
                'galleries.*',
                'users.name as creator_name',
                DB::raw('(SELECT COUNT(*) FROM gallery_items WHERE gallery_items.gallery_id = galleries.id) as item_count')
            );

        if ($publicOnly) {
            $query->where('galleries.is_public', true);
        }
        if ($featuredOnly) {
            $query->where('galleries.is_featured', true);
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('galleries.title', 'ILIKE', $like)
                  ->orWhere('galleries.description', 'ILIKE', $like);
            });
        }

        $total = $query->count();

        $galleries = $query
            ->orderBy('galleries.sort_order')
            ->orderByDesc('galleries.created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->toArray();

        return ['galleries' => $galleries, 'total' => $total];
    }

    public function getGallery(int $id): ?array
    {
        $gallery = DB::table('galleries')
            ->leftJoin('users', 'galleries.created_by', '=', 'users.id')
            ->where('galleries.id', $id)
            ->select('galleries.*', 'users.name as creator_name')
            ->first();

        if (!$gallery) {
            return null;
        }

        $result = (array) $gallery;
        $result['items'] = $this->getGalleryItems($id);
        $result['item_count'] = count($result['items']);

        return $result;
    }

    public function createGallery(array $data): int
    {
        $slug = Str::slug($data['title'] ?? 'gallery');
        $baseSlug = $slug;
        $counter = 1;
        while (DB::table('galleries')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        // Determine next sort order
        $maxSort = DB::table('galleries')->max('sort_order') ?? 0;

        return (int) DB::table('galleries')->insertGetId([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'cover_image' => $data['cover_image'] ?? null,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'is_public' => (bool) ($data['is_public'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? $maxSort + 1),
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function updateGallery(int $id, array $data): void
    {
        $update = ['updated_at' => now()];

        if (isset($data['title'])) {
            $update['title'] = $data['title'];
        }
        if (array_key_exists('description', $data)) {
            $update['description'] = $data['description'];
        }
        if (array_key_exists('cover_image', $data)) {
            $update['cover_image'] = $data['cover_image'];
        }
        if (isset($data['is_featured'])) {
            $update['is_featured'] = (bool) $data['is_featured'];
        }
        if (isset($data['is_public'])) {
            $update['is_public'] = (bool) $data['is_public'];
        }
        if (isset($data['sort_order'])) {
            $update['sort_order'] = (int) $data['sort_order'];
        }

        // Update slug if title changed
        if (isset($data['title'])) {
            $slug = Str::slug($data['title']);
            $baseSlug = $slug;
            $counter = 1;
            while (DB::table('galleries')->where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }
            $update['slug'] = $slug;
        }

        DB::table('galleries')->where('id', $id)->update($update);
    }

    public function deleteGallery(int $id): void
    {
        DB::transaction(function () use ($id): void {
            DB::table('gallery_items')->where('gallery_id', $id)->delete();
            DB::table('galleries')->where('id', $id)->delete();
        });
    }

    public function getGalleryItems(int $galleryId): array
    {
        return DB::table('gallery_items')
            ->where('gallery_id', $galleryId)
            ->orderBy('sort_order')
            ->select('id', 'gallery_id', 'entity_iri', 'entity_type', 'title', 'thumbnail', 'sort_order', 'created_at', 'updated_at')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->toArray();
    }

    public function addItem(int $galleryId, string $entityIri, string $entityType, string $title, ?string $thumbnail = null): int
    {
        $maxSort = DB::table('gallery_items')
            ->where('gallery_id', $galleryId)
            ->max('sort_order') ?? 0;

        return (int) DB::table('gallery_items')->insertGetId([
            'gallery_id' => $galleryId,
            'entity_iri' => $entityIri,
            'entity_type' => $entityType,
            'title' => $title,
            'thumbnail' => $thumbnail,
            'sort_order' => $maxSort + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function removeItem(int $itemId): void
    {
        DB::table('gallery_items')->where('id', $itemId)->delete();
    }

    public function reorderItems(int $galleryId, array $itemOrder): void
    {
        DB::transaction(function () use ($galleryId, $itemOrder): void {
            foreach ($itemOrder as $itemId => $sortOrder) {
                DB::table('gallery_items')
                    ->where('id', (int) $itemId)
                    ->where('gallery_id', $galleryId)
                    ->update(['sort_order' => (int) $sortOrder, 'updated_at' => now()]);
            }
        });
    }

    public function getFeaturedGalleries(int $limit = 10): array
    {
        return DB::table('galleries')
            ->where('is_featured', true)
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->limit($limit)
            ->select('id', 'title', 'slug', 'description', 'cover_image', 'sort_order')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->toArray();
    }
}
