<?php

declare(strict_types=1);

namespace OpenRiC\InstantiationManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\InstantiationManage\Contracts\InstantiationServiceInterface;

class InstantiationController extends Controller
{
    public function __construct(
        private readonly InstantiationServiceInterface $service,
    ) {}

    public function index(Request $request): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $filters = $request->only(['q', 'carrier_type', 'representation_type', 'record_iri']);
        $result = $this->service->browse($filters, $limit, $offset);

        $carrierTypes = $this->service->getCarrierTypes();
        $representationTypes = $this->service->getRepresentationTypes();

        return view('instantiation-manage::instantiations.index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
            'filters' => $filters,
            'carrierTypes' => $carrierTypes,
            'representationTypes' => $representationTypes,
        ]);
    }

    public function show(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        return view('instantiation-manage::instantiations.show', [
            'entity' => $entity,
            'viewMode' => session('openric_view_mode', config('openric.default_view', 'ric')),
        ]);
    }

    public function create(Request $request): View
    {
        $carrierTypes = $this->service->getCarrierTypes();
        $representationTypes = $this->service->getRepresentationTypes();

        return view('instantiation-manage::instantiations.create', [
            'carrierTypes' => $carrierTypes,
            'representationTypes' => $representationTypes,
            'presetRecordIri' => $request->get('record_iri', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'                    => 'required|string|max:1000',
            'identifier'               => 'nullable|string|max:255',
            'production_technique_type' => 'nullable|string|max:2048',
            'representation_type'       => 'nullable|string|max:2048',
            'carrier_type'              => 'nullable|string|max:2048',
            'extent'                   => 'nullable|string|max:500',
            'physical_location'         => 'nullable|string|max:2048',
            'record_iri'                => 'nullable|string|max:2048',
            'mime_type'                => 'nullable|string|max:255',
            'quantity'                 => 'nullable|string|max:255',
            'condition'                => 'nullable|string|max:2000',
            'date_of_instantiation'    => 'nullable|string|max:255',
            'descriptive_note'         => 'nullable|string|max:5000',
        ]);

        $user = Auth::user();
        $iri = $this->service->create($data, $user->getIri(), 'Created via OpenRiC UI');

        return redirect()
            ->route('instantiations.show', ['iri' => urlencode($iri)])
            ->with('success', 'Instantiation created.');
    }

    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        $carrierTypes = $this->service->getCarrierTypes();
        $representationTypes = $this->service->getRepresentationTypes();

        return view('instantiation-manage::instantiations.edit', [
            'entity' => $entity,
            'carrierTypes' => $carrierTypes,
            'representationTypes' => $representationTypes,
        ]);
    }

    public function update(Request $request, string $iri): RedirectResponse
    {
        $data = $request->validate([
            'title'                    => 'required|string|max:1000',
            'identifier'               => 'nullable|string|max:255',
            'production_technique_type' => 'nullable|string|max:2048',
            'representation_type'       => 'nullable|string|max:2048',
            'carrier_type'              => 'nullable|string|max:2048',
            'extent'                   => 'nullable|string|max:500',
            'physical_location'         => 'nullable|string|max:2048',
            'record_iri'                => 'nullable|string|max:2048',
            'mime_type'                => 'nullable|string|max:255',
            'quantity'                 => 'nullable|string|max:255',
            'condition'                => 'nullable|string|max:2000',
            'date_of_instantiation'    => 'nullable|string|max:255',
            'descriptive_note'         => 'nullable|string|max:5000',
        ]);

        $user = Auth::user();
        $this->service->update($iri, $data, $user->getIri(), 'Updated via OpenRiC UI');

        return redirect()
            ->route('instantiations.show', ['iri' => urlencode($iri)])
            ->with('success', 'Instantiation updated.');
    }

    public function destroy(string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->delete($iri, $user->getIri(), 'Deleted via OpenRiC UI');

        return redirect()
            ->route('instantiations.index')
            ->with('success', 'Instantiation deleted.');
    }

    public function forRecord(Request $request, string $recordIri): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $result = $this->service->getForRecord($recordIri, $limit, $offset);

        return view('instantiation-manage::instantiations.for-record', [
            'items' => $result['items'],
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
            'recordIri' => $recordIri,
        ]);
    }
}
