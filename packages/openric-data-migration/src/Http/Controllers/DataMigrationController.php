<?php

declare(strict_types=1);

namespace OpenRiC\DataMigration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use OpenRiC\DataMigration\Contracts\DataMigrationServiceInterface;

/**
 * Data migration controller — adapted from Heratio ahg-data-migration DataMigrationController (350 lines).
 *
 * Provides dashboard, file upload, CSV analysis, column mapping, preview, import execution,
 * import history, export, Preservica import/export, mapping management, job management,
 * validation, and rollback functionality.
 */
class DataMigrationController extends Controller
{
    public function __construct(
        private readonly DataMigrationServiceInterface $service,
    ) {
    }

    // =========================================================================
    // Dashboard — adapted from Heratio DataMigrationController::index()
    // =========================================================================

    public function index(): View
    {
        $presets = $this->service->getFieldMappingPresets();
        $history = $this->service->getImportHistory(10);
        $stats = $this->service->getStats();

        return view('openric-data-migration::index', [
            'mappings' => $presets,
            'recentJobs' => $history['jobs'],
            'stats' => $stats,
        ]);
    }

    // =========================================================================
    // Upload — adapted from Heratio DataMigrationController::upload()
    // =========================================================================

    public function upload(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('get')) {
            $presets = $this->service->getFieldMappingPresets();
            return view('openric-data-migration::upload', ['savedMappings' => $presets]);
        }

        $request->validate([
            'file' => 'required|file|max:102400',
            'target_type' => 'required|string|in:record,agent,accession,repository',
        ]);

        $path = $request->file('file')->store('data-migration/uploads');
        $fileName = $request->file('file')->getClientOriginalName();

        session([
            'dm_file' => $path,
            'dm_filename' => $fileName,
            'dm_target' => $request->input('target_type'),
            'dm_import_type' => $request->input('import_type', 'create'),
        ]);

