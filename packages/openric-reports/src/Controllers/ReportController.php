<?php

declare(strict_types=1);

namespace OpenRiC\Reports\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use OpenRiC\Reports\Contracts\ReportServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ReportController -- dashboard, entity reports, audit, spatial, user activity, CSV export.
 *
 * Adapted from Heratio ahg-reports ReportController (723 LOC).
 * All database queries use PostgreSQL syntax (ILIKE, jsonb operators).
 */
class ReportController extends Controller
{
    protected ReportServiceInterface $service;

    public function __construct(ReportServiceInterface $service)
    {
        $this->service = $service;
    }

    // =====================================================================
    //  Dashboard
    // =====================================================================

    public function dashboard(): View
    {
        $stats = $this->service->getDashboardStats();
        $creationStats = $this->service->getCreationStats('month');

        // Package detection for conditional dashboard sections
        $enabledPackages = [];
        try {
            if (Schema::hasTable('settings')) {
                $enabledPackages = DB::table('settings')
                    ->where('scope', 'packages')
                    ->where('value', 'enabled')
                    ->pluck('key')
                    ->flip()
                    ->toArray();
            }
        } catch (\Exception $e) {
            // ignore
        }
        $hasPackage = fn(string $name): bool => isset($enabledPackages[$name]);

        return view('reports::dashboard', compact('stats', 'creationStats', 'enabledPackages', 'hasPackage'));
    }

    public function index(): View
    {
        return view('reports::index');
    }

    // =====================================================================
    //  Entity Reports
    // =====================================================================

    public function descriptions(Request $request): View|StreamedResponse
    {
        $params = $request->only(['culture', 'dateStart', 'dateEnd', 'dateOf', 'level', 'publicationStatus', 'repositoryId', 'limit', 'page']);
        $data = $this->service->reportDescriptions($params);
        $levels = $this->service->getLevelsOfDescription();
        $repositories = $this->service->getRepositoryList();
        $cultures = $this->service->getAvailableCultures();

        if ($request->query('export') === 'csv') {
            return $this->service->exportCsv(
                $data['results']->toArray(),
                ['ID', 'Identifier', 'Title', 'Level', 'Status', 'Created', 'Updated'],
                'description-report.csv'
            );
        }

        return view('reports::report-descriptions', array_merge($data, [
            'params'       => $params,
            'levels'       => $levels,
            'repositories' => $repositories,
            'cultures'     => $cultures,
        ]));
    }

    public function agents(Request $request): View|StreamedResponse
    {
        $params = $request->only(['culture', 'dateStart', 'dateEnd', 'dateOf', 'entityType', 'limit', 'page']);
        $data = $this->service->reportAgents($params);
        $entityTypes = $this->service->getEntityTypes();
        $cultures = $this->service->getAvailableCultures();

        if ($request->query('export') === 'csv') {
            return $this->service->exportCsv(
                $data['results']->toArray(),
                ['ID', 'Name', 'Entity Type', 'Dates', 'Created', 'Updated'],
                'agent-report.csv'
            );
        }

        return view('reports::report-agents', array_merge($data, [
            'params'      => $params,
            'entityTypes' => $entityTypes,
            'cultures'    => $cultures,
        ]));
    }

    public function repositories(Request $request): View|StreamedResponse
    {
        $params = $request->only(['culture', 'dateStart', 'dateEnd', 'dateOf', 'limit', 'page']);
        $data = $this->service->reportRepositories($params);
        $cultures = $this->service->getAvailableCultures();

        if ($request->query('export') === 'csv') {
            return $this->service->exportCsv(
                $data['results']->toArray(),
                ['ID', 'Identifier', 'Name', 'Holdings', 'Created', 'Updated'],
                'repository-report.csv'
            );
        }

        return view('reports::report-repositories', array_merge($data, [
            'params'   => $params,
            'cultures' => $cultures,
        ]));
    }

