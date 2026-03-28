<?php

declare(strict_types=1);

namespace OpenRiC\Donor\Controllers;

use OpenRiC\Donor\Contracts\DonorServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Donor controller — adapted from Heratio DonorController (533 lines).
 *
 * Heratio uses slug-based routing with DonorService and DonorBrowseService.
 * Also includes agreement management (donor_agreement tables).
 * OpenRiC uses integer IDs with DonorServiceInterface backed by PostgreSQL.
 */
class DonorController extends Controller
{
    private DonorServiceInterface $service;

    public function __construct(DonorServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Browse donors with search and filters.
     *
     * Adapted from Heratio DonorController::browse() which uses
     * DonorBrowseService + SimplePager.
     */
    public function index(Request $request)
    {
        $result = $this->service->browse([
            'page'      => (int) $request->get('page', 1),
            'limit'     => (int) $request->get('limit', 25),
            'sort'      => $request->get('sort', 'alphabetic'),
            'sortDir'   => $request->get('sortDir', 'asc'),
            'subquery'  => $request->get('subquery', ''),
            'donorType' => $request->get('donorType', ''),
            'isActive'  => $request->has('isActive') ? (bool) $request->get('isActive') : null,
        ]);

        $stats = $this->service->getDonorStats();

        return view('openric-donor::index', [
            'hits'    => $result['hits'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'limit'   => $result['limit'],
            'stats'   => $stats,
            'filters' => $request->only(['sort', 'sortDir', 'subquery', 'donorType', 'isActive']),
            'sortOptions' => [
                'alphabetic'  => 'Name',
                'identifier'  => 'UUID',
                'type'        => 'Donor type',
                'lastUpdated' => 'Date modified',
            ],
            'typeOptions' => [
                'individual'   => 'Individual',
                'organization' => 'Organization',
                'estate'       => 'Estate',
                'government'   => 'Government',
            ],
        ]);
    }

    /**
     * Show a single donor with accessions.
     *
     * Adapted from Heratio DonorController::show() which resolves slug,
     * then fetches contacts and related accessions.
     */
    public function show(int $id)
    {
        $donor = $this->service->find($id);

        if ($donor === null) {
            abort(404, 'Donor not found');
        }

        $accessions = $this->service->getAccessionsForDonor($id);

        return view('openric-donor::show', [
            'donor'      => $donor,
            'accessions' => $accessions,
        ]);
    }

    /**
     * Show create form.
     *
     * Adapted from Heratio DonorController::create() which provides
     * an empty contact object for the form.
     */
    public function create()
    {
        return view('openric-donor::create', [
            'typeOptions' => [
                'individual'   => 'Individual',
                'organization' => 'Organization',
                'estate'       => 'Estate',
                'government'   => 'Government',
            ],
        ]);
    }

    /**
     * Store a new donor.
     *
     * Adapted from Heratio DonorController::store() which validates
     * authorized_form_of_name then calls DonorService::create()
     * (5-table transaction + contact sync).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:1024',
            'contact_person'  => 'nullable|string|max:255',
            'institution'     => 'nullable|string|max:1024',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:50',
            'address'         => 'nullable|string|max:1024',
            'city'            => 'nullable|string|max:255',
            'region'          => 'nullable|string|max:255',
            'country_code'    => 'nullable|string|max:3',
            'postal_code'     => 'nullable|string|max:20',
            'donor_type'      => 'nullable|string|in:individual,organization,estate,government',
            'notes'           => 'nullable|string',
            'is_active'       => 'nullable|boolean',
        ]);

        $userId = (int) auth()->id();
        $data = $request->only([
            'name', 'contact_person', 'institution', 'email', 'phone',
            'address', 'city', 'region', 'country_code', 'postal_code',
            'donor_type', 'notes', 'is_active',
        ]);

        $id = $this->service->create($data, $userId);

        return redirect()
            ->route('donors.show', $id)
            ->with('success', 'Donor created successfully.');
    }

    /**
     * Show edit form.
     *
     * Adapted from Heratio DonorController::edit() which resolves slug,
     * fetches donor and contacts.
     */
    public function edit(int $id)
    {
        $donor = $this->service->find($id);

        if ($donor === null) {
            abort(404, 'Donor not found');
        }

        return view('openric-donor::edit', [
            'donor' => $donor,
            'typeOptions' => [
                'individual'   => 'Individual',
                'organization' => 'Organization',
                'estate'       => 'Estate',
                'government'   => 'Government',
            ],
        ]);
    }

    /**
     * Update an existing donor.
     *
     * Adapted from Heratio DonorController::update() which validates
     * then calls DonorService::update() (multi-table update + contact sync).
     */
    public function update(Request $request, int $id)
    {
        $donor = $this->service->find($id);

        if ($donor === null) {
            abort(404, 'Donor not found');
        }

        $request->validate([
            'name'            => 'required|string|max:1024',
            'contact_person'  => 'nullable|string|max:255',
            'institution'     => 'nullable|string|max:1024',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:50',
            'address'         => 'nullable|string|max:1024',
            'city'            => 'nullable|string|max:255',
            'region'          => 'nullable|string|max:255',
            'country_code'    => 'nullable|string|max:3',
            'postal_code'     => 'nullable|string|max:20',
            'donor_type'      => 'nullable|string|in:individual,organization,estate,government',
            'notes'           => 'nullable|string',
            'is_active'       => 'nullable|boolean',
        ]);

        $data = $request->only([
            'name', 'contact_person', 'institution', 'email', 'phone',
            'address', 'city', 'region', 'country_code', 'postal_code',
            'donor_type', 'notes', 'is_active',
        ]);

        $this->service->update($id, $data);

        return redirect()
            ->route('donors.show', $id)
            ->with('success', 'Donor updated successfully.');
    }

    /**
     * Delete (soft-delete) a donor.
     *
     * Adapted from Heratio DonorController::destroy() which resolves slug
     * then calls DonorService::delete() (14+ table hard delete).
     * OpenRiC uses soft delete.
     */
    public function destroy(Request $request, int $id)
    {
        $donor = $this->service->find($id);

        if ($donor === null) {
            abort(404, 'Donor not found');
        }

        $this->service->delete($id);

        return redirect()
            ->route('donors.index')
            ->with('success', 'Donor deleted successfully.');
    }
}
