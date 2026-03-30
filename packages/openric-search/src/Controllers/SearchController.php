<?php

declare(strict_types=1);

namespace OpenRiC\Search\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use OpenRiC\Search\Contracts\SearchServiceInterface;
use OpenRiC\Search\Services\ElasticsearchFacetService;

/**
 * SearchController -- full-text search, advanced search, autocomplete,
 * description updates, global search/replace.
 *
 * Adapted from Heratio ahg-search SearchController (631 LOC).
 * PostgreSQL ILIKE for case-insensitive matching.
 */
class SearchController extends Controller
{
    public function __construct(
        private readonly SearchServiceInterface $searchService,
        private readonly ElasticsearchFacetService $facetService,
    ) {}

    // =====================================================================
    //  Full-text search with faceted filtering
    // =====================================================================

    public function index(Request $request): View
    {
        $query   = trim((string) $request->input('q', ''));
        $page    = max(1, (int) $request->input('page', 1));
        $limit   = 30;
        $repo    = $request->input('repository') ? (int) $request->input('repository') : null;
        $level   = $request->input('level') ? (int) $request->input('level') : null;
        $dateFrom = $request->input('dateFrom') ?: null;
        $dateTo  = $request->input('dateTo') ?: null;
        $hasDo   = $request->has('hasDigitalObject') ? (bool) $request->input('hasDigitalObject') : null;
        $mediaType = $request->input('mediaType') ? (int) $request->input('mediaType') : null;
        $sort    = (string) $request->input('sort', 'relevance');

        $hasFilters = $repo || $level || $dateFrom || $dateTo || $hasDo !== null || $mediaType;

        // No query and no filters -- show empty search page
        if ($query === '' && !$hasFilters) {
            return view('search::index', [
                'query'         => '',
                'items'         => [],
                'total'         => 0,
                'aggregations'  => [],
                'activeFilters' => [],
                'sort'          => $sort,
                'page'          => $page,
                'limit'         => $limit,
                'lastPage'      => 1,
            ]);
        }

        // Use ES faceted search with DB fallback
        $results = $this->facetService->advancedSearch([
            'query'            => $query,
            'repository'       => $repo,
            'level'            => $level,
            'dateFrom'         => $dateFrom,
            'dateTo'           => $dateTo,
            'hasDigitalObject' => $hasDo,
            'mediaType'        => $mediaType,
            'sort'             => $sort,
            'page'             => $page,
            'limit'            => $limit,
        ]);

        $activeFilters = $this->buildActiveFilters(
            $repo, $level, $dateFrom, $dateTo, $hasDo, $mediaType,
            $results['aggregations'] ?? []
        );

        $total    = $results['total'];
        $lastPage = max(1, (int) ceil($total / $limit));

        return view('search::index', [
            'query'         => $query,
            'items'         => $results['hits'],
            'total'         => $total,
            'aggregations'  => $results['aggregations'] ?? [],
            'activeFilters' => $activeFilters,
            'sort'          => $sort,
            'page'          => $page,
            'limit'         => $limit,
            'lastPage'      => $lastPage,
        ]);
    }

    // =====================================================================
    //  Advanced search form
    // =====================================================================

    public function advanced(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $repositories = $this->facetService->getRepositoryList();
        $levels       = $this->facetService->getLevelsOfDescription();
        $mediaTypes   = $this->facetService->getMediaTypes();

        // If the form was submitted, redirect to the main search with params
        if ($request->has('submitted')) {
            $params = array_filter([
                'q'                => $request->input('q'),
                'repository'       => $request->input('repository'),
                'level'            => $request->input('level'),
                'dateFrom'         => $request->input('dateFrom'),
                'dateTo'           => $request->input('dateTo'),
                'hasDigitalObject' => $request->input('hasDigitalObject'),
                'mediaType'        => $request->input('mediaType'),
                'sort'             => $request->input('sort'),
            ], fn($v) => $v !== null && $v !== '');

            return redirect()->route('search', $params);
        }

        return view('search::advanced', [
            'repositories' => $repositories,
            'levels'       => $levels,
            'mediaTypes'   => $mediaTypes,
            'query'        => (string) $request->input('q', ''),
            'sort'         => (string) $request->input('sort', 'relevance'),
        ]);
    }

    // =====================================================================
    //  Autocomplete JSON endpoint
    // =====================================================================

