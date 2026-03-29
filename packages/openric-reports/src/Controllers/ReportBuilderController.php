<?php

declare(strict_types=1);

namespace OpenRiC\Reports\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * ReportBuilderController -- custom report builder with CRUD, query builder,
 * scheduling, sharing, templates, versioning, sections, widgets, attachments.
 *
 * Adapted from Heratio ahg-reports ReportBuilderController (1375 LOC).
 * All queries use PostgreSQL syntax (ILIKE, jsonb, pg_size_pretty).
 */
class ReportBuilderController extends Controller
{
    // =====================================================================
    //  Page Views
    // =====================================================================

    /**
     * Report Builder dashboard -- lists all custom reports grouped by category.
     */
    public function index(): View
    {
        $reports = collect();
        $statistics = ['total_reports' => 0, 'by_source' => []];
        $groupedReports = [];

        try {
            if (Schema::hasTable('custom_reports')) {
                $reports = DB::table('custom_reports')
                    ->orderBy('category')
                    ->orderByDesc('updated_at')
                    ->get();

                $statistics['total_reports'] = $reports->count();
                $statistics['by_source'] = $reports->groupBy('data_source')
                    ->map->count()
                    ->toArray();

                $groupedReports = $reports->groupBy('category')->toArray();
            }
        } catch (\Exception $e) {
            // table may not exist yet
        }

        return view('reports::report-builder.index', compact('reports', 'statistics', 'groupedReports'));
    }

    /**
     * Create report form.
     */
    public function create(): View
    {
        return view('reports::report-builder.create');
    }

    /**
     * Store a new report.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'data_source' => 'required|string',
        ]);

        try {
            if (Schema::hasTable('custom_reports')) {
                DB::table('custom_reports')->insert([
                    'name'        => $request->input('name'),
                    'description' => $request->input('description'),
                    'data_source' => $request->input('data_source'),
                    'category'    => $request->input('category', 'General'),
                    'status'      => 'draft',
                    'is_public'   => $request->input('visibility') === 'public' ? true : false,
                    'is_shared'   => $request->input('visibility') === 'shared' ? true : false,
                    'created_by'  => auth()->id(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        } catch (\Exception $e) {
            return redirect()->route('reports.builder.index')
                ->with('error', 'Failed to create report: ' . $e->getMessage());
        }

        return redirect()->route('reports.builder.index')
            ->with('success', 'Report created successfully.');
    }

    /**
     * Preview a report.
     */
    public function preview(int $id): View
    {
        $report = $this->getReport($id);
        return view('reports::report-builder.preview', compact('report'));
    }

    /**
     * Edit a report.
     */
    public function edit(int $id): View
    {
        $report = $this->getReport($id);
        return view('reports::report-builder.edit', compact('report'));
    }

    /**
     * Update a report.
     */
    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:255']);

        try {
            if (Schema::hasTable('custom_reports')) {
                DB::table('custom_reports')->where('id', $id)->update([
                    'name'        => $request->input('name'),
                    'description' => $request->input('description'),
                    'category'    => $request->input('category'),
                    'updated_at'  => now(),
                ]);
            }
        } catch (\Exception $e) {
            // ignore
        }

