<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenRiC\RecordManage\Contracts\BrowseServiceInterface;

/**
 * Browse service for records — full-text search, faceted browsing, advanced search.
 * Adapted from Heratio InformationObjectBrowseService (534 lines).
 *
 * Uses PostgreSQL ILIKE for case-insensitive search.
 * Tables: records, record_agents, record_access_points, record_events, record_notes.
 */
class BrowseService implements BrowseServiceInterface
{
    protected array $activeFilters = [];
    protected array $advancedCriteria = [];

    /**
     * Browse records with pagination, sorting, search, and filters.
     */
    public function browse(array $params): array
    {
        $page    = max(1, (int) ($params['page'] ?? 1));
        $limit   = max(1, (int) ($params['limit'] ?? 25));
        $offset  = ($page - 1) * $limit;
        $sort    = $params['sort'] ?? 'lastUpdated';
        $sortDir = strtolower($params['sortDir'] ?? '') === 'asc' ? 'asc' : 'desc';
        $search  = trim($params['subquery'] ?? '');

        $this->activeFilters   = $params['filters'] ?? [];
        $this->advancedCriteria = $params['advancedCriteria'] ?? [];

        $query = DB::table('records')
            ->select([
                'records.id',
                'records.iri',
                'records.identifier',
                'records.title',
                'records.level',
                'records.parent_id',
                'records.scope_and_content',
                'records.publication_status',
                'records.created_at',
                'records.updated_at',
            ])
            ->whereNull('records.deleted_at');

        $query = $this->applyFilters($query);
        $query = $this->applySearch($query, $search);
        $query = $this->applyAdvancedCriteria($query);
        $query = $this->applySort($query, $sort, $sortDir);

        $total = (clone $query)->count();
        $hits  = $query->offset($offset)->limit($limit)->get()->map(fn ($row) => $this->transformRow($row))->toArray();

        // Facets
        $facets = $this->buildFacets();

        return [
            'hits'  => $hits,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'facets' => $facets,
        ];
    }

