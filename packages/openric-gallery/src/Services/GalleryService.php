<?php

declare(strict_types=1);

namespace OpenRiC\Gallery\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use OpenRiC\Gallery\Contracts\GalleryServiceInterface;

/**
 * Gallery service -- adapted from Heratio ahg-gallery GalleryService (837 lines).
 *
 * Manages: curated gallery collections, CCO artwork cataloguing via
 * information_object + museum_metadata, gallery artists, and dashboard stats.
 * PostgreSQL ILIKE for case-insensitive search.
 */
class GalleryService implements GalleryServiceInterface
{
    // =========================================================================
    // Gallery Collections (curated entity groups)
    // =========================================================================

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

    // =========================================================================
    // Artwork CRUD (CCO cataloguing via information_object + museum_metadata)
    // =========================================================================

    /**
     * Get a single gallery artwork by its slug.
     * Joins information_object + i18n + slug + museum_metadata + display_object_config (object_type='gallery').
     */
    public function getBySlug(string $slug, string $culture = 'en'): ?object
    {
        $artwork = DB::table('information_object as io')
            ->join('information_object_i18n as i18n', function ($j) use ($culture): void {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->join('object as obj', 'io.id', '=', 'obj.id')
            ->join('slug', 'io.id', '=', 'slug.object_id')
            ->join('display_object_config as doc', function ($j): void {
                $j->on('io.id', '=', 'doc.object_id')->where('doc.object_type', '=', 'gallery');
            })
            ->leftJoin('museum_metadata as mm', 'io.id', '=', 'mm.object_id')
            ->where('slug.slug', $slug)
            ->select([
                'io.id',
                'io.identifier',
                'io.level_of_description_id',
                'io.repository_id',
                'io.parent_id',
                'io.lft',
                'io.rgt',
                'io.description_status_id',
                'io.description_detail_id',
                'io.description_identifier',
                'io.source_standard',
                'io.display_standard_id',
                'io.collection_type_id',
                'io.source_culture',
                'i18n.title',
                'i18n.alternate_title',
                'i18n.extent_and_medium',
                'i18n.scope_and_content',
                'i18n.archival_history',
                'i18n.acquisition',
                'i18n.access_conditions',
                'i18n.reproduction_conditions',
                'i18n.physical_characteristics',
                'i18n.arrangement',
                'i18n.appraisal',
                'i18n.accruals',
                'i18n.finding_aids',
                'i18n.location_of_originals',
                'i18n.location_of_copies',
                'i18n.related_units_of_description',
                'i18n.rules',
                'i18n.sources',
                'i18n.revision_history',
                'i18n.institution_responsible_identifier',
                'obj.created_at',
                'obj.updated_at',
                'slug.slug',
                // Museum/Gallery CCO metadata
                'mm.id as metadata_id',
                'mm.object_id as mm_object_id',
                'mm.work_type',
                'mm.classification',
                'mm.creator_identity',
                'mm.creator_role',
                'mm.creation_date_display',
                'mm.creation_date_earliest',
                'mm.creation_date_latest',
                'mm.creation_place',
                'mm.style',
                'mm.period',
                'mm.movement',
                'mm.school',
                'mm.measurements',
                'mm.dimensions',
                'mm.materials',
                'mm.techniques',
                'mm.inscription',
                'mm.mark_description',
                'mm.condition_term',
                'mm.condition_description',
                'mm.provenance',
                'mm.current_location',
                'mm.rights_type',
                'mm.rights_holder',
                'mm.cataloger_name',
                'mm.cataloging_date',
            ])
            ->first();

        return $artwork;
    }

    /**
     * Browse gallery artworks with pagination, search, sort, filters.
     */
    public function browse(array $params, string $culture = 'en'): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, (int) ($params['limit'] ?? 10));
        $sort = $params['sort'] ?? 'alphabetic';
        $subquery = $params['subquery'] ?? '';

        $query = DB::table('information_object as io')
            ->join('information_object_i18n as i18n', function ($j) use ($culture): void {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->join('object as obj', 'io.id', '=', 'obj.id')
            ->join('slug', 'io.id', '=', 'slug.object_id')
            ->join('display_object_config as doc', function ($j): void {
                $j->on('io.id', '=', 'doc.object_id')->where('doc.object_type', '=', 'gallery');
            })
            ->leftJoin('museum_metadata as mm', 'io.id', '=', 'mm.object_id');

        // Search filter (PostgreSQL ILIKE)
        if ($subquery !== '') {
            $like = '%' . $subquery . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('i18n.title', 'ILIKE', $like)
                    ->orWhere('io.identifier', 'ILIKE', $like)
                    ->orWhere('mm.creator_identity', 'ILIKE', $like)
                    ->orWhere('mm.materials', 'ILIKE', $like)
                    ->orWhere('mm.techniques', 'ILIKE', $like)
                    ->orWhere('mm.classification', 'ILIKE', $like)
                    ->orWhere('mm.work_type', 'ILIKE', $like);
            });
        }

        // Repository filter
        if (!empty($params['filters']['repository_id'])) {
            $query->where('io.repository_id', $params['filters']['repository_id']);
        }

        // Count
        $total = $query->count();

        // Sort
        switch ($sort) {
            case 'lastUpdated':
                $query->orderBy('obj.updated_at', 'desc');
                break;
            case 'identifier':
                $query->orderBy('io.identifier', 'asc');
                break;
            case 'artist':
                $query->orderBy('mm.creator_identity', 'asc');
                break;
            default: // alphabetic
                $query->orderBy('i18n.title', 'asc');
                break;
        }

        $offset = ($page - 1) * $limit;
        $rows = $query->select([
                'io.id',
                'io.identifier',
                'io.repository_id',
                'io.level_of_description_id',
                'i18n.title as name',
                'slug.slug',
                'obj.updated_at',
                'mm.creator_identity',
                'mm.work_type',
                'mm.materials',
                'mm.techniques',
                'mm.creation_date_display',
            ])
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Resolve repository names
        $repoIds = $rows->pluck('repository_id')->filter()->unique()->values()->toArray();
        $repositoryNames = [];
        if (!empty($repoIds)) {
            $repositoryNames = DB::table('repository')
                ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
                ->whereIn('repository.id', $repoIds)
                ->where('actor_i18n.culture', $culture)
                ->pluck('actor_i18n.authorized_form_of_name', 'repository.id')
                ->toArray();
        }

        // Fetch master and thumbnail digital objects for each artwork
        $objectIds = $rows->pluck('id')->toArray();
        $thumbnails = [];
        $masters = [];
        if (!empty($objectIds)) {
            // Master digital objects (no parent_id)
            $masterRows = DB::table('digital_object')
                ->whereIn('object_id', $objectIds)
                ->whereNull('parent_id')
                ->select('id', 'object_id', 'path', 'name', 'mime_type')
                ->get();
            foreach ($masterRows as $mr) {
                $masters[$mr->object_id] = $mr;
            }

            // Thumbnail derivatives (usage_id = 142 for Thumbnail)
            $masterIds = $masterRows->pluck('id')->toArray();
            if (!empty($masterIds)) {
                $thumbRows = DB::table('digital_object')
                    ->whereIn('parent_id', $masterIds)
                    ->where('usage_id', 142)
                    ->select('parent_id', 'path', 'name', 'mime_type')
                    ->get();
                $masterIdToObjectId = [];
                foreach ($masterRows as $mr) {
                    $masterIdToObjectId[$mr->id] = $mr->object_id;
                }
                foreach ($thumbRows as $tr) {
                    $objId = $masterIdToObjectId[$tr->parent_id] ?? null;
                    if ($objId) {
                        $thumbnails[$objId] = $tr;
                    }
                }
            }
        }

        $results = [];
        foreach ($rows as $row) {
            $master = $masters[$row->id] ?? null;
            $results[] = [
                'id' => $row->id,
                'identifier' => $row->identifier,
                'name' => $row->name,
                'slug' => $row->slug,
                'updated_at' => $row->updated_at,
                'repository_id' => $row->repository_id,
                'creator_identity' => $row->creator_identity,
                'work_type' => $row->work_type,
                'materials' => $row->materials,
                'techniques' => $row->techniques,
                'creation_date_display' => $row->creation_date_display,
                'thumbnail' => $thumbnails[$row->id] ?? null,
                'master_path' => $master->path ?? null,
                'master_name' => $master->name ?? null,
            ];
        }

        return [
            'results' => $results,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'repositoryNames' => $repositoryNames,
        ];
    }

