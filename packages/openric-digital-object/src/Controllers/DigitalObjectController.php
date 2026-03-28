<?php

declare(strict_types=1);

namespace OpenRiC\DigitalObject\Controllers;

use OpenRiC\DigitalObject\Contracts\DigitalObjectServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Digital object controller — adapted from Heratio DamController.
 *
 * Heratio controller uses slug-based routing with DamService.
 * OpenRiC uses IRI-based routing with DigitalObjectServiceInterface
 * backed by Fuseki/SPARQL.
 */
class DigitalObjectController extends Controller
{
    private DigitalObjectServiceInterface $service;

    public function __construct(DigitalObjectServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Dashboard view with stats and recent assets.
     *
     * Adapted from Heratio DamController — the original queries
     * display_object_config + dam_iptc_metadata for statistics.
     */
    public function dashboard()
    {
        $stats = $this->service->getDashboardStats();
        $recentAssets = $this->service->getRecentAssets(10);

        return view('openric-digital-object::dashboard', [
            'stats'        => $stats,
            'recentAssets' => $recentAssets,
        ]);
    }

    /**
     * Browse digital objects with search and filters.
     *
     * Adapted from Heratio DamController which uses DamService::browse()
     * with MySQL queries against information_object + dam_iptc_metadata.
     */
    public function browse(Request $request)
    {
        $result = $this->service->browse([
            'page'      => (int) $request->get('page', 1),
            'limit'     => (int) $request->get('limit', 25),
            'sort'      => $request->get('sort', 'lastUpdated'),
            'sortDir'   => $request->get('sortDir', 'desc'),
            'subquery'  => $request->get('subquery', ''),
            'mimeType'  => $request->get('mimeType', ''),
            'recordIri' => $request->get('recordIri', ''),
        ]);

        return view('openric-digital-object::browse', [
            'hits'    => $result['hits'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'limit'   => $result['limit'],
            'filters' => $request->only(['sort', 'sortDir', 'subquery', 'mimeType', 'recordIri']),
            'sortOptions' => [
                'alphabetic'  => 'Title',
                'identifier'  => 'Identifier',
                'date'        => 'Date created',
                'lastUpdated' => 'Date modified',
            ],
        ]);
    }

    /**
     * Show a single digital object.
     *
     * Adapted from Heratio DamController::show() which resolves slug → ID
     * then calls DamService::getById() with 7+ joins.
     */
    public function show(Request $request, string $iri)
    {
        $iri = $this->decodeIri($iri);
        $digitalObject = $this->service->find($iri);

        if ($digitalObject === null) {
            abort(404, 'Digital object not found');
        }

        $fileMetadata = $this->service->getFileMetadata($iri);

        return view('openric-digital-object::show', [
            'digitalObject' => $digitalObject,
            'fileMetadata'  => $fileMetadata,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(Request $request)
    {
        return view('openric-digital-object::create', [
            'recordIri' => $request->get('recordIri', ''),
        ]);
    }

    /**
     * Store a new digital object.
     *
     * Adapted from Heratio DamController store logic which validates then
     * calls DamService::create() (7-table transaction).
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'             => 'required|string|max:1024',
            'identifier'        => 'nullable|string|max:255',
            'description'       => 'nullable|string',
            'mime_type'         => 'nullable|string|max:255',
            'date_created'      => 'nullable|date',
            'creator'           => 'nullable|string|max:1024',
            'format'            => 'nullable|string|max:255',
            'access_conditions' => 'nullable|string',
            'license'           => 'nullable|string',
            'keywords'          => 'nullable|string',
            'record_iri'        => 'nullable|string|max:2048',
            'file'              => 'nullable|file|max:512000', // 500MB max
        ]);

        $userId = (string) auth()->id();
        $data = $request->only([
            'title', 'identifier', 'description', 'mime_type', 'date_created',
            'creator', 'format', 'access_conditions', 'license', 'keywords', 'record_iri',
        ]);

        $iri = $this->service->create($data, $userId);

        // Handle file upload if present
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $this->service->uploadFile($iri, $request->file('file'), $userId);
        }

        return redirect()
            ->route('digital-objects.show', ['iri' => $this->encodeIri($iri)])
            ->with('success', 'Digital object created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(string $iri)
    {
        $iri = $this->decodeIri($iri);
        $digitalObject = $this->service->find($iri);

        if ($digitalObject === null) {
            abort(404, 'Digital object not found');
        }

        return view('openric-digital-object::edit', [
            'digitalObject' => $digitalObject,
        ]);
    }

    /**
     * Update an existing digital object.
     *
     * Adapted from Heratio DamController update logic which validates
     * then calls DamService::update() (multi-table update in transaction).
     */
    public function update(Request $request, string $iri)
    {
        $iri = $this->decodeIri($iri);
        $digitalObject = $this->service->find($iri);

        if ($digitalObject === null) {
            abort(404, 'Digital object not found');
        }

        $request->validate([
            'title'             => 'required|string|max:1024',
            'identifier'        => 'nullable|string|max:255',
            'description'       => 'nullable|string',
            'mime_type'         => 'nullable|string|max:255',
            'date_created'      => 'nullable|date',
            'creator'           => 'nullable|string|max:1024',
            'format'            => 'nullable|string|max:255',
            'access_conditions' => 'nullable|string',
            'license'           => 'nullable|string',
            'keywords'          => 'nullable|string',
            'record_iri'        => 'nullable|string|max:2048',
        ]);

        $userId = (string) auth()->id();
        $data = $request->only([
            'title', 'identifier', 'description', 'mime_type', 'date_created',
            'creator', 'format', 'access_conditions', 'license', 'keywords', 'record_iri',
        ]);

        $this->service->update($iri, $data, $userId);

        return redirect()
            ->route('digital-objects.show', ['iri' => $this->encodeIri($iri)])
            ->with('success', 'Digital object updated successfully.');
    }

    /**
     * Delete a digital object.
     *
     * Adapted from Heratio DamController which calls DamService::delete()
     * (10-table delete in transaction).
     */
    public function destroy(Request $request, string $iri)
    {
        $iri = $this->decodeIri($iri);
        $digitalObject = $this->service->find($iri);

        if ($digitalObject === null) {
            abort(404, 'Digital object not found');
        }

        $userId = (string) auth()->id();
        $this->service->delete($iri, $userId);

        return redirect()
            ->route('digital-objects.browse')
            ->with('success', 'Digital object deleted successfully.');
    }

    /**
     * Handle file upload for an existing digital object.
     */
    public function upload(Request $request, string $iri)
    {
        $iri = $this->decodeIri($iri);
        $digitalObject = $this->service->find($iri);

        if ($digitalObject === null) {
            abort(404, 'Digital object not found');
        }

        $request->validate([
            'file' => 'required|file|max:512000', // 500MB max
        ]);

        $userId = (string) auth()->id();

        // Delete existing file if present
        $existingFile = $this->service->getFileMetadata($iri);
        if ($existingFile !== null) {
            $this->service->deleteFile($iri, $userId);
        }

        $result = $this->service->uploadFile($iri, $request->file('file'), $userId);

        return redirect()
            ->route('digital-objects.show', ['iri' => $this->encodeIri($iri)])
            ->with('success', 'File uploaded successfully (' . $result['filename'] . ').');
    }

    /**
     * Encode an IRI for use in URL segments.
     */
    private function encodeIri(string $iri): string
    {
        return base64_encode($iri);
    }

    /**
     * Decode an IRI from a URL segment.
     */
    private function decodeIri(string $encoded): string
    {
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            abort(400, 'Invalid IRI encoding');
        }
        return $decoded;
    }
}
