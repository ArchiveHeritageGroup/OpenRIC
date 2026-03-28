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
 * Data migration controller — adapted from Heratio ahg-data-migration DataMigrationController.
 *
 * Provides dashboard, file upload, CSV analysis, column mapping, preview, import execution,
 * import history, and rollback functionality.
 */
class DataMigrationController extends Controller
{
    public function __construct(
        private readonly DataMigrationServiceInterface $service,
    ) {
    }

    /**
     * Dashboard: show presets, recent jobs, stats.
     */
    public function index(): View
    {
        $presets = $this->service->getFieldMappingPresets();
        $history = $this->service->getImportHistory(10);

        return view('openric-data-migration::index', [
            'presets' => $presets,
            'recentJobs' => $history['jobs'],
            'totalJobs' => $history['total'],
        ]);
    }

    /**
     * Upload form (GET) or handle file upload (POST).
     */
    public function upload(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('get')) {
            $presets = $this->service->getFieldMappingPresets();
            return view('openric-data-migration::upload', ['presets' => $presets]);
        }

        $request->validate([
            'file' => 'required|file|max:102400|mimes:csv,txt,tsv',
            'target_type' => 'required|string|in:record,agent,accession',
        ]);

        $path = $request->file('file')->store('data-migration/uploads');
        $fileName = $request->file('file')->getClientOriginalName();

        session([
            'dm_file' => $path,
            'dm_filename' => $fileName,
            'dm_target' => $request->input('target_type'),
        ]);

        return redirect()->route('data-migration.analyze');
    }

    /**
     * Analyze the uploaded CSV: show headers, types, preview.
     */
    public function analyze(Request $request): View|RedirectResponse
    {
        $filePath = session('dm_file');
        if (!$filePath) {
            return redirect()->route('data-migration.upload')
                ->with('error', 'No file uploaded. Please upload a file first.');
        }

        $analysis = $this->service->analyzeCsv($filePath);
        $targetType = session('dm_target', 'record');
        $targetFields = $this->service->getTargetFields($targetType);

        return view('openric-data-migration::analyze', [
            'fileName' => session('dm_filename', 'unknown'),
            'analysis' => $analysis,
            'targetType' => $targetType,
            'targetFields' => $targetFields,
        ]);
    }

    /**
     * Column mapping UI: map source columns to target fields.
     */
    public function mapping(Request $request): View|RedirectResponse
    {
        $filePath = session('dm_file');
        if (!$filePath) {
            return redirect()->route('data-migration.upload')
                ->with('error', 'No file uploaded. Please upload a file first.');
        }

        $analysis = $this->service->analyzeCsv($filePath);
        $targetType = session('dm_target', 'record');
        $targetFields = $this->service->getTargetFields($targetType);
        $presets = $this->service->getFieldMappingPresets();

        return view('openric-data-migration::mapping', [
            'fileName' => session('dm_filename', 'unknown'),
            'sourceColumns' => $analysis['headers'],
            'totalRows' => $analysis['totalRows'],
            'columnTypes' => $analysis['columnTypes'],
            'targetType' => $targetType,
            'targetFields' => $targetFields,
            'presets' => $presets,
        ]);
    }

    /**
     * Preview: show transformed/mapped data before executing.
     */
    public function preview(Request $request): View|JsonResponse
    {
        $filePath = session('dm_file');
        $targetType = session('dm_target', 'record');

        if (!$filePath) {
            return response()->json(['error' => 'No file in session'], 400);
        }

        $columnMapping = json_decode((string) $request->input('column_mapping', '{}'), true) ?: [];
        $transformRules = json_decode((string) $request->input('transform_rules', '{}'), true) ?: [];

        $analysis = $this->service->analyzeCsv($filePath, 20);
        $mapped = $this->service->mapColumns($columnMapping, $analysis['rows']);

        $previewRows = [];
        foreach ($mapped as $row) {
            $transformed = $this->service->transformRow($row, $transformRules);
            $validation = $this->service->validateRow($transformed, $targetType);
            $previewRows[] = [
                'data' => $transformed,
                'valid' => $validation['valid'],
                'errors' => $validation['errors'],
            ];
        }

        if ($request->expectsJson()) {
            return response()->json(['preview' => $previewRows, 'totalRows' => $analysis['totalRows']]);
        }

        return view('openric-data-migration::preview', [
            'previewRows' => $previewRows,
            'totalRows' => $analysis['totalRows'],
            'targetType' => $targetType,
        ]);
    }

    /**
     * Execute the import: create a job and process all rows.
     */
    public function execute(Request $request): RedirectResponse|JsonResponse
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

        $columnMapping = json_decode((string) $request->input('column_mapping', '{}'), true) ?: [];
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
        $result = $this->service->importBatch($transformedRows, $targetType, $jobId);

        // Clear session
        session()->forget(['dm_file', 'dm_filename', 'dm_target']);

        if ($request->expectsJson()) {
            return response()->json(array_merge(['job_id' => $jobId], $result));
        }

        $msg = sprintf(
            'Import complete: %d imported, %d skipped, %d errors.',
            $result['imported'],
            $result['skipped'],
            count($result['errors'])
        );

        return redirect()->route('data-migration.history')
            ->with('success', $msg);
    }

    /**
     * Import history list.
     */
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

    /**
     * Rollback a completed import.
     */
    public function rollback(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['job_id' => 'required|integer']);

        $result = $this->service->rollbackImport((int) $request->input('job_id'));

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        $flash = $result['success'] ? 'success' : 'error';
        return redirect()->route('data-migration.history')
            ->with($flash, $result['message']);
    }
}