    /**
     * Create a new gallery artwork: IO + i18n + museum_metadata + display_object_config + slug.
     */
    public function createArtwork(array $data, string $culture = 'en'): string
    {
        return DB::transaction(function () use ($data, $culture): string {
            $parentId = $data['parent_id'] ?? 1;

            // Determine lft/rgt position
            $parent = DB::table('information_object')
                ->where('id', $parentId)
                ->select('lft', 'rgt')
                ->first();

            if (!$parent) {
                abort(422, 'Invalid parent information object.');
            }

            $newLft = $parent->rgt;
            $newRgt = $parent->rgt + 1;

            // Shift nested set values
            DB::table('information_object')
                ->where('rgt', '>=', $parent->rgt)
                ->increment('rgt', 2);

            DB::table('information_object')
                ->where('lft', '>', $parent->rgt)
                ->increment('lft', 2);

            // Insert into object table
            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitInformationObject',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert information_object
            DB::table('information_object')->insert([
                'id' => $objectId,
                'identifier' => $data['identifier'] ?? null,
                'level_of_description_id' => !empty($data['level_of_description_id']) ? $data['level_of_description_id'] : null,
                'collection_type_id' => null,
                'repository_id' => !empty($data['repository_id']) ? $data['repository_id'] : null,
                'parent_id' => $parentId,
                'description_status_id' => !empty($data['description_status_id']) ? $data['description_status_id'] : null,
                'description_detail_id' => !empty($data['description_detail_id']) ? $data['description_detail_id'] : null,
                'description_identifier' => $data['description_identifier'] ?? null,
                'source_standard' => $data['source_standard'] ?? null,
                'display_standard_id' => !empty($data['display_standard_id']) ? $data['display_standard_id'] : null,
                'lft' => $newLft,
                'rgt' => $newRgt,
                'source_culture' => $culture,
            ]);

            // Insert information_object_i18n
            DB::table('information_object_i18n')->insert([
                'id' => $objectId,
                'culture' => $culture,
                'title' => $data['title'],
                'alternate_title' => $data['alternate_title'] ?? null,
                'extent_and_medium' => $data['extent_and_medium'] ?? null,
                'scope_and_content' => $data['scope_and_content'] ?? null,
                'archival_history' => $data['archival_history'] ?? null,
                'acquisition' => $data['acquisition'] ?? null,
                'access_conditions' => $data['access_conditions'] ?? null,
                'reproduction_conditions' => $data['reproduction_conditions'] ?? null,
                'physical_characteristics' => $data['physical_characteristics'] ?? null,
                'arrangement' => $data['arrangement'] ?? null,
                'appraisal' => $data['appraisal'] ?? null,
                'accruals' => $data['accruals'] ?? null,
                'finding_aids' => $data['finding_aids'] ?? null,
                'location_of_originals' => $data['location_of_originals'] ?? null,
                'location_of_copies' => $data['location_of_copies'] ?? null,
                'related_units_of_description' => $data['related_units_of_description'] ?? null,
                'rules' => $data['rules'] ?? null,
                'sources' => $data['sources'] ?? null,
                'revision_history' => $data['revision_history'] ?? null,
                'institution_responsible_identifier' => $data['institution_responsible_identifier'] ?? null,
            ]);

            // Insert display_object_config for gallery type
            DB::table('display_object_config')->insert([
                'object_id' => $objectId,
                'object_type' => 'gallery',
            ]);

            // Insert museum_metadata (shared CCO fields)
            DB::table('museum_metadata')->insert([
                'object_id' => $objectId,
                'work_type' => $data['work_type'] ?? null,
                'classification' => $data['classification'] ?? null,
                'creator_identity' => $data['creator_identity'] ?? null,
                'creator_role' => $data['creator_role'] ?? null,
                'creation_date_display' => $data['creation_date_display'] ?? null,
                'creation_date_earliest' => $data['creation_date_earliest'] ?? null,
                'creation_date_latest' => $data['creation_date_latest'] ?? null,
                'creation_place' => $data['creation_place'] ?? null,
                'style' => $data['style'] ?? null,
                'period' => $data['period'] ?? null,
                'movement' => $data['movement'] ?? null,
                'school' => $data['school'] ?? null,
                'measurements' => $data['measurements'] ?? null,
                'dimensions' => $data['dimensions'] ?? null,
                'materials' => $data['materials'] ?? null,
                'techniques' => $data['techniques'] ?? null,
                'inscription' => $data['inscription'] ?? null,
                'mark_description' => $data['mark_description'] ?? null,
                'condition_term' => $data['condition_term'] ?? null,
                'condition_description' => $data['condition_description'] ?? null,
                'provenance' => $data['provenance'] ?? null,
                'current_location' => $data['current_location'] ?? null,
                'rights_type' => $data['rights_type'] ?? null,
                'rights_holder' => $data['rights_holder'] ?? null,
                'cataloger_name' => $data['cataloger_name'] ?? null,
                'cataloging_date' => $data['cataloging_date'] ?? null,
            ]);

            // Generate slug
            $baseSlug = Str::slug($data['title'] ?: 'untitled');
            $slug = $baseSlug;
            $counter = 1;
            while (DB::table('slug')->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            DB::table('slug')->insert([
                'object_id' => $objectId,
                'slug' => $slug,
            ]);

            // Set publication status (draft by default: 159)
            DB::table('status')->insert([
                'object_id' => $objectId,
                'type_id' => 158,
                'status_id' => 159,
            ]);

            return $slug;
        });
    }

    /**
     * Update a gallery artwork.
     */
    public function updateArtwork(string $slug, array $data, string $culture = 'en'): void
    {
        DB::transaction(function () use ($slug, $data, $culture): void {
            $io = DB::table('slug')
                ->join('information_object', 'slug.object_id', '=', 'information_object.id')
                ->where('slug.slug', $slug)
                ->select('information_object.id')
                ->first();

            if (!$io) {
                abort(404);
            }

            $ioId = $io->id;

            // Update information_object
            DB::table('information_object')
                ->where('id', $ioId)
                ->update([
                    'identifier' => $data['identifier'] ?? null,
                    'level_of_description_id' => !empty($data['level_of_description_id']) ? $data['level_of_description_id'] : null,
                    'repository_id' => !empty($data['repository_id']) ? $data['repository_id'] : null,
                    'description_status_id' => !empty($data['description_status_id']) ? $data['description_status_id'] : null,
                    'description_detail_id' => !empty($data['description_detail_id']) ? $data['description_detail_id'] : null,
                    'description_identifier' => $data['description_identifier'] ?? null,
                    'source_standard' => $data['source_standard'] ?? null,
                    'display_standard_id' => !empty($data['display_standard_id']) ? $data['display_standard_id'] : null,
                ]);

            // Update information_object_i18n
            DB::table('information_object_i18n')
                ->where('id', $ioId)
                ->where('culture', $culture)
                ->update([
                    'title' => $data['title'],
                    'alternate_title' => $data['alternate_title'] ?? null,
                    'extent_and_medium' => $data['extent_and_medium'] ?? null,
                    'scope_and_content' => $data['scope_and_content'] ?? null,
                    'archival_history' => $data['archival_history'] ?? null,
                    'acquisition' => $data['acquisition'] ?? null,
                    'access_conditions' => $data['access_conditions'] ?? null,
                    'reproduction_conditions' => $data['reproduction_conditions'] ?? null,
                    'physical_characteristics' => $data['physical_characteristics'] ?? null,
                    'arrangement' => $data['arrangement'] ?? null,
                    'appraisal' => $data['appraisal'] ?? null,
                    'accruals' => $data['accruals'] ?? null,
                    'finding_aids' => $data['finding_aids'] ?? null,
                    'location_of_originals' => $data['location_of_originals'] ?? null,
                    'location_of_copies' => $data['location_of_copies'] ?? null,
                    'related_units_of_description' => $data['related_units_of_description'] ?? null,
                    'rules' => $data['rules'] ?? null,
                    'sources' => $data['sources'] ?? null,
                    'revision_history' => $data['revision_history'] ?? null,
                    'institution_responsible_identifier' => $data['institution_responsible_identifier'] ?? null,
                ]);

            // Update museum_metadata
            $metadataExists = DB::table('museum_metadata')->where('object_id', $ioId)->exists();
            $metaFields = [
                'work_type' => $data['work_type'] ?? null,
                'classification' => $data['classification'] ?? null,
                'creator_identity' => $data['creator_identity'] ?? null,
                'creator_role' => $data['creator_role'] ?? null,
                'creation_date_display' => $data['creation_date_display'] ?? null,
                'creation_date_earliest' => $data['creation_date_earliest'] ?? null,
                'creation_date_latest' => $data['creation_date_latest'] ?? null,
                'creation_place' => $data['creation_place'] ?? null,
                'style' => $data['style'] ?? null,
                'period' => $data['period'] ?? null,
                'movement' => $data['movement'] ?? null,
                'school' => $data['school'] ?? null,
                'measurements' => $data['measurements'] ?? null,
                'dimensions' => $data['dimensions'] ?? null,
                'materials' => $data['materials'] ?? null,
                'techniques' => $data['techniques'] ?? null,
                'inscription' => $data['inscription'] ?? null,
                'mark_description' => $data['mark_description'] ?? null,
                'condition_term' => $data['condition_term'] ?? null,
                'condition_description' => $data['condition_description'] ?? null,
                'provenance' => $data['provenance'] ?? null,
                'current_location' => $data['current_location'] ?? null,
                'rights_type' => $data['rights_type'] ?? null,
                'rights_holder' => $data['rights_holder'] ?? null,
                'cataloger_name' => $data['cataloger_name'] ?? null,
                'cataloging_date' => $data['cataloging_date'] ?? null,
            ];

            if ($metadataExists) {
                DB::table('museum_metadata')->where('object_id', $ioId)->update($metaFields);
            } else {
                DB::table('museum_metadata')->insert(array_merge(['object_id' => $ioId], $metaFields));
            }

            // Update object.updated_at
            DB::table('object')->where('id', $ioId)->update(['updated_at' => now()]);
        });
    }

    /**
     * Delete a gallery artwork and all associated records.
     */
    public function deleteArtwork(string $slug): void
    {
        DB::transaction(function () use ($slug): void {
            $record = DB::table('slug')
                ->join('information_object', 'slug.object_id', '=', 'information_object.id')
                ->where('slug.slug', $slug)
                ->select('information_object.id', 'information_object.lft', 'information_object.rgt')
                ->first();

            if (!$record) {
                abort(404);
            }

            $width = $record->rgt - $record->lft + 1;

            // Collect all descendant IDs
            $descendantIds = DB::table('information_object')
                ->whereBetween('lft', [$record->lft, $record->rgt])
                ->pluck('id')
                ->toArray();

            // Delete museum_metadata for all descendants
            DB::table('museum_metadata')->whereIn('object_id', $descendantIds)->delete();

            // Delete display_object_config for all descendants
            DB::table('display_object_config')->whereIn('object_id', $descendantIds)->delete();

            // Delete status rows
            DB::table('status')->whereIn('object_id', $descendantIds)->delete();

            // Delete i18n rows
            DB::table('information_object_i18n')->whereIn('id', $descendantIds)->delete();

            // Delete information_object rows
            DB::table('information_object')->whereIn('id', $descendantIds)->delete();

            // Delete slug rows
            DB::table('slug')->whereIn('object_id', $descendantIds)->delete();

            // Delete object rows
            DB::table('object')->whereIn('id', $descendantIds)->delete();

            // Close the gap in the nested set
            DB::table('information_object')
                ->where('lft', '>', $record->rgt)
                ->decrement('lft', $width);

            DB::table('information_object')
                ->where('rgt', '>', $record->rgt)
                ->decrement('rgt', $width);
        });
    }

    // =========================================================================
    // Artists
    // =========================================================================

    /**
     * Get all gallery artists with pagination/search.
     */
    public function getArtists(array $params = []): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, (int) ($params['limit'] ?? 10));
        $sort = $params['sort'] ?? 'alphabetic';
        $subquery = $params['subquery'] ?? '';

