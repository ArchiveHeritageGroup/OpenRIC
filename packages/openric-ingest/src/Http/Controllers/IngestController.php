<?php

declare(strict_types=1);

namespace OpenRiC\Ingest\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use OpenRiC\Ingest\Contracts\IngestServiceInterface;

/**
 * Ingest controller -- adapted from Heratio AhgIngest\Controllers\IngestController (158 lines).
 */
class IngestController extends Controller
{
    public function __construct(
        private readonly IngestServiceInterface $service,
    ) {}

    /**
     * Dashboard: import history and stats.
     */
    public function index(Request $request): JsonResponse
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        $history = $this->service->getImportHistory($page, $limit);
        $stats   = $this->service->getImportStats();

        return response()->json(array_merge($history, ['stats' => $stats]));
    }

    /**
     * CSV upload form data (returns available mapping targets).
     */
    public function csvUpload(): JsonResponse
    {
        return response()->json([
            'mapping_targets' => [
                'title'                => 'Title',
                'identifier'           => 'Identifier / Reference Code',
                'description'          => 'Scope and Content',
                'date'                 => 'Date Statement',
                'date_start'           => 'Start Date',
                'date_end'             => 'End Date',
                'extent'               => 'Extent / Physical Description',
                'creator'              => 'Creator',
                'level_of_description' => 'Level of Description',
                'language'             => 'Language',
                'access_conditions'    => 'Access Conditions',
                'finding_aid'         => 'Finding Aid',
                'parent_iri'           => 'Parent IRI',
            ],
        ]);
    }

    /**
     * Process CSV upload and import.
     */
    public function csvProcess(Request $request): JsonResponse
    {
        $request->validate([
            'file'           => 'required|file|mimes:csv,txt|max:51200',
            'column_mapping' => 'required|array',
        ]);

        $file     = $request->file('file');
        $filepath = $file->getRealPath();
        $mapping  = $request->input('column_mapping');
        $options  = $request->only(['iri_prefix']);

        $result = $this->service->importCsv($filepath, $mapping, (int) Auth::id(), $options);

        return response()->json($result, 201);
    }

    /**
     * XML upload form data (returns supported formats).
     */
    public function xmlUpload(): JsonResponse
    {
        return response()->json([
            'formats' => [
                'ead3'    => 'EAD3 (Encoded Archival Description v3)',
                'eac-cpf' => 'EAC-CPF (Encoded Archival Context — Corporate Bodies, Persons, Families)',
            ],
        ]);
    }

    /**
     * Process XML upload and import.
     */
    public function xmlProcess(Request $request): JsonResponse
    {
        $request->validate([
            'file'   => 'required|file|mimes:xml|max:51200',
            'format' => 'required|string|in:ead3,eac-cpf',
        ]);

        $file     = $request->file('file');
        $filepath = $file->getRealPath();
        $format   = $request->input('format');
        $options  = $request->only(['iri_prefix']);

        $result = $this->service->importXml($filepath, $format, (int) Auth::id(), $options);

        return response()->json($result, 201);
    }

    /**
     * Import history list.
     */
    public function history(Request $request): JsonResponse
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        return response()->json($this->service->getImportHistory($page, $limit));
    }

    /**
     * Preview: validate an import job before committing.
     */
    public function preview(int $jobId): JsonResponse
    {
        $validation = $this->service->validateImport($jobId);

        return response()->json($validation);
    }
}
