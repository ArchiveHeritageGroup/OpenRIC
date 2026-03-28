<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\Core\Contracts\StandardsMappingServiceInterface;
use OpenRiC\RecordManage\Contracts\RecordServiceInterface;
use OpenRiC\RecordManage\Requests\StoreRecordRequest;

class RecordController extends Controller
{
    public function __construct(
        private readonly RecordServiceInterface $service,
        private readonly StandardsMappingServiceInterface $mappingService,
    ) {}

    public function index(Request $request): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $result = $this->service->browse($request->only(['q']), $limit, $offset);

        return view('record-manage::record.index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    public function show(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));

        if ($viewMode === 'traditional') {
            $isadg = $this->mappingService->renderIsadG($entity['properties'] ?? []);

            return view('record-manage::record.show-traditional', [
                'entity' => $entity,
                'isadg' => $isadg,
                'viewMode' => $viewMode,
            ]);
        }

        return view('record-manage::record.show', [
            'entity' => $entity,
            'viewMode' => $viewMode,
        ]);
    }

    public function create(): View
    {
        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));

        if ($viewMode === 'traditional') {
            return view('record-manage::record.create-traditional');
        }

        return view('record-manage::record.create');
    }

    public function store(StoreRecordRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        if ($request->input('_form_type') === 'isadg') {
            $data = array_merge($data, ['_rico_properties' => $this->mappingService->isadgToRico($data)]);
        }

        $iri = $this->service->create($data, $user->getIri(), 'Created via OpenRiC UI');

        return redirect()->route('records.show', ['iri' => urlencode($iri)])
            ->with('success', 'Record created successfully.');
    }

    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        return view('record-manage::record.edit', ['entity' => $entity]);
    }

    public function update(StoreRecordRequest $request, string $iri): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        if ($request->input('_form_type') === 'isadg') {
            $data = array_merge($data, ['_rico_properties' => $this->mappingService->isadgToRico($data)]);
        }

        $this->service->update($iri, $data, $user->getIri(), 'Updated via OpenRiC UI');

        return redirect()->route('records.show', ['iri' => urlencode($iri)])
            ->with('success', 'Record updated successfully.');
    }

    public function destroy(string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->delete($iri, $user->getIri(), 'Deleted via OpenRiC UI');

        return redirect()->route('records.index')->with('success', 'Record deleted successfully.');
    }
}
