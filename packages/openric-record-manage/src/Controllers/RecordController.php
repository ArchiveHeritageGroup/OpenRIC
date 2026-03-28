<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\RecordManage\Contracts\RecordServiceInterface;
use OpenRiC\RecordManage\Requests\StoreRecordRequest;

class RecordController extends Controller
{
    public function __construct(
        private readonly RecordServiceInterface $service,
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

        return view('record-manage::record.show', [
            'entity' => $entity,
            'viewMode' => session('openric_view_mode', config('openric.default_view', 'ric')),
        ]);
    }

    public function create(): View
    {
        return view('record-manage::record.create');
    }

    public function store(StoreRecordRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $iri = $this->service->create($request->validated(), $user->getIri(), 'Created via OpenRiC UI');

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
        $this->service->update($iri, $request->validated(), $user->getIri(), 'Updated via OpenRiC UI');

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
