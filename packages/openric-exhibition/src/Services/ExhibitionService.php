<?php

declare(strict_types=1);

namespace OpenRiC\Exhibition\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenRiC\Exhibition\Contracts\ExhibitionServiceInterface;

/**
 * Exhibition management service — adapted from Heratio ahg-exhibition ExhibitionService (197 lines).
 *
 * Manages exhibitions with objects, storylines, sections, events, and checklists.
 * Uses PostgreSQL via Illuminate DB. Exhibition objects reference RiC entity IRIs.
 */
class ExhibitionService implements ExhibitionServiceInterface
{
    public function search(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $query = DB::table('exhibitions');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['exhibition_type'])) {
            $query->where('exhibition_type', $filters['exhibition_type']);
        }
        if (!empty($filters['year'])) {
            $query->whereYear('start_date', (int) $filters['year']);
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('title', 'ILIKE', $like)
                  ->orWhere('description', 'ILIKE', $like)
                  ->orWhere('theme', 'ILIKE', $like)
                  ->orWhere('curator', 'ILIKE', $like);
            });
        }

        $total = $query->count();
        $results = $query->orderByDesc('created_at')->offset($offset)->limit($limit)->get();

        return ['results' => $results, 'total' => $total];
    }

    public function get(int $id, bool $withRelated = false): ?object
    {
        $exhibition = DB::table('exhibitions')->where('id', $id)->first();

        if ($exhibition && $withRelated) {
            $exhibition->objects = $this->getObjects($id);
            $exhibition->storylines = $this->getStorylines($id);
            $exhibition->sections = $this->getSections($id);
            $exhibition->events = $this->getEvents($id);
            $exhibition->checklists = $this->getChecklists($id);
        }

        return $exhibition;
    }

    public function getBySlug(string $slug): ?object
    {
        return DB::table('exhibitions')->where('slug', $slug)->first();
    }

    public function create(array $data): int
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['title'] ?? 'exhibition');
        }

        return (int) DB::table('exhibitions')->insertGetId($data);
    }

    public function update(int $id, array $data): void
    {
        $data['updated_at'] = now();

        if (isset($data['title']) && !isset($data['slug'])) {
            $existing = DB::table('exhibitions')->where('id', $id)->first();
            if ($existing && Str::slug($data['title']) !== $existing->slug) {
                $data['slug'] = $this->generateUniqueSlug($data['title'], $id);
            }
        }

        DB::table('exhibitions')->where('id', $id)->update($data);
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            DB::table('exhibition_objects')->where('exhibition_id', $id)->delete();
            DB::table('exhibition_storylines')->where('exhibition_id', $id)->delete();
            DB::table('exhibition_sections')->where('exhibition_id', $id)->delete();
            DB::table('exhibition_events')->where('exhibition_id', $id)->delete();
            DB::table('exhibition_checklists')->where('exhibition_id', $id)->delete();
            DB::table('exhibitions')->where('id', $id)->delete();
        });
    }

    public function getTypes(): array
    {
        return [
            'temporary'  => 'Temporary',
            'permanent'  => 'Permanent',
            'travelling' => 'Travelling',
            'virtual'    => 'Virtual',
            'pop_up'     => 'Pop-up',
        ];
    }

    public function getStatuses(): array
    {
        return [
            'planning'    => 'Planning',
            'preparation' => 'In Preparation',
            'active'      => 'Active',
            'completed'   => 'Completed',
            'archived'    => 'Archived',
        ];
    }

    public function getStatistics(): array
    {
        return [
            'total'     => DB::table('exhibitions')->count(),
            'active'    => DB::table('exhibitions')->where('status', 'active')->count(),
            'planning'  => DB::table('exhibitions')->where('status', 'planning')->count(),
            'completed' => DB::table('exhibitions')->where('status', 'completed')->count(),
        ];
    }

    // ─── Objects ────────────────────────────────────────────────────────

    public function getObjects(int $exhibitionId): Collection
    {
        return DB::table('exhibition_objects')
            ->where('exhibition_id', $exhibitionId)
            ->orderBy('sort_order')
            ->get();
    }

    public function addObject(int $exhibitionId, array $data): int
    {
        $maxSort = DB::table('exhibition_objects')
            ->where('exhibition_id', $exhibitionId)
            ->max('sort_order') ?? 0;

        return (int) DB::table('exhibition_objects')->insertGetId([
            'exhibition_id'  => $exhibitionId,
            'entity_iri'     => $data['entity_iri'] ?? '',
            'entity_type'    => $data['entity_type'] ?? 'Record',
            'title'          => $data['title'] ?? '',
            'identifier'     => $data['identifier'] ?? '',
            'section'        => $data['section'] ?? '',
            'status'         => $data['status'] ?? 'pending',
            'notes'          => $data['notes'] ?? '',
            'thumbnail_url'  => $data['thumbnail_url'] ?? null,
            'sort_order'     => ($data['sort_order'] ?? $maxSort + 1),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function removeObject(int $exhibitionId, int $objectId): void
    {
        DB::table('exhibition_objects')
            ->where('exhibition_id', $exhibitionId)
            ->where('id', $objectId)
            ->delete();
    }

    public function reorderObjects(int $exhibitionId, array $objectIds): void
    {
        DB::transaction(function () use ($exhibitionId, $objectIds): void {
            foreach ($objectIds as $position => $objectId) {
                DB::table('exhibition_objects')
                    ->where('exhibition_id', $exhibitionId)
                    ->where('id', (int) $objectId)
                    ->update(['sort_order' => $position + 1]);
            }
        });
    }

    // ─── Storylines ────────────────────────────────────────────────────

    public function getStorylines(int $exhibitionId): Collection
    {
        return DB::table('exhibition_storylines')
            ->where('exhibition_id', $exhibitionId)
            ->orderBy('sort_order')
            ->get();
    }

    public function getStoryline(int $id): ?object
    {
        return DB::table('exhibition_storylines')->where('id', $id)->first();
    }

    public function createStoryline(int $exhibitionId, array $data): int
    {
        $maxSort = DB::table('exhibition_storylines')
            ->where('exhibition_id', $exhibitionId)
            ->max('sort_order') ?? 0;

        return (int) DB::table('exhibition_storylines')->insertGetId([
            'exhibition_id' => $exhibitionId,
            'title'         => $data['title'] ?? '',
            'description'   => $data['description'] ?? '',
            'sort_order'    => ($data['sort_order'] ?? $maxSort + 1),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function updateStoryline(int $id, array $data): void
    {
        $data['updated_at'] = now();
        DB::table('exhibition_storylines')->where('id', $id)->update($data);
    }

    public function deleteStoryline(int $id): void
    {
        DB::table('exhibition_storylines')->where('id', $id)->delete();
    }

    // ─── Sections ──────────────────────────────────────────────────────

    public function getSections(int $exhibitionId): Collection
    {
        return DB::table('exhibition_sections')
            ->where('exhibition_id', $exhibitionId)
            ->orderBy('sort_order')
            ->get();
    }

    public function createSection(int $exhibitionId, array $data): int
    {
        $maxSort = DB::table('exhibition_sections')
            ->where('exhibition_id', $exhibitionId)
            ->max('sort_order') ?? 0;

        return (int) DB::table('exhibition_sections')->insertGetId([
            'exhibition_id' => $exhibitionId,
            'title'         => $data['title'] ?? '',
            'description'   => $data['description'] ?? '',
            'location'      => $data['location'] ?? '',
            'sort_order'    => ($data['sort_order'] ?? $maxSort + 1),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function updateSection(int $id, array $data): void
    {
        $data['updated_at'] = now();
        DB::table('exhibition_sections')->where('id', $id)->update($data);
    }

    public function deleteSection(int $id): void
    {
        DB::table('exhibition_sections')->where('id', $id)->delete();
    }

    // ─── Events ────────────────────────────────────────────────────────

    public function getEvents(int $exhibitionId): Collection
    {
        return DB::table('exhibition_events')
            ->where('exhibition_id', $exhibitionId)
            ->orderBy('event_date')
            ->get();
    }

    public function createEvent(int $exhibitionId, array $data): int
    {
        return (int) DB::table('exhibition_events')->insertGetId([
            'exhibition_id' => $exhibitionId,
            'title'         => $data['title'] ?? '',
            'description'   => $data['description'] ?? '',
            'event_date'    => $data['event_date'] ?? null,
            'event_time'    => $data['event_time'] ?? null,
            'location'      => $data['location'] ?? '',
            'event_type'    => $data['event_type'] ?? 'general',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function updateEvent(int $id, array $data): void
    {
        $data['updated_at'] = now();
        DB::table('exhibition_events')->where('id', $id)->update($data);
    }

    public function deleteEvent(int $id): void
    {
        DB::table('exhibition_events')->where('id', $id)->delete();
    }

    // ─── Checklists ────────────────────────────────────────────────────

    public function getChecklists(int $exhibitionId): Collection
    {
        return DB::table('exhibition_checklists')
            ->where('exhibition_id', $exhibitionId)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get();
    }

    public function createChecklist(int $exhibitionId, array $data): int
    {
        $maxSort = DB::table('exhibition_checklists')
            ->where('exhibition_id', $exhibitionId)
            ->max('sort_order') ?? 0;

        return (int) DB::table('exhibition_checklists')->insertGetId([
            'exhibition_id' => $exhibitionId,
            'title'         => $data['title'] ?? '',
            'category'      => $data['category'] ?? 'general',
            'is_completed'  => $data['is_completed'] ?? false,
            'assigned_to'   => $data['assigned_to'] ?? null,
            'due_date'      => $data['due_date'] ?? null,
            'notes'         => $data['notes'] ?? '',
            'sort_order'    => ($data['sort_order'] ?? $maxSort + 1),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function updateChecklist(int $id, array $data): void
    {
        $data['updated_at'] = now();
        DB::table('exhibition_checklists')->where('id', $id)->update($data);
    }

    public function deleteChecklist(int $id): void
    {
        DB::table('exhibition_checklists')->where('id', $id)->delete();
    }

    // ─── CSV Export ────────────────────────────────────────────────────

    public function exportObjectListCsv(int $exhibitionId): string
    {
        $objects = $this->getObjects($exhibitionId);
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['#', 'Title', 'Identifier', 'Entity IRI', 'Section', 'Status', 'Notes']);

        foreach ($objects as $i => $obj) {
            fputcsv($output, [
                $i + 1,
                $obj->title ?? 'Untitled',
                $obj->identifier ?? '',
                $obj->entity_iri ?? '',
                $obj->section ?? '',
                $obj->status ?? '',
                $obj->notes ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    // ─── Helpers ───────────────────────────────────────────────────────

    private function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($title);
        if (empty($baseSlug)) {
            $baseSlug = 'exhibition';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $query = DB::table('exhibitions')->where('slug', $slug);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
            if (!$query->exists()) {
                break;
            }
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
