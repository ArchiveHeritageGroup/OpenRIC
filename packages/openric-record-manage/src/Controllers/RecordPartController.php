<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\RecordManage\Contracts\RecordPartServiceInterface;
use OpenRiC\RecordManage\Requests\StoreRecordPartRequest;

class RecordPartController extends Controller
{
    public function __construct(
        private readonly RecordPartServiceInterface $service,
    ) {}

    public function index(Request $request): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $result = $this->service->browse($request->only(['q']), $limit, $offset);

        return view('record-manage::record-part.index', [
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

        return view('record-manage::record-part.show', [
            'entity' => $entity,
            'viewMode' => session('openric_view_mode', config('openric.default_view', 'ric')),
        ]);
    }

    public function create(): View
    {
        return view('record-manage::record-part.create');
    }

    public function store(StoreRecordPartRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $iri = $this->service->create($request->validated(), $user->getIri(), 'Created via OpenRiC UI');

        return redirect()->route('record-parts.show', ['iri' => urlencode($iri)])
            ->with('success', 'Record Part created successfully.');
    }

    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        return view('record-manage::record-part.edit', ['entity' => $entity]);
    }

    public function update(StoreRecordPartRequest $request, string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->update($iri, $request->validated(), $user->getIri(), 'Updated via OpenRiC UI');

        return redirect()->route('record-parts.show', ['iri' => urlencode($iri)])
            ->with('success', 'Record Part updated successfully.');
    }

    public function destroy(string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->delete($iri, $user->getIri(), 'Deleted via OpenRiC UI');

        return redirect()->route('record-parts.index')->with('success', 'Record Part deleted successfully.');
    }
}