        return redirect()->route('reports.builder.edit', $id)
            ->with('success', 'Report updated successfully.');
    }

    /**
     * View a report.
     */
    public function view(int $id): View
    {
        $report = $this->getReport($id);
        return view('reports::report-builder.view', compact('report'));
    }

    /**
     * Query builder for a report.
     */
    public function query(int $id): View
    {
        $report = $this->getReport($id);
        return view('reports::report-builder.query', compact('report'));
    }

    /**
     * Schedule a report.
     */
    public function schedule(int $id): View
    {
        $report = $this->getReport($id);
        $existingSchedule = null;
        try {
            if (Schema::hasTable('custom_report_schedules')) {
                $existingSchedule = DB::table('custom_report_schedules')
                    ->where('report_id', $id)
                    ->first();
            }
        } catch (\Exception $e) {
            // ignore
        }
        return view('reports::report-builder.schedule', compact('report', 'existingSchedule'));
    }

    /**
     * Share a report.
     */
    public function share(int $id): View
    {
        $report = $this->getReport($id);
        $shares = collect();
        try {
            if (Schema::hasTable('custom_report_shares')) {
                $shares = DB::table('custom_report_shares')
                    ->where('report_id', $id)
                    ->orderByDesc('created_at')
                    ->get();
            }
        } catch (\Exception $e) {
            // ignore
        }
        return view('reports::report-builder.share', compact('report', 'shares'));
    }

    /**
     * Report execution history.
     */
    public function history(int $id): View
    {
        $report = $this->getReport($id);
        $versions = collect();
        try {
            if (Schema::hasTable('custom_report_versions')) {
                $versions = DB::table('custom_report_versions')
                    ->where('report_id', $id)
                    ->orderByDesc('created_at')
                    ->get();
            }
        } catch (\Exception $e) {
            // ignore
        }
        return view('reports::report-builder.history', compact('report', 'versions'));
    }

    /**
     * Report widget configuration.
     */
    public function widget(int $id): View
    {
        $report = $this->getReport($id);
        return view('reports::report-builder.widget', compact('report'));
    }

    /**
     * Report templates listing.
     */
    public function templates(): View
    {
        $templates = collect();
        try {
            if (Schema::hasTable('custom_report_templates')) {
                $templates = DB::table('custom_report_templates')
                    ->orderByDesc('updated_at')
                    ->get();
            }
        } catch (\Exception $e) {
            // ignore
        }
        return view('reports::report-builder.templates', compact('templates'));
    }

    /**
     * Archived reports listing.
     */
    public function archive(): View
    {
        $reports = collect();
        try {
            if (Schema::hasTable('custom_reports')) {
                $reports = DB::table('custom_reports')
                    ->where('status', 'archived')
                    ->orderByDesc('updated_at')
                    ->get();
            }
        } catch (\Exception $e) {
            // ignore
        }
        return view('reports::report-builder.archive', compact('reports'));
    }

    /**
     * Edit a report template.
     */
    public function editTemplate(int $id): View
    {
        $report = $this->getReport($id);
        return view('reports::report-builder.edit-template', compact('report'));
    }

    /**
     * Preview a report template.
     */
    public function previewTemplate(int $id): View
    {
        $report = $this->getReport($id);
        return view('reports::report-builder.preview-template', compact('report'));
    }

    /**
     * Delete a report template.
     */
    public function deleteTemplate(int $id): \Illuminate\Http\RedirectResponse
    {
        try {
            if (Schema::hasTable('custom_report_templates')) {
                DB::table('custom_report_templates')->where('id', $id)->delete();
            }
        } catch (\Exception $e) {
            // ignore
        }

        return redirect()->route('reports.builder.templates')
            ->with('success', 'Template deleted successfully.');
    }

    // =====================================================================
    //  Report Builder API Actions (AJAX)
    // =====================================================================

    /**
     * API: Delete a report.
     */
    public function apiDelete(Request $request, int $id): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_reports')) {
                DB::table('custom_reports')->where('id', $id)->delete();
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Save report (create or update).
     */
    public function apiSave(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:255']);

        try {
            if (!Schema::hasTable('custom_reports')) {
                return response()->json(['success' => false, 'error' => 'Report table not available']);
            }

            $data = [
                'name'             => $request->input('name'),
                'description'      => $request->input('description'),
                'data_source'      => $request->input('data_source'),
                'category'         => $request->input('category', 'General'),
                'query_definition' => $request->input('query_definition'),
                'layout_config'    => $request->input('layout_config'),
                'chart_config'     => $request->input('chart_config'),
                'filters'          => $request->input('filters'),
                'is_public'        => $request->boolean('is_public'),
                'is_shared'        => $request->boolean('is_shared'),
                'updated_at'       => now(),
            ];

            $id = $request->input('id');
            if ($id) {
                DB::table('custom_reports')->where('id', $id)->update($data);
            } else {
                $data['status'] = 'draft';
                $data['created_at'] = now();
                $data['created_by'] = auth()->id();
                $id = DB::table('custom_reports')->insertGetId($data);
            }

            return response()->json(['success' => true, 'id' => $id]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * API: Get report data for rendering.
     */
    public function apiData(Request $request, int $id): JsonResponse
    {
        $report = $this->getReport($id);
        if (!$report) {
            return response()->json(['success' => false, 'error' => 'Report not found']);
        }

        $data = [];
        try {
            $queryDef = json_decode($report->query_definition ?? '{}', true);
            if (!empty($queryDef['table'])) {
                $query = DB::table($queryDef['table']);
                if (!empty($queryDef['columns'])) {
                    $query->select($queryDef['columns']);
                }
                if (!empty($queryDef['where'])) {
                    foreach ($queryDef['where'] as $condition) {
                        $operator = $condition['operator'] ?? '=';
                        // Use ILIKE for case-insensitive text matching on PostgreSQL
                        if (strtoupper($operator) === 'LIKE') {
                            $operator = 'ILIKE';
                        }
                        $query->where($condition['column'], $operator, $condition['value']);
                    }
                }
                if (!empty($queryDef['joins'])) {
                    foreach ($queryDef['joins'] as $join) {
                        $query->leftJoin($join['table'], $join['first'], '=', $join['second']);
                    }
                }
                if (!empty($queryDef['orderBy'])) {
                    $query->orderBy($queryDef['orderBy'], $queryDef['orderDir'] ?? 'asc');
                }
                $limit = min((int) ($queryDef['limit'] ?? 1000), 10000);
                $data = $query->limit($limit)->get()->toArray();
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true, 'data' => $data, 'count' => count($data)]);
    }

    /**
     * API: Get available columns for a data source.
     */
    public function apiColumns(Request $request): JsonResponse
    {
        $table = $request->input('table');
        if (!$table) {
            return response()->json(['success' => false, 'error' => 'Table name required']);
        }

        try {
            if (!Schema::hasTable($table)) {
                return response()->json(['success' => false, 'error' => 'Table not found']);
            }
            $columns = Schema::getColumnListing($table);
            $details = [];
            foreach ($columns as $col) {
                $details[] = ['name' => $col, 'type' => Schema::getColumnType($table, $col)];
            }
            return response()->json(['success' => true, 'columns' => $details]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * API: Get available tables for query builder.
     */
    public function apiQueryTables(): JsonResponse
    {
        $allowedTables = [
            'record_descriptions', 'record_description_i18n',
            'agents', 'agent_i18n',
            'accessions', 'accession_i18n',
            'repositories', 'repository_i18n',
            'donors', 'donor_i18n',
            'terms', 'term_i18n', 'taxonomies',
            'physical_storage_locations', 'physical_storage_i18n',
            'digital_objects', 'events', 'relations',
            'rights_statements', 'embargoes',
            'contact_information', 'notes', 'note_i18n',
            'audit_log', 'users',
        ];

        return response()->json(['success' => true, 'tables' => $allowedTables]);
    }

    /**
     * API: Validate a query definition.
     */
    public function apiQueryValidate(Request $request): JsonResponse
    {
        $queryDef = $request->input('query');
        if (!$queryDef) {
            return response()->json(['valid' => false, 'error' => 'No query provided']);
        }

        $parsed = is_string($queryDef) ? json_decode($queryDef, true) : $queryDef;
        if (!$parsed || !isset($parsed['table'])) {
            return response()->json(['valid' => false, 'error' => 'Invalid query format - table required']);
        }

        if (!Schema::hasTable($parsed['table'])) {
            return response()->json(['valid' => false, 'error' => 'Table does not exist: ' . $parsed['table']]);
        }

        return response()->json(['valid' => true]);
    }

    /**
     * API: Execute a query and return results.
     */
    public function apiQueryExecute(Request $request): JsonResponse
    {
        $queryDef = $request->input('query');
        $parsed = is_string($queryDef) ? json_decode($queryDef, true) : $queryDef;

        if (!$parsed || !isset($parsed['table'])) {
            return response()->json(['success' => false, 'error' => 'Invalid query']);
        }

        try {
            $query = DB::table($parsed['table']);
            if (!empty($parsed['columns'])) {
                $query->select($parsed['columns']);
            }
            if (!empty($parsed['joins'])) {
                foreach ($parsed['joins'] as $join) {
                    $query->leftJoin($join['table'], $join['first'], '=', $join['second']);
                }
            }
            if (!empty($parsed['where'])) {
                foreach ($parsed['where'] as $condition) {
                    $operator = $condition['operator'] ?? '=';
                    if (strtoupper($operator) === 'LIKE') {
                        $operator = 'ILIKE';
                    }
                    $query->where($condition['column'], $operator, $condition['value']);
                }
            }
            if (!empty($parsed['orderBy'])) {
                $query->orderBy($parsed['orderBy'], $parsed['orderDir'] ?? 'asc');
            }

            $limit = min((int) ($parsed['limit'] ?? 100), 10000);
            $results = $query->limit($limit)->get();

            return response()->json(['success' => true, 'data' => $results, 'count' => $results->count()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * API: Get query relationships between tables.
     */
    public function apiQueryRelationships(): JsonResponse
    {
        $relationships = [
            ['from' => 'record_descriptions', 'to' => 'record_description_i18n', 'key' => 'record_description_id'],
            ['from' => 'agents', 'to' => 'agent_i18n', 'key' => 'agent_id'],
            ['from' => 'accessions', 'to' => 'accession_i18n', 'key' => 'accession_id'],
            ['from' => 'repositories', 'to' => 'repository_i18n', 'key' => 'repository_id'],
            ['from' => 'terms', 'to' => 'term_i18n', 'key' => 'term_id'],
            ['from' => 'donors', 'to' => 'donor_i18n', 'key' => 'donor_id'],
            ['from' => 'record_descriptions', 'to' => 'digital_objects', 'key' => 'record_description_id'],
            ['from' => 'record_descriptions', 'to' => 'events', 'key' => 'record_description_id'],
            ['from' => 'record_descriptions', 'to' => 'rights_statements', 'key' => 'record_description_id'],
            ['from' => 'record_descriptions', 'to' => 'embargoes', 'key' => 'record_description_id'],
        ];

        return response()->json(['success' => true, 'relationships' => $relationships]);
    }

    /**
     * API: Save a query for a report.
     */
    public function apiQuerySave(Request $request, int $id): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_reports')) {
                DB::table('custom_reports')->where('id', $id)->update([
                    'query_definition' => json_encode($request->input('query')),
                    'updated_at'       => now(),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Change report status.
     */
    public function apiStatusChange(Request $request, int $id): JsonResponse
    {
        $status = $request->input('status');
        $validStatuses = ['draft', 'active', 'archived', 'published'];

        if (!in_array($status, $validStatuses, true)) {
            return response()->json(['success' => false, 'error' => 'Invalid status']);
        }

        try {
            if (Schema::hasTable('custom_reports')) {
                DB::table('custom_reports')->where('id', $id)->update([
                    'status'     => $status,
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Get chart data for a report.
     */
    public function apiChartData(Request $request, int $id): JsonResponse
    {
        $report = $this->getReport($id);
        if (!$report) {
            return response()->json(['success' => false, 'error' => 'Report not found']);
        }

        $chartConfig = json_decode($report->chart_config ?? '{}', true);
        $queryDef = json_decode($report->query_definition ?? '{}', true);

        $data = [];
        try {
            if (!empty($queryDef['table'])) {
                $query = DB::table($queryDef['table']);
                if (!empty($chartConfig['groupBy'])) {
                    $query->select(DB::raw($chartConfig['groupBy'] . ' as label, COUNT(*) as value'))
                          ->groupBy($chartConfig['groupBy']);
                }
                $data = $query->limit(100)->get()->toArray();
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true, 'chartData' => $data]);
    }

    /**
     * API: Search entities for linking.
     */
    public function apiEntitySearch(Request $request): JsonResponse
    {
        $searchQuery = $request->input('query', '');
        $type = $request->input('type', 'record_descriptions');

        $results = [];
        if (strlen($searchQuery) < 2) {
            return response()->json(['success' => true, 'results' => $results]);
        }

        try {
            $culture = app()->getLocale();
            if ($type === 'record_descriptions') {
                $results = DB::table('record_descriptions as rd')
                    ->leftJoin('record_description_i18n as rdi', function ($join) use ($culture) {
                        $join->on('rd.id', '=', 'rdi.record_description_id')
                            ->where('rdi.culture', '=', $culture);
                    })
                    ->where(function ($q) use ($searchQuery) {
                        $q->where('rd.identifier', 'ILIKE', "%{$searchQuery}%")
                          ->orWhere('rdi.title', 'ILIKE', "%{$searchQuery}%");
                    })
                    ->select(['rd.id', 'rd.identifier', 'rdi.title'])
                    ->limit(20)
                    ->get()
                    ->toArray();
            } elseif ($type === 'agents') {
                $results = DB::table('agents as a')
                    ->leftJoin('agent_i18n as ai', function ($join) use ($culture) {
                        $join->on('a.id', '=', 'ai.agent_id')
                            ->where('ai.culture', '=', $culture);
                    })
                    ->where('ai.authorized_form_of_name', 'ILIKE', "%{$searchQuery}%")
                    ->select(['a.id', 'ai.authorized_form_of_name as name'])
                    ->limit(20)
                    ->get()
                    ->toArray();
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true, 'results' => $results]);
    }

    /**
     * API: Save report section.
     */
    public function apiSectionSave(Request $request, int $id): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_sections')) {
                $sectionId = $request->input('section_id');
                $data = [
                    'report_id'    => $id,
                    'title'        => $request->input('title'),
                    'section_type' => $request->input('section_type', 'text'),
                    'content'      => $request->input('content'),
                    'sort_order'   => (int) $request->input('sort_order', 0),
                    'config'       => json_encode($request->input('config', [])),
                    'updated_at'   => now(),
                ];

                if ($sectionId) {
                    DB::table('custom_report_sections')->where('id', $sectionId)->update($data);
                } else {
                    $data['created_at'] = now();
                    $sectionId = DB::table('custom_report_sections')->insertGetId($data);
                }

                return response()->json(['success' => true, 'section_id' => $sectionId]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'error' => 'Section table not available']);
    }

    /**
     * API: Delete report section.
     */
    public function apiSectionDelete(Request $request, int $id, int $sectionId): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_sections')) {
                DB::table('custom_report_sections')
                    ->where('id', $sectionId)
                    ->where('report_id', $id)
                    ->delete();
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Reorder report sections.
     */
    public function apiSectionReorder(Request $request, int $id): JsonResponse
    {
        $order = $request->input('order', []);

        try {
            if (Schema::hasTable('custom_report_sections')) {
                foreach ($order as $i => $sectionId) {
                    DB::table('custom_report_sections')
                        ->where('id', $sectionId)
                        ->where('report_id', $id)
                        ->update(['sort_order' => $i]);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Create a snapshot of a report.
     */
    public function apiSnapshot(Request $request, int $id): JsonResponse
    {
        try {
            $report = $this->getReport($id);
            if (!$report) {
                return response()->json(['success' => false, 'error' => 'Report not found']);
            }

            if (Schema::hasTable('custom_report_snapshots')) {
                $snapshotId = DB::table('custom_report_snapshots')->insertGetId([
                    'report_id'     => $id,
                    'snapshot_data' => json_encode($report),
                    'created_by'    => auth()->id(),
                    'created_at'    => now(),
                ]);

                return response()->json(['success' => true, 'snapshot_id' => $snapshotId]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'error' => 'Snapshot table not available']);
    }

    /**
     * API: Get report versions.
     */
    public function apiVersions(Request $request, int $id): JsonResponse
    {
        $versions = [];
        try {
            if (Schema::hasTable('custom_report_versions')) {
                $versions = DB::table('custom_report_versions')
                    ->where('report_id', $id)
                    ->orderByDesc('created_at')
                    ->get()
                    ->toArray();
            }
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json(['success' => true, 'versions' => $versions]);
    }

    /**
     * API: Create a new version of a report.
     */
    public function apiVersionCreate(Request $request, int $id): JsonResponse
    {
        try {
            $report = $this->getReport($id);
            if (!$report) {
                return response()->json(['success' => false, 'error' => 'Report not found']);
            }

            if (Schema::hasTable('custom_report_versions')) {
                $versionId = DB::table('custom_report_versions')->insertGetId([
                    'report_id'     => $id,
                    'version_data'  => json_encode($report),
                    'version_label' => $request->input('label', 'v' . now()->format('Ymd-His')),
                    'notes'         => $request->input('notes'),
                    'created_by'    => auth()->id(),
                    'created_at'    => now(),
                ]);

                return response()->json(['success' => true, 'version_id' => $versionId]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'error' => 'Version table not available']);
    }

    /**
     * API: Restore a report version.
     */
    public function apiVersionRestore(Request $request, int $id, int $versionId): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_versions')) {
                $version = DB::table('custom_report_versions')
                    ->where('id', $versionId)
                    ->where('report_id', $id)
                    ->first();

                if (!$version) {
                    return response()->json(['success' => false, 'error' => 'Version not found']);
                }

                $versionData = json_decode($version->version_data, true);
                if ($versionData) {
                    $updateData = array_intersect_key($versionData, array_flip([
                        'name', 'description', 'data_source', 'category',
                        'query_definition', 'layout_config', 'chart_config', 'filters',
                    ]));
                    $updateData['updated_at'] = now();

                    DB::table('custom_reports')->where('id', $id)->update($updateData);
                }

                return response()->json(['success' => true]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'error' => 'Version table not available']);
    }

    /**
     * API: Create share link for a report.
     */
    public function apiShareCreate(Request $request, int $id): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_shares')) {
                $token = Str::random(32);
                $shareId = DB::table('custom_report_shares')->insertGetId([
                    'report_id'  => $id,
                    'token'      => $token,
                    'expires_at' => $request->input('expires_at'),
                    'is_active'  => true,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                ]);

                return response()->json([
                    'success'  => true,
                    'share_id' => $shareId,
                    'token'    => $token,
                    'url'      => url('/admin/reports/builder/shared/' . $token),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'error' => 'Share table not available']);
    }

    /**
     * API: Deactivate a share link.
     */
    public function apiShareDeactivate(Request $request, int $id, int $shareId): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_shares')) {
                DB::table('custom_report_shares')
                    ->where('id', $shareId)
                    ->where('report_id', $id)
                    ->update(['is_active' => false]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Save a report template.
     */
    public function apiTemplateSave(Request $request): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_templates')) {
                $data = [
                    'name'          => $request->input('name'),
                    'description'   => $request->input('description'),
                    'template_data' => json_encode($request->input('template_data', [])),
                    'category'      => $request->input('category', 'General'),
                    'updated_at'    => now(),
                ];

                $templateId = $request->input('template_id');
                if ($templateId) {
                    DB::table('custom_report_templates')->where('id', $templateId)->update($data);
                } else {
                    $data['created_at'] = now();
                    $data['created_by'] = auth()->id();
                    $templateId = DB::table('custom_report_templates')->insertGetId($data);
                }

                return response()->json(['success' => true, 'template_id' => $templateId]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'error' => 'Template table not available']);
    }

    /**
     * API: Delete a report template.
     */
    public function apiTemplateDelete(Request $request, int $templateId): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_templates')) {
                DB::table('custom_report_templates')->where('id', $templateId)->delete();
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Apply a template to a report.
     */
    public function apiTemplateApply(Request $request, int $id): JsonResponse
    {
        $templateId = $request->input('template_id');

        try {
            if (Schema::hasTable('custom_report_templates') && Schema::hasTable('custom_reports')) {
                $template = DB::table('custom_report_templates')->where('id', $templateId)->first();
                if (!$template) {
                    return response()->json(['success' => false, 'error' => 'Template not found']);
                }

                $templateData = json_decode($template->template_data, true) ?? [];
                $updateData = array_intersect_key($templateData, array_flip([
                    'layout_config', 'chart_config', 'filters',
                ]));
                $updateData['updated_at'] = now();

                DB::table('custom_reports')->where('id', $id)->update($updateData);

                return response()->json(['success' => true]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'error' => 'Tables not available']);
    }

    /**
     * API: Get widgets for a report.
     */
    public function apiWidgets(Request $request, int $id): JsonResponse
    {
        $widgets = [];
        try {
            if (Schema::hasTable('custom_report_widgets')) {
                $widgets = DB::table('custom_report_widgets')
                    ->where('report_id', $id)
                    ->orderBy('sort_order')
                    ->get()
                    ->toArray();
            }
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json(['success' => true, 'widgets' => $widgets]);
    }

    /**
     * API: Save a widget.
     */
    public function apiWidgetSave(Request $request, int $id): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_widgets')) {
                $data = [
                    'report_id'   => $id,
                    'widget_type' => $request->input('widget_type', 'chart'),
                    'title'       => $request->input('title'),
                    'config'      => json_encode($request->input('config', [])),
                    'sort_order'  => (int) $request->input('sort_order', 0),
                    'updated_at'  => now(),
                ];

                $widgetId = $request->input('widget_id');
                if ($widgetId) {
                    DB::table('custom_report_widgets')->where('id', $widgetId)->update($data);
                } else {
                    $data['created_at'] = now();
                    $widgetId = DB::table('custom_report_widgets')->insertGetId($data);
                }

                return response()->json(['success' => true, 'widget_id' => $widgetId]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'error' => 'Widget table not available']);
    }

    /**
     * API: Delete a widget.
     */
    public function apiWidgetDelete(Request $request, int $id, int $widgetId): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_widgets')) {
                DB::table('custom_report_widgets')
                    ->where('id', $widgetId)
                    ->where('report_id', $id)
                    ->delete();
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Add a comment to a report.
     */
    public function apiComment(Request $request, int $id): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_comments')) {
                $commentId = DB::table('custom_report_comments')->insertGetId([
                    'report_id'  => $id,
                    'user_id'    => auth()->id(),
                    'comment'    => $request->input('comment'),
                    'created_at' => now(),
                ]);

                return response()->json(['success' => true, 'comment_id' => $commentId]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'error' => 'Comment table not available']);
    }

    /**
     * API: Upload attachment for a report.
     */
    public function apiAttachmentUpload(Request $request, int $id): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:10240']);

        try {
            $file = $request->file('file');
            if (!$file) {
                return response()->json(['success' => false, 'error' => 'No file uploaded']);
            }
            $path = $file->store('report-attachments/' . $id, 'public');

            if (Schema::hasTable('custom_report_attachments')) {
                $attachmentId = DB::table('custom_report_attachments')->insertGetId([
                    'report_id'   => $id,
                    'filename'    => $file->getClientOriginalName(),
                    'file_path'   => $path,
                    'file_size'   => $file->getSize(),
                    'mime_type'   => $file->getMimeType(),
                    'uploaded_by' => auth()->id(),
                    'created_at'  => now(),
                ]);

                return response()->json(['success' => true, 'attachment_id' => $attachmentId, 'path' => $path]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'error' => 'Attachment table not available']);
    }

    /**
     * API: Get attachments for a report.
     */
    public function apiAttachments(Request $request, int $id): JsonResponse
    {
        $attachments = [];
        try {
            if (Schema::hasTable('custom_report_attachments')) {
                $attachments = DB::table('custom_report_attachments')
                    ->where('report_id', $id)
                    ->orderByDesc('created_at')
                    ->get()
                    ->toArray();
            }
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json(['success' => true, 'attachments' => $attachments]);
    }

    /**
     * API: Delete an attachment.
     */
    public function apiAttachmentDelete(Request $request, int $id, int $attachmentId): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_attachments')) {
                $attachment = DB::table('custom_report_attachments')
                    ->where('id', $attachmentId)
                    ->where('report_id', $id)
                    ->first();

                if ($attachment && \Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
                }

                DB::table('custom_report_attachments')
                    ->where('id', $attachmentId)
                    ->where('report_id', $id)
                    ->delete();
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Save a link (bookmark / reference) for a report.
     */
    public function apiLinkSave(Request $request, int $id): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_links')) {
                $linkId = DB::table('custom_report_links')->insertGetId([
                    'report_id'   => $id,
                    'url'         => $request->input('url'),
                    'title'       => $request->input('title'),
                    'description' => $request->input('description'),
                    'created_at'  => now(),
                ]);

                return response()->json(['success' => true, 'link_id' => $linkId]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'error' => 'Link table not available']);
    }

    /**
     * API: Delete a link.
     */
    public function apiLinkDelete(Request $request, int $id, int $linkId): JsonResponse
    {
        try {
            if (Schema::hasTable('custom_report_links')) {
                DB::table('custom_report_links')
                    ->where('id', $linkId)
                    ->where('report_id', $id)
                    ->delete();
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * View a shared report (public access via token).
     */
    public function sharedView(string $token): View
    {
        $share = null;
        $report = null;

        try {
            if (Schema::hasTable('custom_report_shares')) {
                $share = DB::table('custom_report_shares')
                    ->where('token', $token)
                    ->where('is_active', true)
                    ->first();

                if ($share) {
                    if ($share->expires_at && now()->gt($share->expires_at)) {
                        abort(410, 'This shared link has expired.');
                    }
                    $report = $this->getReport((int) $share->report_id);
                }
            }
        } catch (\Exception $e) {
            // ignore
        }

        if (!$report) {
            abort(404, 'Report not found');
        }

        return view('reports::report-builder.view', compact('report', 'share'));
    }

    /**
     * Clone an existing report.
     */
    public function cloneReport(int $id): \Illuminate\Http\RedirectResponse
    {
        $report = $this->getReport($id);
        if (!$report) {
            abort(404, 'Report not found');
        }

        try {
            if (Schema::hasTable('custom_reports')) {
                $newId = DB::table('custom_reports')->insertGetId([
                    'name'             => $report->name . ' (Copy)',
                    'description'      => $report->description,
                    'data_source'      => $report->data_source,
                    'category'         => $report->category,
                    'query_definition' => $report->query_definition,
                    'layout_config'    => $report->layout_config,
                    'chart_config'     => $report->chart_config,
                    'filters'          => $report->filters,
                    'status'           => 'draft',
                    'is_public'        => false,
                    'is_shared'        => false,
                    'created_by'       => auth()->id(),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                return redirect()->route('reports.builder.edit', $newId)
                    ->with('success', 'Report cloned successfully.');
            }
        } catch (\Exception $e) {
            return redirect()->route('reports.builder.index')
                ->with('error', 'Failed to clone report: ' . $e->getMessage());
        }

        return redirect()->route('reports.builder.index');
    }

    /**
     * Export a report (download).
     */
    public function export(Request $request, int $id, string $format = 'json'): \Symfony\Component\HttpFoundation\Response
    {
        $report = $this->getReport($id);
        if (!$report) {
            abort(404, 'Report not found');
        }

        if ($format === 'json') {
            return response()->json($report)
                ->header('Content-Disposition', 'attachment; filename="report-' . $id . '.json"');
        }

        // CSV export
        $queryDef = json_decode($report->query_definition ?? '{}', true);
        $data = [];
        try {
            if (!empty($queryDef['table'])) {
                $query = DB::table($queryDef['table']);
                if (!empty($queryDef['columns'])) {
                    $query->select($queryDef['columns']);
                }
                $data = $query->limit(10000)->get()->toArray();
            }
        } catch (\Exception $e) {
            abort(500, 'Error generating export: ' . $e->getMessage());
        }

        if (empty($data)) {
            abort(404, 'No data to export');
        }

        $headers = array_keys((array) $data[0]);
        $csv = implode(',', $headers) . "\n";
        foreach ($data as $row) {
            $values = array_map(function ($v) {
                return '"' . str_replace('"', '""', (string) ($v ?? '')) . '"';
            }, array_values((array) $row));
            $csv .= implode(',', $values) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="report-' . $id . '.csv"',
        ]);
    }

    /**
     * Store/update a schedule for a report (POST).
     */
    public function scheduleStore(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'frequency'        => 'required|string|in:daily,weekly,monthly',
            'day_of_week'      => 'nullable|integer|min:0|max:6',
            'day_of_month'     => 'nullable|integer|min:1|max:31',
            'time'             => 'required|string',
            'email_recipients' => 'nullable|string',
            'format'           => 'nullable|string|in:csv,pdf,xlsx',
        ]);

        try {
            if (Schema::hasTable('custom_report_schedules')) {
                $data = [
                    'report_id'        => $id,
                    'frequency'        => $request->input('frequency'),
                    'day_of_week'      => $request->input('day_of_week'),
                    'day_of_month'     => $request->input('day_of_month'),
                    'time'             => $request->input('time'),
                    'email_recipients' => $request->input('email_recipients'),
                    'format'           => $request->input('format', 'csv'),
                    'is_active'        => true,
                    'updated_at'       => now(),
                ];

                $existing = DB::table('custom_report_schedules')->where('report_id', $id)->first();
                if ($existing) {
                    DB::table('custom_report_schedules')->where('report_id', $id)->update($data);
                } else {
                    $data['created_at'] = now();
                    DB::table('custom_report_schedules')->insert($data);
                }
            }
        } catch (\Exception $e) {
            return redirect()->route('reports.builder.schedule', $id)
                ->with('error', 'Failed to save schedule: ' . $e->getMessage());
        }

        return redirect()->route('reports.builder.schedule', $id)
            ->with('success', 'Schedule saved.');
    }

    /**
     * Schedule management -- delete a schedule entry.
     */
    public function scheduleDelete(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        try {
            if (Schema::hasTable('custom_report_schedules')) {
                DB::table('custom_report_schedules')->where('report_id', $id)->delete();
            }
        } catch (\Exception $e) {
            return redirect()->route('reports.builder.schedule', $id)
                ->with('error', 'Failed to delete schedule: ' . $e->getMessage());
        }

        return redirect()->route('reports.builder.schedule', $id)
            ->with('success', 'Schedule removed.');
    }

    // =====================================================================
    //  Internal Helpers
    // =====================================================================

    /**
     * Get a single report by ID.
     */
    private function getReport(int $id): ?object
    {
        try {
            if (Schema::hasTable('custom_reports')) {
                return DB::table('custom_reports')->where('id', $id)->first();
            }
        } catch (\Exception $e) {
            // ignore
        }
        return null;
    }
}