        $query = DB::table('gallery_artist');

        if ($subquery !== '') {
            $like = '%' . $subquery . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('display_name', 'ILIKE', $like)
                    ->orWhere('nationality', 'ILIKE', $like)
                    ->orWhere('medium_specialty', 'ILIKE', $like)
                    ->orWhere('movement_style', 'ILIKE', $like)
                    ->orWhere('biography', 'ILIKE', $like);
            });
        }

        // Only active by default
        if (!isset($params['include_inactive'])) {
            $query->where('is_active', true);
        }

        $total = $query->count();

        switch ($sort) {
            case 'lastUpdated':
                $query->orderBy('updated_at', 'desc');
                break;
            case 'nationality':
                $query->orderBy('nationality', 'asc')->orderBy('sort_name', 'asc');
                break;
            default: // alphabetic
                $query->orderBy('sort_name', 'asc');
                break;
        }

        $offset = ($page - 1) * $limit;
        $rows = $query->offset($offset)->limit($limit)->get();

        $results = [];
        foreach ($rows as $row) {
            $results[] = (array) $row;
        }

        return [
            'results' => $results,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Get a single artist with related artworks and authority info.
     */
    public function getArtist(int $id): ?object
    {
        $artist = DB::table('gallery_artist')->where('id', $id)->first();

        if (!$artist) {
            return null;
        }

        // Get artworks by this artist (via museum_metadata.creator_identity matching display_name)
        $artist->artworks = DB::table('museum_metadata as mm')
            ->join('display_object_config as doc', function ($j): void {
                $j->on('mm.object_id', '=', 'doc.object_id')->where('doc.object_type', '=', 'gallery');
            })
            ->join('information_object_i18n as i18n', function ($j): void {
                $j->on('mm.object_id', '=', 'i18n.id')->where('i18n.culture', '=', app()->getLocale());
            })
            ->join('slug', 'mm.object_id', '=', 'slug.object_id')
            ->where('mm.creator_identity', 'ILIKE', '%' . $artist->display_name . '%')
            ->select('mm.object_id as id', 'i18n.title', 'slug.slug', 'mm.work_type', 'mm.creation_date_display', 'mm.materials')
            ->get();

        // If artist has an actor_id, get related information from actor_i18n
        if ($artist->actor_id) {
            $culture = app()->getLocale();
            $actorInfo = DB::table('actor_i18n')
                ->where('id', $artist->actor_id)
                ->where('culture', $culture)
                ->select('history', 'places', 'legal_status', 'functions', 'mandates', 'internal_structures', 'general_context')
                ->first();
            $artist->actor_info = $actorInfo;
        }

        return $artist;
    }

    /**
     * Create a new gallery artist.
     */
    public function createArtist(array $data): int
    {
        return (int) DB::table('gallery_artist')->insertGetId([
            'actor_id' => !empty($data['actor_id']) ? (int) $data['actor_id'] : null,
            'display_name' => $data['display_name'],
            'sort_name' => $data['sort_name'] ?? $data['display_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'birth_place' => $data['birth_place'] ?? null,
            'death_date' => $data['death_date'] ?? null,
            'death_place' => $data['death_place'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'artist_type' => $data['artist_type'] ?? null,
            'medium_specialty' => $data['medium_specialty'] ?? null,
            'movement_style' => $data['movement_style'] ?? null,
            'active_period' => $data['active_period'] ?? null,
            'represented' => $data['represented'] ?? null,
            'biography' => $data['biography'] ?? null,
            'artist_statement' => $data['artist_statement'] ?? null,
            'cv' => $data['cv'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'studio_address' => $data['studio_address'] ?? null,
            'instagram' => $data['instagram'] ?? null,
            'twitter' => $data['twitter'] ?? null,
            'facebook' => $data['facebook'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update a gallery artist.
     */
    public function updateArtist(int $id, array $data): void
    {
        $update = ['updated_at' => now()];

        $fields = [
            'actor_id', 'display_name', 'sort_name', 'birth_date', 'birth_place',
            'death_date', 'death_place', 'nationality', 'artist_type', 'medium_specialty',
            'movement_style', 'active_period', 'represented', 'biography', 'artist_statement',
            'cv', 'email', 'phone', 'website', 'studio_address', 'instagram', 'twitter',
            'facebook', 'notes',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        if (array_key_exists('is_active', $data)) {
            $update['is_active'] = (bool) $data['is_active'];
        }

        DB::table('gallery_artist')->where('id', $id)->update($update);
    }

    /**
     * Delete a gallery artist.
     */
    public function deleteArtist(int $id): void
    {
        DB::table('gallery_artist')->where('id', $id)->delete();
    }

    // =========================================================================
    // Form Helpers
    // =========================================================================

    /**
     * Get dropdown choices for gallery artwork forms.
     */
    public function getFormChoices(string $culture = 'en'): array
    {
        // Level of description options (taxonomy_id = 34)
        $levels = DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', 34)
            ->where('term_i18n.culture', $culture)
            ->orderBy('term_i18n.name')
            ->select('term.id', 'term_i18n.name')
            ->get();

        // Repositories
        $repositories = DB::table('repository')
            ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
            ->where('actor_i18n.culture', $culture)
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->get();

        // Work types for gallery
        $workTypes = [
            'Painting',
            'Sculpture',
            'Drawing',
            'Print',
            'Photograph',
            'Mixed Media',
            'Installation',
            'Video Art',
            'Performance',
            'Other',
        ];

        // Creator roles for gallery
        $creatorRoles = [
            'Artist',
            'Collaborator',
            'Workshop',
            'Studio',
            'School of',
            'Circle of',
            'Follower of',
            'Attributed to',
            'After',
            'Unknown',
        ];

        // Artist types
        $artistTypes = [
            'Painter',
            'Sculptor',
            'Printmaker',
            'Photographer',
            'Mixed Media Artist',
            'Installation Artist',
            'Video Artist',
            'Performance Artist',
            'Ceramicist',
            'Textile Artist',
            'Digital Artist',
            'Other',
        ];

        return compact('levels', 'repositories', 'workTypes', 'creatorRoles', 'artistTypes');
    }

    /**
     * Get extra data needed for the edit form: physical location, display standards.
     */
    public function getEditExtras(?int $objectId, string $culture): array
    {
        // Physical objects for storage container dropdown
        $physicalObjects = [];
        try {
            $poResult = DB::table('physical_object as po')
                ->leftJoin('physical_object_i18n as poi', function ($join) use ($culture): void {
                    $join->on('poi.id', '=', 'po.id')->where('poi.culture', '=', $culture);
                })
                ->select(['po.id', 'poi.name', 'poi.location'])
                ->orderBy('poi.name')
                ->get();
            foreach ($poResult as $po) {
                $physicalObjects[$po->id] = $po->name . ($po->location ? ' (' . $po->location . ')' : '');
            }
        } catch (\Exception $e) {
            // Table may not exist
        }

        // Item location data
        $itemLocation = [];
        if ($objectId) {
            try {
                $loc = DB::table('item_physical_location')->where('object_id', $objectId)->first();
                if ($loc) {
                    $itemLocation = (array) $loc;
                }
            } catch (\Exception $e) {
                // Table may not exist
            }
        }

        // Display standards
        $displayStandards = [];
        try {
            $terms = DB::table('term')
                ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                ->where('term.taxonomy_id', 53)
                ->where('term_i18n.culture', $culture)
                ->orderBy('term_i18n.name')
                ->select('term.id', 'term_i18n.name')
                ->get();
            foreach ($terms as $t) {
                $displayStandards[$t->id] = $t->name;
            }
        } catch (\Exception $e) {
            // Taxonomy may not exist
        }

        // Current display standard
        $currentDisplayStandard = null;
        if ($objectId) {
            $currentDisplayStandard = DB::table('information_object')
                ->where('id', $objectId)
                ->value('display_standard_id');
        }

        // Source culture
        $sourceCulture = 'English';
        if ($objectId) {
            $sc = DB::table('information_object')->where('id', $objectId)->value('source_culture');
            if ($sc) {
                $sourceCulture = locale_get_display_language($sc, 'en') ?: $sc;
            }
        }

        return compact(
            'physicalObjects',
            'itemLocation',
            'displayStandards',
            'currentDisplayStandard',
            'sourceCulture'
        );
    }

    // =========================================================================
    // Dashboard Stats
    // =========================================================================

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        $totalItems = Schema::hasTable('gallery_artwork') ? DB::table('gallery_artwork')->count() : 0;
        $itemsWithMedia = Schema::hasTable('gallery_artwork') ? DB::table('gallery_artwork')->whereNotNull('digital_object_id')->count() : 0;
        $totalArtists = Schema::hasTable('gallery_artist') ? DB::table('gallery_artist')->count() : 0;
        $activeLoans = Schema::hasTable('gallery_loan') ? DB::table('gallery_loan')->where('status', 'active')->count() : 0;
        $recentItems = Schema::hasTable('gallery_artwork') ? DB::table('gallery_artwork')->orderBy('created_at', 'desc')->limit(10)->get() : collect();

        return compact('totalItems', 'itemsWithMedia', 'totalArtists', 'activeLoans', 'recentItems');
    }

    /**
     * Get reporting statistics.
     */
    public function getReportStats(): array
    {
        $stats = [
            'exhibitions' => ['total' => 0, 'open' => 0, 'planning' => 0, 'upcoming' => 0],
            'loans' => ['total' => 0, 'overdue' => 0],
            'valuations' => ['total' => 0, 'total_value' => 0],
        ];

        if (Schema::hasTable('gallery_exhibition')) {
            $stats['exhibitions']['total'] = DB::table('gallery_exhibition')->count();
        }
        if (Schema::hasTable('gallery_loan')) {
            $stats['loans']['total'] = DB::table('gallery_loan')->count();
        }
        if (Schema::hasTable('gallery_valuation')) {
            $stats['valuations']['total'] = DB::table('gallery_valuation')->count();
            $stats['valuations']['total_value'] = (float) DB::table('gallery_valuation')->sum('value');
        }

        return $stats;
    }
}