    public function accessions(Request $request): View|StreamedResponse
    {
        $params = $request->only(['culture', 'dateStart', 'dateEnd', 'dateOf', 'limit', 'page']);
        $data = $this->service->reportAccessions($params);
        $cultures = $this->service->getAvailableCultures();

        if ($request->query('export') === 'csv') {
            return $this->service->exportCsv(
                $data['results']->toArray(),
                ['ID', 'Identifier', 'Title', 'Scope', 'Created', 'Updated'],
                'accession-report.csv'
            );
        }

        return view('reports::report-accessions', array_merge($data, [
            'params'   => $params,
            'cultures' => $cultures,
        ]));
    }

    public function donors(Request $request): View|StreamedResponse
    {
        $params = $request->only(['culture', 'dateStart', 'dateEnd', 'dateOf', 'limit', 'page']);
        $data = $this->service->reportDonors($params);
        $cultures = $this->service->getAvailableCultures();

        if ($request->query('export') === 'csv') {
            return $this->service->exportCsv(
                $data['results']->toArray(),
                ['ID', 'Name', 'Email', 'Phone', 'City', 'Created', 'Updated'],
                'donor-report.csv'
            );
        }

        return view('reports::report-donors', array_merge($data, [
            'params'   => $params,
            'cultures' => $cultures,
        ]));
    }

    public function storage(Request $request): View|StreamedResponse
    {
        $params = $request->only(['culture', 'dateStart', 'dateEnd', 'dateOf', 'limit', 'page']);
        $data = $this->service->reportPhysicalStorage($params);
        $cultures = $this->service->getAvailableCultures();

        if ($request->query('export') === 'csv') {
            return $this->service->exportCsv(
                $data['results']->toArray(),
                ['ID', 'Name', 'Type', 'Location', 'Created', 'Updated'],
                'physical-storage-report.csv'
            );
        }

        return view('reports::report-storage', array_merge($data, [
            'params'   => $params,
            'cultures' => $cultures,
        ]));
    }

    public function taxonomy(Request $request): View|StreamedResponse
    {
        $params = $request->only(['culture', 'dateStart', 'dateEnd', 'sort', 'limit', 'page']);
        $data = $this->service->reportTaxonomies($params);

        if ($request->query('export') === 'csv') {
            return $this->service->exportCsv(
                $data['results']->toArray(),
                ['ID', 'Name', 'Usage', 'Terms', 'Created', 'Updated'],
                'taxonomy-report.csv'
            );
        }

        return view('reports::report-taxonomy', array_merge($data, ['params' => $params]));
    }

    public function recent(Request $request): View|StreamedResponse
    {
        $params = $request->only(['dateStart', 'dateEnd', 'entityType', 'limit', 'page']);
        $data = $this->service->reportUpdates($params);

        if ($request->query('export') === 'csv') {
            return $this->service->exportCsv(
                $data['results']->toArray(),
                ['ID', 'Entity Type', 'Entity ID', 'Action', 'Created', 'Updated'],
                'recent-updates-report.csv'
            );
        }

        return view('reports::report-recent', array_merge($data, ['params' => $params]));
    }

    public function activity(Request $request): View
    {
        $params = $request->only(['dateStart', 'dateEnd', 'actionUser', 'userAction', 'limit', 'page']);
        $data = $this->service->reportUserActivity($params);
        $users = $this->service->getAuditUsers();

        return view('reports::report-activity', array_merge($data, [
            'params' => $params,
            'users'  => $users,
        ]));
    }

    // =====================================================================
    //  Access / Rights Report
    // =====================================================================

    public function access(Request $request): View|StreamedResponse
    {
        $params = $request->only(['culture', 'limit', 'page']);
        $data = $this->service->reportAccess($params);

        if ($request->query('export') === 'csv') {
            $rows = [];
            foreach ($data['rights_by_basis'] ?? [] as $basis => $count) {
                $rows[] = ['basis' => $basis, 'count' => $count];
            }
            return $this->service->exportCsv(
                $rows,
                ['Rights Basis', 'Count'],
                'access-report.csv'
            );
        }

        return view('reports::report-access', array_merge($data, ['params' => $params]));
    }