        return redirect()->route('data-migration.map');
    }

    // =========================================================================
    // Map — adapted from Heratio DataMigrationController::map()
    // =========================================================================

    public function map(Request $request): View|RedirectResponse
    {
        $filePath = session('dm_file');
        $fileName = session('dm_filename', 'unknown');
        $targetType = session('dm_target', 'record');

        if (!$filePath) {
            return redirect()->route('data-migration.upload')
                ->with('error', 'No file uploaded. Please upload a file first.');
        }

        $analysis = $this->service->analyzeCsv($filePath);
        $targetFields = $this->service->getTargetFields($targetType);
        $savedMappings = $this->service->getFieldMappingPresets();

        return view('openric-data-migration::map', [
            'fileName' => $fileName,
            'targetType' => $targetType,
            'sourceColumns' => $analysis['headers'],
            'totalRows' => $analysis['totalRows'],
            'targetFields' => $targetFields,
            'savedMappings' => $savedMappings,
            'previewRows' => $analysis['rows'],
        ]);
    }

    // =========================================================================
    // Save Mapping — adapted from Heratio DataMigrationController::saveMapping()
    // =========================================================================

    public function saveMapping(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $mappingData = $request->input('field_mappings', $request->input('mappings', '{}'));
        if (is_string($mappingData)) {
            $mappingData = json_decode($mappingData, true) ?: [];
        }

        $id = $this->service->saveFieldMappingPreset([
            'name' => $request->input('name'),
            'entity_type' => $request->input('target_type', session('dm_target', 'record')),
            'category' => $request->input('category', 'Custom'),
            'column_mapping' => $mappingData,
        ]);

        return response()->json(['success' => true, 'id' => $id]);
    }

    // =========================================================================
    // Delete Mapping — adapted from Heratio DataMigrationController::deleteMapping()
    // =========================================================================

    public function deleteMapping(int $id): RedirectResponse
    {
        $this->service->deletePreset($id);
        return redirect()->route('data-migration.index')
            ->with('success', 'Mapping deleted.');
    }

    // =========================================================================
    // Preview — adapted from Heratio DataMigrationController::preview()
    // =========================================================================

    public function preview(Request $request): View|JsonResponse
    {
        $filePath = session('dm_file');
        $targetType = session('dm_target', 'record');

        if (!$filePath) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No file in session'], 400);
            }
            return redirect()->route('data-migration.upload')
                ->with('error', 'No file uploaded.');
        }

        $mappingJson = $request->input('mapping', $request->input('column_mapping', '{}'));
        $columnMapping = is_string($mappingJson) ? (json_decode($mappingJson, true) ?: []) : $mappingJson;

        $analysis = $this->service->analyzeCsv($filePath, 20);
        $mapped = $this->service->mapColumns($columnMapping, $analysis['rows']);

        $targetHeaders = array_unique(array_values($columnMapping));
        $transformedRows = $mapped;

        if ($request->expectsJson()) {
            return response()->json([
                'preview' => $transformedRows,
                'totalRows' => $analysis['totalRows'],
            ]);
        }

        return view('openric-data-migration::preview', [
            'transformedRows' => $transformedRows,
            'targetHeaders' => $targetHeaders,
            'totalRows' => $analysis['totalRows'],
            'targetType' => $targetType,
            'mapping' => $columnMapping,
        ]);
    }

    // =========================================================================
    // Execute — adapted from Heratio DataMigrationController::execute()
    // =========================================================================

    public function execute(Request $request): RedirectResponse|JsonResponse
    {
        $filePath = session('dm_file');
        $targetType = session('dm_target', 'record');
        $importType = session('dm_import_type', 'create');

        if (!$filePath) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No file in session'], 400);
            }
            return redirect()->route('data-migration.upload')
                ->with('error', 'No file uploaded.');
        }

        $mappingJson = $request->input('mapping', $request->input('column_mapping', '{}'));
        $columnMapping = is_string($mappingJson) ? (json_decode($mappingJson, true) ?: []) : $mappingJson;
        $transformRules = json_decode((string) $request->input('transform_rules', '{}'), true) ?: [];

        // Parse full CSV
        $analysis = $this->service->analyzeCsv($filePath, PHP_INT_MAX);

        // Create job
        $jobId = $this->service->createJob(
            $filePath,
            'csv',
            $targetType,
            $columnMapping,
            $transformRules,
            $analysis['totalRows']
        );

        // Map and transform all rows
        $mapped = $this->service->mapColumns($columnMapping, $analysis['rows']);
        $transformedRows = [];
        foreach ($mapped as $row) {
            $transformedRows[] = $this->service->transformRow($row, $transformRules);
        }

        // Execute import
        $result = $this->service->importBatch($transformedRows, $targetType, $jobId, [
            'import_type' => $importType,
            'skip_errors' => true,
        ]);

        // Clear session
        session()->forget(['dm_file', 'dm_filename', 'dm_target', 'dm_import_type']);

        if ($request->expectsJson()) {
            return response()->json(array_merge(['job_id' => $jobId], $result));
        }

        return redirect()->route('data-migration.job', $jobId)
            ->with('success', sprintf(
                'Import complete: %d imported, %d updated, %d skipped, %d errors.',
                $result['imported'],
                $result['updated'],
                $result['skipped'],
                count($result['errors'])
            ));
    }

    // =========================================================================
    // Jobs list — adapted from Heratio DataMigrationController::jobs()
    // =========================================================================

    public function jobs(Request $request): View
    {
        $result = $this->service->getImportHistory(50);

        return view('openric-data-migration::jobs', [
            'jobs' => $result['jobs'],
        ]);
    }

    // =========================================================================
    // Job Status — adapted from Heratio DataMigrationController::jobStatus()
    // =========================================================================

    public function jobStatus(int $id): View
    {
        $job = $this->service->getJob($id);
        if (!$job) {
            abort(404, 'Job not found.');
        }

        $progressPercent = $job['total_rows'] > 0
            ? min(100, (int) round(($job['processed_rows'] / $job['total_rows']) * 100))
            : 0;

        return view('openric-data-migration::job-status', [
            'job' => $job,
            'progressPercent' => $progressPercent,
        ]);
    }

    // =========================================================================
    // Batch Export — adapted from Heratio DataMigrationController::batchExport()
    // =========================================================================

    public function batchExport(Request $request)
    {
        $counts = $this->service->getRecordCounts();

        if ($request->has('export') && $request->has('entity_type')) {
            $entityType = $request->input('entity_type');
            $filters = $request->only(['date_from', 'date_to']);
            return $this->service->batchExportCsv($entityType, $filters);
        }

        return view('openric-data-migration::batch-export', [
            'counts' => $counts,
        ]);
    }

    // =========================================================================
    // Export Records — adapted from Heratio DataMigrationController::export()
    // =========================================================================

    public function export(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $entityType = $request->input('target_type', 'record');
            $filters = $request->only(['date_from', 'date_to', 'repository_id']);
            $jobId = $this->service->createJob(
                'export',
                $request->input('export_type', 'csv'),
                $entityType,
                [],
                [],
                0
            );

            return redirect()->route('data-migration.job', $jobId)
                ->with('success', 'Export job queued. Job ID: ' . $jobId);
        }

        return view('openric-data-migration::export');
    }

    // =========================================================================
    // Import Results — adapted from Heratio DataMigrationController::importResults()
    // =========================================================================

    public function importResults(Request $request): View
    {
        $jobId = $request->input('job_id');
        $job = null;
        $result = null;
        $results = [];

        if ($jobId) {
            $job = $this->service->getJob((int) $jobId);
            $results = $this->service->getJobResults((int) $jobId);
            if ($job) {
                $result = [
                    'success' => $job['status'] === 'completed',
                    'imported' => $job['processed_rows'] - ($job['error_rows'] ?? 0),
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => $job['error_rows'] ?? 0,
                    'message' => $job['progress_message'] ?? '',
                ];
            }
        }

        return view('openric-data-migration::import-results', [
            'job' => $job,
            'result' => $result,
            'results' => $results,
        ]);
    }

    // =========================================================================
    // Preservica Import — adapted from Heratio DataMigrationController::preservicaImport()
    // =========================================================================

    public function preservicaImport(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $jobId = $this->service->createJob(
                'preservica-import',
                'preservica',
                'record',
                $request->only(['preservica_url', 'preservica_user', 'collection_ref', 'target_repository']),
                [],
                0
            );

            return redirect()->route('data-migration.job', $jobId)
                ->with('success', 'Preservica import job queued. Job ID: ' . $jobId);
        }

        return view('openric-data-migration::preservica-import');
    }

    // =========================================================================
    // Preservica Export — adapted from Heratio DataMigrationController::preservicaExport()
    // =========================================================================

    public function preservicaExport(Request $request, ?int $id = null): View|RedirectResponse
    {
        $job = null;
        if ($id) {
            $job = $this->service->getJob($id);
        }

        if ($request->isMethod('post')) {
            $jobId = $this->service->createJob(
                'preservica-export',
                'preservica',
                'record',
                $request->only(['preservica_url', 'preservica_user', 'source_repository']),
                [],
                0
            );

            return redirect()->route('data-migration.job', $jobId)
                ->with('success', 'Preservica export job queued. Job ID: ' . $jobId);
        }

        return view('openric-data-migration::preservica-export', [
            'job' => $job,
        ]);
    }

    // =========================================================================
    // Download — adapted from Heratio DataMigrationController::download()
    // =========================================================================

    public function download(Request $request)
    {
        $request->validate(['file' => 'required|string']);
        $path = 'data-migration/exports/' . basename($request->input('file'));
        if (!Storage::exists($path)) {
            abort(404, 'Export file not found.');
        }
        return Storage::download($path);
    }

    // =========================================================================
    // Get Mapping (AJAX) — adapted from Heratio DataMigrationController::getMapping()
    // =========================================================================

    public function getMapping(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);
        $mapping = $this->service->getPreset((int) $request->input('id'));
        if (!$mapping) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json($mapping);
    }

    // =========================================================================
    // Job Progress (AJAX) — adapted from Heratio DataMigrationController::jobProgress()
    // =========================================================================

    public function jobProgress(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);
        $job = $this->service->getJob((int) $request->input('id'));
        if (!$job) {
            return response()->json(['error' => 'Not found'], 404);
        }
        $pct = $job['total_rows'] > 0
            ? min(100, (int) round(($job['processed_rows'] / $job['total_rows']) * 100))
            : 0;

        return response()->json([
            'id' => $job['id'],
            'status' => $job['status'],
            'progress' => $pct,
            'message' => $job['progress_message'] ?? '',
            'errors' => $job['error_rows'] ?? 0,
        ]);
    }

    // =========================================================================
    // Queue Job (AJAX) — adapted from Heratio DataMigrationController::queueJob()
    // =========================================================================

    public function queueJob(Request $request): JsonResponse
    {
        $request->validate(['type' => 'required|string']);
        $jobId = $this->service->createJob(
            $request->input('source', 'manual'),
            $request->input('format', 'csv'),
            $request->input('type'),
            [],
            [],
            0
        );
        return response()->json(['success' => true, 'job_id' => $jobId]);
    }

    // =========================================================================
    // Cancel Job — adapted from Heratio DataMigrationController::cancelJob()
    // =========================================================================

    public function cancelJob(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['id' => 'required|integer']);
        $this->service->cancelJob((int) $request->input('id'));

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('data-migration.jobs')
            ->with('success', 'Job cancelled.');
    }

    // =========================================================================
    // Export CSV — adapted from Heratio DataMigrationController::exportCsv()
    // =========================================================================

    public function exportCsv(Request $request)
    {
        $request->validate(['job_id' => 'required|integer']);
        $data = $this->service->getJobResults((int) $request->input('job_id'));
        $fileName = 'migration-results-' . $request->input('job_id') . '-' . date('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            if (!empty($data)) {
                fputcsv($handle, array_keys($data[0]));
            }
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // Load Mapping (AJAX) — adapted from Heratio DataMigrationController::loadMapping()
    // =========================================================================

    public function loadMapping(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);
        $mapping = $this->service->getPreset((int) $request->input('id'));
        if (!$mapping) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json([
            'id' => $mapping['id'],
            'name' => $mapping['name'],
            'column_mapping' => $mapping['column_mapping'] ?? [],
        ]);
    }

    // =========================================================================
    // Preview Validation (AJAX) — adapted from Heratio DataMigrationController::previewValidation()
    // =========================================================================

    public function previewValidation(Request $request): JsonResponse
    {
        $filePath = session('dm_file');
        $targetType = session('dm_target', 'record');
        $mappings = json_decode($request->input('mappings', '{}'), true) ?: [];
        $validation = $this->service->validateImportFile($filePath ?? '', $targetType, $mappings);
        return response()->json($validation);
    }

    // =========================================================================
    // Export Mapping — adapted from Heratio DataMigrationController::exportMapping()
    // =========================================================================

    public function exportMapping(int $id): JsonResponse
    {
        $mapping = $this->service->getPreset($id);
        if (!$mapping) {
            abort(404);
        }
        $fileName = 'mapping-' . ($mapping['name'] ?? 'export') . '-' . date('Ymd') . '.json';
        return response()->json($mapping)
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    // =========================================================================
    // Import Mapping — adapted from Heratio DataMigrationController::importMapping()
    // =========================================================================

    public function importMapping(Request $request): RedirectResponse
    {
        $request->validate(['mapping_file' => 'required|file|max:1024']);
        $content = file_get_contents($request->file('mapping_file')->getRealPath());
        $data = json_decode($content, true);

        if (!$data || !isset($data['name'])) {
            return redirect()->route('data-migration.index')
                ->with('error', 'Invalid mapping file format.');
        }

        $this->service->saveFieldMappingPreset([
            'name' => $data['name'] . ' (imported)',
            'entity_type' => $data['entity_type'] ?? 'record',
            'category' => $data['category'] ?? 'Imported',
            'column_mapping' => $data['column_mapping'] ?? [],
        ]);

        return redirect()->route('data-migration.index')
            ->with('success', 'Mapping imported successfully.');
    }

    // =========================================================================
    // Validate — adapted from Heratio DataMigrationController::validate()
    // =========================================================================

    public function validateFile(Request $request): JsonResponse
    {
        $filePath = session('dm_file');
        $targetType = session('dm_target', 'record');

        if (!$filePath) {
            return response()->json(['valid' => false, 'error' => 'No file in session.']);
        }

        $result = $this->service->validateImportFile($filePath, $targetType, []);
        return response()->json($result);
    }

    // =========================================================================
    // Rollback — adapted from OpenRiC original
    // =========================================================================

    public function rollback(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['job_id' => 'required|integer']);

        $result = $this->service->rollbackImport((int) $request->input('job_id'));

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        $flash = $result['success'] ? 'success' : 'error';
        return redirect()->route('data-migration.jobs')
            ->with($flash, $result['message']);
    }

    // =========================================================================
    // History alias — adapted from OpenRiC original
    // =========================================================================

    public function history(Request $request): View
    {
        $page = max(1, (int) $request->get('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $result = $this->service->getImportHistory($limit, $offset);

        return view('openric-data-migration::history', [
            'jobs' => $result['jobs'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => (int) ceil($result['total'] / $limit),
        ]);
    }

    // =========================================================================
    // Sector Export — adapted from Heratio DataMigrationController::sectorExport()
    // =========================================================================

    public function sectorExport(Request $request): View
    {
        return view('openric-data-migration::sector-export');
    }

    // =========================================================================
    // Preview Data — adapted from Heratio DataMigrationController::previewData()
    // =========================================================================

    public function previewData(Request $request): View
    {
        return view('openric-data-migration::preview-data', ['rows' => collect()]);
    }
}
