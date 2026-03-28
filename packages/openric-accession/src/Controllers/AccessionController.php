<?php

declare(strict_types=1);

namespace OpenRiC\Accession\Controllers;

use OpenRiC\Accession\Contracts\AccessionServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Accession controller — adapted from Heratio AccessionController (622 lines).
 *
 * Heratio uses slug-based routing, term IDs for status/priority/type,
 * and culture-based i18n. OpenRiC uses integer IDs for accessions,
 * string-based status values, and PostgreSQL storage.
 */
class AccessionController extends Controller
{
    private AccessionServiceInterface $service;

    public function __construct(AccessionServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Browse accessions with search and filters.
     *
     * Adapted from Heratio AccessionController::browse() which uses
     * AccessionBrowseService + SimplePager + term name resolution.
     */
    public function index(Request $request)
    {
        $result = $this->service->browse([
            'page'     => (int) $request->get('page', 1),
            'limit'    => (int) $request->get('limit', 25),
            'sort'     => $request->get('sort', 'lastUpdated'),
            'sortDir'  => $request->get('sortDir', 'desc'),
            'subquery' => $request->get('subquery', ''),
            'status'   => $request->get('status', ''),
        ]);

        $stats = $this->service->getAccessionStats();

        return view('openric-accession::index', [
            'hits'    => $result['hits'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'limit'   => $result['limit'],
            'stats'   => $stats,
            'filters' => $request->only(['sort', 'sortDir', 'subquery', 'status']),
            'sortOptions' => [
                'alphabetic'  => 'Title',
                'identifier'  => 'Accession number',
                'date'        => 'Received date',
                'lastUpdated' => 'Date modified',
            ],
            'statusOptions' => [
                'pending'     => 'Pending',
                'in_progress' => 'In Progress',
                'processed'   => 'Processed',
                'archived'    => 'Archived',
            ],
        ]);
    }

    /**
     * Show a single accession with all details.
     *
     * Adapted from Heratio AccessionController::show() which resolves
     * slug, fetches term names, donors (via relation table), deaccessions,
     * accruals, creators, dates, events, alternative identifiers, linked
     * information objects, rights, and physical objects (12+ queries).
     */
    public function show(int $id)
    {
        $accession = $this->service->find($id);

        if ($accession === null) {
            abort(404, 'Accession not found');
        }

        $linkedRecords = $this->service->getLinkedRecords($id);

        return view('openric-accession::show', [
            'accession'     => $accession,
            'linkedRecords' => $linkedRecords,
        ]);
    }

    /**
     * Show create form.
     *
     * Adapted from Heratio AccessionController::create() which loads
     * form choices from multiple taxonomy lookups.
     */
    public function create(Request $request)
    {
        $nextNumber = $this->service->generateAccessionNumber();

        return view('openric-accession::create', [
            'nextAccessionNumber' => $nextNumber,
            'recordIri'           => $request->get('recordIri', ''),
            'statusOptions' => [
                'pending'     => 'Pending',
                'in_progress' => 'In Progress',
                'processed'   => 'Processed',
                'archived'    => 'Archived',
            ],
        ]);
    }

    /**
     * Store a new accession.
     *
     * Adapted from Heratio AccessionController::store() which validates
     * 14+ fields then calls AccessionService::create() (4-table transaction).
     */
    public function store(Request $request)
    {
        $request->validate([
            'accession_number'    => 'required|string|max:255|unique:accessions,accession_number',
            'title'               => 'nullable|string|max:1024',
            'donor_id'            => 'nullable|integer|exists:donors,id',
            'received_date'       => 'nullable|date',
            'description'         => 'nullable|string',
            'extent'              => 'nullable|string|max:1024',
            'condition_notes'     => 'nullable|string',
            'access_restrictions' => 'nullable|string',
            'processing_status'   => 'nullable|string|in:pending,in_progress,processed,archived',
            'object_iri'          => 'nullable|string|max:2048',
            'items'               => 'nullable|array',
            'items.*.description' => 'nullable|string',
            'items.*.object_iri'  => 'nullable|string|max:2048',
            'items.*.quantity'    => 'nullable|integer|min:1',
        ]);

        $userId = (int) auth()->id();
        $data = $request->only([
            'accession_number', 'title', 'donor_id', 'received_date',
            'description', 'extent', 'condition_notes', 'access_restrictions',
            'processing_status', 'object_iri', 'items',
        ]);

        $id = $this->service->create($data, $userId);

        return redirect()
            ->route('accessions.show', $id)
            ->with('success', 'Accession created successfully.');
    }

    /**
     * Show edit form.
     *
     * Adapted from Heratio AccessionController::edit() which resolves
     * slug, fetches accession, donor, donor contact, and form choices.
     */
    public function edit(int $id)
    {
        $accession = $this->service->find($id);

        if ($accession === null) {
            abort(404, 'Accession not found');
        }

        return view('openric-accession::edit', [
            'accession' => $accession,
            'statusOptions' => [
                'pending'     => 'Pending',
                'in_progress' => 'In Progress',
                'processed'   => 'Processed',
                'archived'    => 'Archived',
            ],
        ]);
    }

    /**
     * Update an existing accession.
     *
     * Adapted from Heratio AccessionController::update() which validates
     * 14+ fields then calls AccessionService::update() (multi-table update).
     */
    public function update(Request $request, int $id)
    {
        $accession = $this->service->find($id);

        if ($accession === null) {
            abort(404, 'Accession not found');
        }

        $request->validate([
            'accession_number'    => 'required|string|max:255|unique:accessions,accession_number,' . $id,
            'title'               => 'nullable|string|max:1024',
            'donor_id'            => 'nullable|integer|exists:donors,id',
            'received_date'       => 'nullable|date',
            'description'         => 'nullable|string',
            'extent'              => 'nullable|string|max:1024',
            'condition_notes'     => 'nullable|string',
            'access_restrictions' => 'nullable|string',
            'processing_status'   => 'nullable|string|in:pending,in_progress,processed,archived',
            'processed_by'        => 'nullable|integer|exists:users,id',
            'object_iri'          => 'nullable|string|max:2048',
            'items'               => 'nullable|array',
            'items.*.id'          => 'nullable|integer',
            'items.*.description' => 'nullable|string',
            'items.*.object_iri'  => 'nullable|string|max:2048',
            'items.*.quantity'    => 'nullable|integer|min:1',
        ]);

        $data = $request->only([
            'accession_number', 'title', 'donor_id', 'received_date',
            'description', 'extent', 'condition_notes', 'access_restrictions',
            'processing_status', 'processed_by', 'object_iri', 'items',
        ]);

        // Auto-set processed_at when status changes to processed
        if (($data['processing_status'] ?? '') === 'processed' && ($accession['processing_status'] ?? '') !== 'processed') {
            $data['processed_at'] = now();
            if (empty($data['processed_by'])) {
                $data['processed_by'] = auth()->id();
            }
        }

        $this->service->update($id, $data);

        return redirect()
            ->route('accessions.show', $id)
            ->with('success', 'Accession updated successfully.');
    }

    /**
     * Delete an accession.
     *
     * Adapted from Heratio AccessionController::destroy() which resolves
     * slug then calls AccessionService::delete() (8+ table delete).
     */
    public function destroy(Request $request, int $id)
    {
        $accession = $this->service->find($id);

        if ($accession === null) {
            abort(404, 'Accession not found');
        }

        $this->service->delete($id);

        return redirect()
            ->route('accessions.index')
            ->with('success', 'Accession deleted successfully.');
    }
}