    protected function applyFilters($query)
    {
        if (!empty($this->activeFilters['level'])) {
            $query->where('records.level', $this->activeFilters['level']);
        }
        if (!empty($this->activeFilters['repository_id'])) {
            $query->where('records.repository_id', $this->activeFilters['repository_id']);
        }
        if (!empty($this->activeFilters['top_level'])) {
            $query->whereNull('records.parent_id');
        }
        if (!empty($this->activeFilters['publication_status'])) {
            $query->where('records.publication_status', $this->activeFilters['publication_status']);
        }
        if (!empty($this->activeFilters['has_digital'])) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('digital_objects')
                    ->whereColumn('digital_objects.record_id', 'records.id');
            });
        }
        if (!empty($this->activeFilters['start_date'])) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('record_events')
                    ->whereColumn('record_events.record_id', 'records.id')
                    ->where('record_events.end_date', '>=', $this->activeFilters['start_date']);
            });
        }
        if (!empty($this->activeFilters['end_date'])) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('record_events')
                    ->whereColumn('record_events.record_id', 'records.id')
                    ->where('record_events.start_date', '<=', $this->activeFilters['end_date']);
            });
        }

        return $query;
    }

    protected function applySearch($query, string $search)
    {
        if ($search === '') {
            return $query;
        }

        $like = '%' . addcslashes($search, '%_') . '%';
        $query->where(function ($q) use ($like) {
            $q->where('records.title', 'ILIKE', $like)
              ->orWhere('records.identifier', 'ILIKE', $like)
              ->orWhere('records.alternate_title', 'ILIKE', $like)
              ->orWhere('records.scope_and_content', 'ILIKE', $like)
              ->orWhere('records.archival_history', 'ILIKE', $like)
              ->orWhere('records.extent_and_medium', 'ILIKE', $like)
              ->orWhere('records.acquisition', 'ILIKE', $like)
              ->orWhere('records.appraisal', 'ILIKE', $like)
              ->orWhere('records.accruals', 'ILIKE', $like)
              ->orWhere('records.arrangement', 'ILIKE', $like)
              ->orWhere('records.access_conditions', 'ILIKE', $like)
              ->orWhere('records.reproduction_conditions', 'ILIKE', $like)
              ->orWhere('records.physical_characteristics', 'ILIKE', $like)
              ->orWhere('records.finding_aids', 'ILIKE', $like)
              ->orWhere('records.location_of_originals', 'ILIKE', $like)
              ->orWhere('records.location_of_copies', 'ILIKE', $like)
              ->orWhere('records.related_units_of_description', 'ILIKE', $like)
              ->orWhere('records.rules', 'ILIKE', $like)
              ->orWhere('records.sources', 'ILIKE', $like)
              ->orWhere('records.revision_history', 'ILIKE', $like);

            // Notes
            $q->orWhereExists(function ($sub) use ($like) {
                $sub->select(DB::raw(1))
                    ->from('record_notes')
                    ->whereColumn('record_notes.record_id', 'records.id')
                    ->where('record_notes.content', 'ILIKE', $like);
            });

            // Creators / agents
            $q->orWhereExists(function ($sub) use ($like) {
                $sub->select(DB::raw(1))
                    ->from('record_agents')
                    ->whereColumn('record_agents.record_id', 'records.id')
                    ->where('record_agents.agent_name', 'ILIKE', $like);
            });

            // Subject access points
            $q->orWhereExists(function ($sub) use ($like) {
                $sub->select(DB::raw(1))
                    ->from('record_access_points')
                    ->whereColumn('record_access_points.record_id', 'records.id')
                    ->where('record_access_points.term_name', 'ILIKE', $like);
            });
        });

        return $query;
    }

    protected function applyAdvancedCriteria($query)
    {
        if (empty($this->advancedCriteria)) {
            return $query;
        }

        $fieldMap = [
            'title'                   => 'records.title',
            'scopeAndContent'         => 'records.scope_and_content',
            'archivalHistory'         => 'records.archival_history',
            'extentAndMedium'         => 'records.extent_and_medium',
            'arrangement'             => 'records.arrangement',
            'accessConditions'        => 'records.access_conditions',
            'reproductionConditions'  => 'records.reproduction_conditions',
            'physicalCharacteristics' => 'records.physical_characteristics',
            'findingAids'             => 'records.finding_aids',
            'locationOfOriginals'     => 'records.location_of_originals',
            'locationOfCopies'        => 'records.location_of_copies',
            'relatedUnits'            => 'records.related_units_of_description',
            'rules'                   => 'records.rules',
            'sources'                 => 'records.sources',
            'appraisal'               => 'records.appraisal',
            'accruals'                => 'records.accruals',
            'alternateTitle'          => 'records.alternate_title',
            'identifier'              => 'records.identifier',
            'acquisition'             => 'records.acquisition',
        ];

        foreach ($this->advancedCriteria as $i => $row) {
            $term = trim($row['query'] ?? '');
            if ($term === '') {
                continue;
            }

            $like = '%' . addcslashes($term, '%_') . '%';
            $field = $row['field'] ?? '';
            $operator = ($i === 0) ? 'and' : ($row['operator'] ?? 'and');

            $applyWhere = function ($q) use ($like, $field, $fieldMap) {
                if ($field && isset($fieldMap[$field])) {
                    $q->where($fieldMap[$field], 'ILIKE', $like);
                } elseif ($field === 'creatorSearch') {
                    $q->whereExists(function ($sub) use ($like) {
                        $sub->select(DB::raw(1))->from('record_agents')
                            ->whereColumn('record_agents.record_id', 'records.id')
                            ->where('record_agents.agent_name', 'ILIKE', $like);
                    });
                } elseif ($field === 'subjectSearch') {
                    $q->whereExists(function ($sub) use ($like) {
                        $sub->select(DB::raw(1))->from('record_access_points')
                            ->whereColumn('record_access_points.record_id', 'records.id')
                            ->where('record_access_points.type', 'subject')
                            ->where('record_access_points.term_name', 'ILIKE', $like);
                    });
                } elseif ($field === 'placeSearch') {
                    $q->whereExists(function ($sub) use ($like) {
                        $sub->select(DB::raw(1))->from('record_access_points')
                            ->whereColumn('record_access_points.record_id', 'records.id')
                            ->where('record_access_points.type', 'place')
                            ->where('record_access_points.term_name', 'ILIKE', $like);
                    });
                } elseif ($field === 'genreSearch') {
                    $q->whereExists(function ($sub) use ($like) {
                        $sub->select(DB::raw(1))->from('record_access_points')
                            ->whereColumn('record_access_points.record_id', 'records.id')
                            ->where('record_access_points.type', 'genre')
                            ->where('record_access_points.term_name', 'ILIKE', $like);
                    });
                } elseif ($field === 'noteContent') {
                    $q->whereExists(function ($sub) use ($like) {
                        $sub->select(DB::raw(1))->from('record_notes')
                            ->whereColumn('record_notes.record_id', 'records.id')
                            ->where('record_notes.content', 'ILIKE', $like);
                    });
                } else {
                    $q->where(function ($inner) use ($like, $fieldMap) {
                        foreach ($fieldMap as $col) {
                            $inner->orWhere($col, 'ILIKE', $like);
                        }
                    });
                }
            };

            if ($operator === 'not') {
                $query->where(fn ($q) => $q->whereNot(fn ($inner) => $applyWhere($inner)));
            } elseif ($operator === 'or') {
                $query->orWhere(fn ($q) => $applyWhere($q));
            } else {
                $query->where(fn ($q) => $applyWhere($q));
            }
        }

        return $query;
    }

    protected function applySort($query, string $sort, string $sortDir)
    {
        return match ($sort) {
            'alphabetic'    => $query->orderBy('records.title', $sortDir),
            'identifier'    => $query->orderBy('records.identifier', $sortDir)->orderBy('records.title', $sortDir),
            'referenceCode' => $query->orderBy('records.identifier', $sortDir),
            'startDate'     => $query->orderByRaw("(SELECT MIN(e.start_date) FROM record_events e WHERE e.record_id = records.id) {$sortDir}"),
            'endDate'       => $query->orderByRaw("(SELECT MAX(e.end_date) FROM record_events e WHERE e.record_id = records.id) {$sortDir}"),
            default         => $query->orderBy('records.updated_at', $sortDir),
        };
    }

    protected function transformRow(object $row): array
    {
        return [
            'id'                 => $row->id,
            'iri'                => $row->iri,
            'title'              => $row->title ?? '',
            'identifier'         => $row->identifier ?? '',
            'level'              => $row->level ?? null,
            'parent_id'          => $row->parent_id ?? null,
            'scope_and_content'  => Str::limit($row->scope_and_content ?? '', 200),
            'publication_status' => $row->publication_status ?? 'draft',
            'updated_at'         => $row->updated_at ?? '',
        ];
    }

    protected function buildFacets(): array
    {
        $facets = [];

        try {
            $facets['levels'] = [
                'label' => 'Level of description',
                'terms' => DB::table('records')
                    ->whereNotNull('level')
                    ->whereNull('deleted_at')
                    ->select('level as label', DB::raw('COUNT(*) as count'))
                    ->groupBy('level')
                    ->orderByDesc('count')
                    ->limit(20)
                    ->get()
                    ->map(fn ($t) => ['id' => $t->label, 'label' => $t->label, 'count' => $t->count])
                    ->toArray(),
            ];
        } catch (\Exception $e) {
            $facets['levels'] = ['label' => 'Level of description', 'terms' => []];
        }

        try {
            $facets['creators'] = [
                'label' => 'Creator',
                'terms' => DB::table('record_agents')
                    ->where('record_agents.relation_type', 'creator')
                    ->select('record_agents.agent_name as label', DB::raw('COUNT(DISTINCT record_agents.record_id) as count'))
                    ->groupBy('record_agents.agent_name')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->get()
                    ->map(fn ($t) => ['id' => $t->label, 'label' => $t->label, 'count' => $t->count])
                    ->toArray(),
            ];
        } catch (\Exception $e) {
            $facets['creators'] = ['label' => 'Creator', 'terms' => []];
        }

        try {
            $facets['subjects'] = [
                'label' => 'Subject',
                'terms' => DB::table('record_access_points')
                    ->where('type', 'subject')
                    ->select('term_name as label', DB::raw('COUNT(DISTINCT record_id) as count'))
                    ->groupBy('term_name')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->get()
                    ->map(fn ($t) => ['id' => $t->label, 'label' => $t->label, 'count' => $t->count])
                    ->toArray(),
            ];
        } catch (\Exception $e) {
            $facets['subjects'] = ['label' => 'Subject', 'terms' => []];
        }

        return $facets;
    }
}