    public function autocomplete(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));

        if ($query === '' || mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $suggestions = $this->searchService->suggest($query, 10);

        $results = [];
        foreach ($suggestions as $s) {
            $results[] = [
                'title'      => $s['text'] ?? '[Untitled]',
                'iri'        => $s['iri'] ?? '',
                'type'       => $s['type'] ?? 'Record',
                'identifier' => null,
            ];
        }

        return response()->json($results);
    }

    // =====================================================================
    //  Suggestion (alias for search.suggest)
    // =====================================================================

    public function suggest(Request $request): JsonResponse
    {
        return $this->autocomplete($request);
    }

    // =====================================================================
    //  Description Updates -- recently modified records
    // =====================================================================

    public function descriptionUpdates(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $entityType        = (string) $request->input('entityType', '');
        $dateStart         = (string) $request->input('dateStart', '');
        $dateEnd           = (string) $request->input('dateEnd', '');
        $dateOf            = (string) $request->input('dateOf', 'updated');
        $publicationStatus = (string) $request->input('publicationStatus', '');
        $userName          = (string) $request->input('user', '');
        $page              = max(1, (int) $request->input('page', 1));
        $limit             = 25;

        $entityTypes = [
            ''                 => 'All',
            'record_set'       => 'Record sets',
            'record'           => 'Records',
            'agent'            => 'Agents',
            'repository'       => 'Repositories',
            'term'             => 'Terms',
        ];

        $hasAuditLog = Schema::hasTable('audit_log');

        if ($hasAuditLog) {
            $results = $this->descriptionUpdatesFromAuditLog(
                $entityType, $dateStart, $dateEnd, $dateOf, $userName, $page, $limit
            );
        } else {
            $results = $this->descriptionUpdatesFromDb(
                $entityType, $dateStart, $dateEnd, $dateOf, $publicationStatus, $page, $limit
            );
        }

        // Get users for the filter dropdown
        $users = [];
        try {
            if ($hasAuditLog) {
                $users = DB::table('audit_log')
                    ->select('user_id')
                    ->distinct()
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
                    ->mapWithKeys(function ($userId) {
                        $name = DB::table('users')->where('id', $userId)->value('name');
                        return [$userId => $name ?? "User {$userId}"];
                    })
                    ->toArray();
            }
        } catch (\Exception $e) {
            // ignore
        }

        return view('search::description-updates', [
            'results'           => $results['items'],
            'total'             => $results['total'],
            'page'              => $page,
            'limit'             => $limit,
            'lastPage'          => max(1, (int) ceil($results['total'] / $limit)),
            'entityTypes'       => $entityTypes,
            'users'             => $users,
            'entityType'        => $entityType,
            'dateStart'         => $dateStart,
            'dateEnd'           => $dateEnd,
            'dateOf'            => $dateOf,
            'publicationStatus' => $publicationStatus,
            'userName'          => $userName,
        ]);
    }

    /**
     * Query audit_log for description updates.
     */
    protected function descriptionUpdatesFromAuditLog(
        string $entityType, string $dateStart, string $dateEnd,
        string $dateOf, string $userName, int $page, int $limit
    ): array {
        $query = DB::table('audit_log')
            ->select([
                'audit_log.id',
                'audit_log.entity_type',
                'audit_log.entity_id',
                'audit_log.action',
                'audit_log.user_id',
                'audit_log.created_at',
                DB::raw("COALESCE(audit_log.new_values::text, '{}') as metadata"),
            ])
            ->whereIn('audit_log.action', ['create', 'update'])
            ->orderBy('audit_log.created_at', 'desc');

        if ($entityType !== '') {
            $tableMap = [
                'record_set'  => ['record_descriptions', 'record_description_i18n'],
                'record'      => ['record_descriptions', 'record_description_i18n'],
                'agent'       => ['agents', 'agent_i18n'],
                'repository'  => ['repositories', 'repository_i18n'],
                'term'        => ['terms', 'term_i18n'],
            ];
            if (isset($tableMap[$entityType])) {
                $query->whereIn('audit_log.entity_type', $tableMap[$entityType]);
            }
        }

        if ($dateStart !== '') {
            $query->where('audit_log.created_at', '>=', $dateStart . ' 00:00:00');
        }
        if ($dateEnd !== '') {
            $query->where('audit_log.created_at', '<=', $dateEnd . ' 23:59:59');
        }
        if ($userName !== '') {
            $query->where('audit_log.user_id', (int) $userName);
        }

        $total  = $query->count();
        $offset = ($page - 1) * $limit;
        $rows   = $query->offset($offset)->limit($limit)->get();

        $items = $rows->map(function ($row) {
            $userName = DB::table('users')->where('id', $row->user_id)->value('name') ?? '';
            return (object) [
                'title'       => $this->extractTitleFromMetadata($row->metadata),
                'slug'        => '',
                'entity_type' => $this->humanEntityType($row->entity_type),
                'class_name'  => $row->entity_type,
                'repository'  => '',
                'date'        => $row->created_at,
                'username'    => $userName,
                'action'      => $row->action,
            ];
        });

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Fallback: query entity tables directly for description updates.
     */
    protected function descriptionUpdatesFromDb(
        string $entityType, string $dateStart, string $dateEnd,
        string $dateOf, string $publicationStatus, int $page, int $limit
    ): array {
        $dateColumn = $dateOf === 'created' ? 'created_at' : 'updated_at';

        // Query record_descriptions as the primary source
        $query = DB::table('record_descriptions')
            ->select([
                'record_descriptions.id',
                'record_descriptions.created_at',
                'record_descriptions.updated_at',
                DB::raw("'record_description' as entity_type"),
            ])
            ->orderBy("record_descriptions.{$dateColumn}", 'desc');

        if ($dateStart !== '') {
            $query->where("record_descriptions.{$dateColumn}", '>=', $dateStart . ' 00:00:00');
        }
        if ($dateEnd !== '') {
            $query->where("record_descriptions.{$dateColumn}", '<=', $dateEnd . ' 23:59:59');
        }

        $total  = $query->count();
        $offset = ($page - 1) * $limit;
        $rows   = $query->offset($offset)->limit($limit)->get();

        $items = $rows->map(function ($row) {
            $title = DB::table('record_description_i18n')
                ->where('record_description_id', $row->id)
                ->where('culture', 'en')
                ->value('title') ?? '[Untitled]';

            return (object) [
                'title'       => $title,
                'slug'        => '',
                'entity_type' => $this->humanEntityType($row->entity_type),
                'class_name'  => $row->entity_type,
                'repository'  => '',
                'date'        => $row->updated_at,
                'username'    => '',
                'action'      => '',
            ];
        });

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Extract title from audit log metadata JSON.
     */
    protected function extractTitleFromMetadata(string $metadata): string
    {
        $decoded = json_decode($metadata, true);
        if ($decoded && isset($decoded['title'])) {
            return (string) $decoded['title'];
        }
        if ($decoded && isset($decoded['name'])) {
            return (string) $decoded['name'];
        }
        if ($decoded && isset($decoded['authorized_form_of_name'])) {
            return (string) $decoded['authorized_form_of_name'];
        }
        return '[Untitled]';
    }

    /**
     * Convert entity type / table name to human-readable label.
     */
    protected function humanEntityType(string $className): string
    {
        return match ($className) {
            'record_descriptions', 'record_description_i18n', 'record_set' => 'Record description',
            'agents', 'agent_i18n', 'agent'                                => 'Agent',
            'repositories', 'repository_i18n', 'repository'                => 'Repository',
            'terms', 'term_i18n', 'term'                                   => 'Term',
            'donors', 'donor_i18n'                                         => 'Donor',
            default                                                         => ucfirst(str_replace('_', ' ', $className)),
        };
    }

    // =====================================================================
    //  Global search/replace in text fields
    // =====================================================================

    public function globalReplace(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Available i18n columns for replacement (PostgreSQL column names)
        $columns = [
            'title'                    => 'Title',
            'alternate_title'          => 'Alternate title',
            'extent_and_medium'        => 'Extent and medium',
            'archival_history'         => 'Archival history',
            'acquisition'              => 'Acquisition',
            'scope_and_content'        => 'Scope and content',
            'appraisal'                => 'Appraisal',
            'accruals'                 => 'Accruals',
            'arrangement'              => 'Arrangement',
            'access_conditions'        => 'Access conditions',
            'reproduction_conditions'  => 'Reproduction conditions',
            'physical_characteristics' => 'Physical characteristics',
            'finding_aids'             => 'Finding aids',
            'location_of_originals'    => 'Location of originals',
            'location_of_copies'       => 'Location of copies',
            'related_units'            => 'Related units of description',
            'rules'                    => 'Rules',
            'sources'                  => 'Sources',
            'revision_history'         => 'Revision history',
        ];

        if ($request->isMethod('get')) {
            return view('search::global-replace', [
                'columns'  => $columns,
                'results'  => null,
                'replaced' => false,
                'count'    => 0,
            ]);
        }

        // POST -- either preview or execute
        $request->validate([
            'column'      => 'required|in:' . implode(',', array_keys($columns)),
            'pattern'     => 'required|string|min:1',
            'replacement' => 'present|string',
        ]);

        $column        = $request->input('column');
        $pattern       = (string) $request->input('pattern');
        $replacement   = (string) $request->input('replacement', '');
        $caseSensitive = $request->boolean('caseSensitive', true);
        $confirm       = $request->boolean('confirm', false);

        // PostgreSQL: ILIKE for case-insensitive, LIKE for case-sensitive
        $likeOp = $caseSensitive ? 'LIKE' : 'ILIKE';

        // Preview: find affected records
        $affected = DB::table('record_description_i18n')
            ->where('culture', 'en')
            ->where($column, $likeOp, '%' . $pattern . '%')
            ->select([
                'record_description_id as id',
                'title',
                "{$column} as field_value",
            ])
            ->limit(500)
            ->get();

        $totalAffected = DB::table('record_description_i18n')
            ->where('culture', 'en')
            ->where($column, $likeOp, '%' . $pattern . '%')
            ->count();

        if ($confirm && $totalAffected > 0) {
            // Execute the replacement using PostgreSQL REPLACE()
            $updatedCount = DB::table('record_description_i18n')
                ->where('culture', 'en')
                ->where($column, $likeOp, '%' . $pattern . '%')
                ->update([
                    $column => DB::raw("REPLACE(\"{$column}\", " . DB::getPdo()->quote($pattern) . ", " . DB::getPdo()->quote($replacement) . ")"),
                ]);

            return redirect()->route('search.globalReplace')
                ->with('success', "Successfully replaced {$updatedCount} record(s). Column: {$columns[$column]}.");
        }

        // Preview mode -- show affected records with snippets
        $previewResults = $affected->map(function ($row) use ($column, $pattern, $replacement) {
            $currentValue = $row->field_value ?? '';
            $pos = mb_stripos($currentValue, $pattern);
            $start = max(0, ($pos !== false ? $pos : 0) - 50);
            $snippet = mb_strlen($currentValue) > 200
                ? '...' . mb_substr($currentValue, $start, 200) . '...'
                : $currentValue;
            $newSnippet = str_ireplace($pattern, $replacement, $snippet);

            return (object) [
                'id'            => $row->id,
                'title'         => $row->title ?: '[Untitled]',
                'slug'          => '',
                'current_value' => $snippet,
                'new_value'     => $newSnippet,
            ];
        });

        return view('search::global-replace', [
            'columns'       => $columns,
            'results'       => $previewResults,
            'replaced'      => false,
            'count'         => $totalAffected,
            'column'        => $column,
            'pattern'       => $pattern,
            'replacement'   => $replacement,
            'caseSensitive' => $caseSensitive,
        ]);
    }

    // =====================================================================
    //  Active filter label builder
    // =====================================================================

    protected function buildActiveFilters(
        ?int $repo, ?int $level, ?string $dateFrom, ?string $dateTo,
        ?bool $hasDo, ?int $mediaType, array $aggregations
    ): array {
        $filters = [];

        if ($repo) {
            $label = '[Unknown repository]';
            foreach ($aggregations['repositories'] ?? [] as $r) {
                if ((int) ($r['id'] ?? 0) === $repo) {
                    $label = $r['label'] ?? $label;
                    break;
                }
            }
            if ($label === '[Unknown repository]') {
                $repos = $this->facetService->getRepositoryList();
                $label = $repos[$repo] ?? $label;
            }
            $filters[] = ['param' => 'repository', 'label' => 'Repository: ' . $label];
        }

        if ($level) {
            $label = '[Unknown level]';
            foreach ($aggregations['levels'] ?? [] as $l) {
                if ((int) ($l['id'] ?? 0) === $level) {
                    $label = $l['label'] ?? $label;
                    break;
                }
            }
            if ($label === '[Unknown level]') {
                $levels = $this->facetService->getLevelsOfDescription();
                $label = $levels[$level] ?? $label;
            }
            $filters[] = ['param' => 'level', 'label' => 'Level: ' . $label];
        }

        if ($dateFrom) {
            $filters[] = ['param' => 'dateFrom', 'label' => 'From: ' . $dateFrom];
        }

        if ($dateTo) {
            $filters[] = ['param' => 'dateTo', 'label' => 'To: ' . $dateTo];
        }

        if ($hasDo !== null) {
            $filters[] = ['param' => 'hasDigitalObject', 'label' => $hasDo ? 'Has digital object' : 'No digital object'];
        }

        if ($mediaType) {
            $label = '[Unknown media type]';
            foreach ($aggregations['mediaTypes'] ?? [] as $m) {
                if ((int) ($m['id'] ?? 0) === $mediaType) {
                    $label = $m['label'] ?? $label;
                    break;
                }
            }
            $filters[] = ['param' => 'mediaType', 'label' => 'Media: ' . $label];
        }

        return $filters;
    }
}