    // =====================================================================
    //  Spatial Analysis
    // =====================================================================

    public function spatialAnalysis(Request $request): View|StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $culture = $request->input('culture', 'en');
        $params = $request->only(['culture', 'place', 'level', 'subjects', 'topLevelOnly', 'requireCoordinates']);

        $spatialParams = [
            'culture'            => $culture,
            'placeIds'           => $request->input('place', []),
            'level'              => $request->input('level'),
            'subjects'           => $request->input('subjects', ''),
            'topLevelOnly'       => $request->boolean('topLevelOnly'),
            'requireCoordinates' => $request->boolean('requireCoordinates', true),
        ];

        $spatial = $this->service->reportSpatial($spatialParams);
        $placeTerms = $this->service->getPlaceTerms($culture);
        $levels = $this->service->getLevelsOfDescription($culture);

        // Handle exports on POST
        $export = $request->input('export');
        if ($request->isMethod('post') && $export === 'csv') {
            // Get full result set for export
            $fullParams = array_merge($spatialParams, ['limit' => 10000]);
            $fullResults = $this->service->reportSpatial($fullParams);
            return $this->service->exportCsv(
                $fullResults['results']->toArray(),
                ['ID', 'Identifier', 'Title', 'Latitude', 'Longitude', 'Level', 'Repository'],
                'spatial-analysis-export.csv'
            );
        }

        if ($request->isMethod('post') && $export === 'json') {
            $fullParams = array_merge($spatialParams, ['limit' => 10000]);
            $fullResults = $this->service->reportSpatial($fullParams);
            return $this->service->exportGeoJson($fullResults['results']);
        }

