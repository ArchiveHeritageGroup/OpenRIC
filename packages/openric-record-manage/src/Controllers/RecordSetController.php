<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\Core\Contracts\StandardsMappingServiceInterface;
use OpenRiC\RecordManage\Contracts\RecordSetServiceInterface;
use OpenRiC\RecordManage\Requests\StoreRecordSetRequest;
use OpenRiC\RecordManage\Requests\UpdateRecordSetRequest;

class RecordSetController extends Controller
{
    public function __construct(
        private readonly RecordSetServiceInterface $service,
        private readonly StandardsMappingServiceInterface $mappingService,
    ) {}

    public function index(Request $request): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $result = $this->service->browse($request->only(['q']), $limit, $offset);

        return view('record-manage::record-set.index', [
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

        $children = $this->service->getChildren($iri);
        $creators = $this->service->getCreators($iri);
        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));

        if ($viewMode === 'traditional') {
            $isadg = $this->mappingService->renderIsadG($entity['properties'] ?? []);

            return view('record-manage::record-set.show-traditional', [
                'entity' => $entity,
                'isadg' => $isadg,
                'children' => $children,
                'creators' => $creators,
                'viewMode' => $viewMode,
            ]);
        }

        return view('record-manage::record-set.show', [
            'entity' => $entity,
            'children' => $children,
            'creators' => $creators,
            'viewMode' => $viewMode,
        ]);
    }

    public function create(): View
    {
        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));

        if ($viewMode === 'traditional') {
            return view('record-manage::record-set.create-traditional');
        }

        return view('record-manage::record-set.create');
    }

    public function store(StoreRecordSetRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        // If submitted from ISAD(G) form, convert to RiC-O properties
        if ($request->input('_form_type') === 'isadg') {
            $ricoProperties = $this->mappingService->isadgToRico($data);
            $data = array_merge($data, ['_rico_properties' => $ricoProperties]);
        }

        $iri = $this->service->create(
            $data,
            $user->getIri(),
            'Created via OpenRiC UI'
        );

        return redirect()->route('record-sets.show', ['iri' => urlencode($iri)])
            ->with('success', 'Record Set created successfully.');
    }

    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);

        if ($entity === null) {
            abort(404);
        }

        return view('record-manage::record-set.edit', ['entity' => $entity]);
    }

    public function update(UpdateRecordSetRequest $request, string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->update($iri, $request->validated(), $user->getIri(), 'Updated via OpenRiC UI');

        return redirect()->route('record-sets.show', ['iri' => urlencode($iri)])
            ->with('success', 'Record Set updated successfully.');
    }

    public function destroy(string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->delete($iri, $user->getIri(), 'Deleted via OpenRiC UI');

        return redirect()->route('record-sets.index')
            ->with('success', 'Record Set deleted successfully.');
    }
}