        return view('reports::report-spatial', [
            'preview'    => $spatial['results'],
            'totalCount' => $spatial['total'],
            'params'     => $params,
            'placeTerms' => $placeTerms,
            'levels'     => $levels,
        ]);
    }

    // =====================================================================
    //  Collections (SPARQL-based)
    // =====================================================================

    public function collections(Request $request): View|StreamedResponse
    {
        $data = $this->service->getCollectionStats();

        if ($request->input('export') === 'csv') {
            return $this->service->exportCsv($data, ['IRI', 'Title', 'Records'], 'collection-report.csv');
        }

        return view('reports::report-collections', compact('data'));
    }

    // =====================================================================
    //  Search Analytics
    // =====================================================================

    public function search(Request $request): View|StreamedResponse
    {
        $data = $this->service->getSearchStats();

        if ($request->input('export') === 'csv') {
            return $this->service->exportCsv($data['top_search_terms'] ?? [], ['Search Term', 'Count'], 'search-report.csv');
        }

        return view('reports::report-search', compact('data'));
    }

    // =====================================================================
    //  Browse & Report Select (matching Heratio)
    // =====================================================================

    public function browse(Request $request): View
    {
        $strongrooms = [];
        $locations = [];

        try {
            if (Schema::hasTable('physical_storage_i18n')) {
                $strongrooms = DB::table('physical_storage_i18n')
                    ->select('name')
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->distinct()
                    ->orderBy('name')
                    ->pluck('name')
                    ->toArray();

                $locations = DB::table('physical_storage_i18n')
                    ->select('location')
                    ->whereNotNull('location')
                    ->where('location', '!=', '')
                    ->distinct()
                    ->orderBy('location')
                    ->pluck('location')
                    ->toArray();
            }
        } catch (\Exception $e) {
            // ignore
        }

        return view('reports::browse', compact('strongrooms', 'locations'));
    }

    public function browsePublish(Request $request): View
    {
        $items = collect();
        return view('reports::browse-publish', compact('items'));
    }

    public function reportSelect(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $objectType = $request->input('objectType');

        if ($objectType) {
            $routeMap = [
                'description'      => 'reports.descriptions',
                'agent'            => 'reports.agents',
                'repository'       => 'reports.repositories',
                'accession'        => 'reports.accessions',
                'donor'            => 'reports.donors',
                'physical_storage' => 'reports.storage',
            ];

            if (isset($routeMap[$objectType])) {
                return redirect()->route($routeMap[$objectType]);
            }
        }

        return view('reports::report-select');
    }

    public function report(Request $request): View
    {
        $reportName = $request->input('name', 'Report');
        $results = [];
        $summary = [];

        return view('reports::report', compact('reportName', 'results', 'summary'));
    }

    // =====================================================================
    //  Audit Reports
    // =====================================================================

    public function auditAgents(Request $request): View
    {
        $records = $this->getAuditRecords($request, ['agents', 'agent_i18n']);
        return view('reports::audit-agents', compact('records'));
    }

    public function auditDescriptions(Request $request): View
    {
        $records = $this->getAuditRecords($request, ['record_descriptions', 'record_description_i18n']);
        return view('reports::audit-descriptions', compact('records'));
    }

    public function auditDonors(Request $request): View
    {
        $records = $this->getAuditRecords($request, ['donors', 'donor_i18n']);
        return view('reports::audit-donors', compact('records'));
    }

    public function auditPermissions(Request $request): View
    {
        $records = $this->getAuditRecords($request, ['permissions', 'roles', 'role_user']);
        return view('reports::audit-permissions', compact('records'));
    }

    public function auditPhysicalStorage(Request $request): View
    {
        $records = $this->getAuditRecords($request, ['physical_storage_locations', 'physical_storage_i18n']);
        return view('reports::audit-physical-storage', compact('records'));
    }

    public function auditRepositories(Request $request): View
    {
        $records = $this->getAuditRecords($request, ['repositories', 'repository_i18n']);
        return view('reports::audit-repositories', compact('records'));
    }

    public function auditTaxonomies(Request $request): View
    {
        $records = $this->getAuditRecords($request, ['terms', 'term_i18n', 'taxonomies']);
        return view('reports::audit-taxonomies', compact('records'));
    }

    /**
     * Helper: get audit trail records for given DB tables.
     */
    private function getAuditRecords(Request $request, array $tables): \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection
    {
        try {
            if (!Schema::hasTable('audit_log')) {
                return collect();
            }

            $query = DB::table('audit_log')
                ->whereIn('entity_type', $tables)
                ->select(
                    'id',
                    DB::raw("COALESCE((SELECT name FROM users WHERE users.id = audit_log.user_id), 'system') AS username"),
                    'action',
                    'created_at as action_date_time',
                    'entity_id as record_id',
                    'entity_type as db_table',
                    DB::raw("COALESCE(new_values::text, '') as db_query")
                );

            if ($request->filled('dateStart')) {
                $query->where('created_at', '>=', $request->input('dateStart') . ' 00:00:00');
            }
            if ($request->filled('dateEnd')) {
                $query->where('created_at', '<=', $request->input('dateEnd') . ' 23:59:59');
            }

            return $query->orderByDesc('created_at')
                ->paginate((int) $request->input('limit', 25));
        } catch (\Exception $e) {
            return collect();
        }
    }

    // =====================================================================
    //  Generic Export
    // =====================================================================

    public function export(Request $request): StreamedResponse
    {
        $type = $request->input('type', 'dashboard');

        $data = match ($type) {
            'collections' => $this->service->getCollectionStats(),
            'users'       => $this->service->reportUserActivity([])['results']->toArray(),
            'search'      => $this->service->getSearchStats()['top_search_terms'] ?? [],
            default       => [['metric' => 'total_entities', 'value' => $this->service->getDashboardStats()['total_entities'] ?? 0]],
        };

        $headers = match ($type) {
            'collections' => ['IRI', 'Title', 'Records'],
            'users'       => ['ID', 'Username', 'Action', 'Entity', 'Date'],
            'search'      => ['Search Term', 'Count'],
            default       => ['Metric', 'Value'],
        };

        return $this->service->exportCsv($data, $headers, "{$type}-report.csv");
    }
}
