<?php

declare(strict_types=1);

namespace OpenRiC\AgentManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\AgentManage\Contracts\CorporateBodyServiceInterface;
use OpenRiC\Core\Contracts\StandardsMappingServiceInterface;

class CorporateBodyController extends Controller
{
    public function __construct(
        private readonly CorporateBodyServiceInterface $service,
        private readonly StandardsMappingServiceInterface $mappingService,
    ) {}

    public function index(Request $request): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $result = $this->service->browse($request->only(['q']), $limit, $offset);

        return view('agent-manage::corporate-bodies.index', [
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
            $isaar = $this->mappingService->renderIsaarCpf($entity['properties'] ?? []);

            return view('agent-manage::corporate-bodies.show-traditional', [
                'entity' => $entity,
                'isaar' => $isaar,
                'viewMode' => $viewMode,
            ]);
        }

        return view('agent-manage::corporate-bodies.show', [
            'entity' => $entity,
            'viewMode' => $viewMode,
        ]);
    }

    public function create(): View
    {
        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));

        if ($viewMode === 'traditional') {
            return view('agent-manage::corporate-bodies.create-traditional');
        }

        return view('agent-manage::corporate-bodies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => 'required|string|max:1000', 'identifier' => 'nullable|string|max:255']);
        $user = Auth::user();

        if ($request->input('_form_type') === 'isaar_cpf') {
            $data = array_merge($data, ['_rico_properties' => $this->mappingService->isaarCpfToRico($request->all())]);
        }

        $iri = $this->service->create($data, $user->getIri(), 'Created via OpenRiC UI');

        return redirect()->route('corporate-bodies.show', ['iri' => urlencode($iri)])->with('success', 'CorporateBody created.');
    }

    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        return view('agent-manage::corporate-bodies.edit', ['entity' => $entity]);
    }

    public function update(Request $request, string $iri): RedirectResponse
    {
        $data = $request->validate(['title' => 'required|string|max:1000', 'identifier' => 'nullable|string|max:255']);
        $user = Auth::user();

        if ($request->input('_form_type') === 'isaar_cpf') {
            $data = array_merge($data, ['_rico_properties' => $this->mappingService->isaarCpfToRico($request->all())]);
        }

        $this->service->update($iri, $data, $user->getIri(), 'Updated via OpenRiC UI');

        return redirect()->route('corporate-bodies.show', ['iri' => urlencode($iri)])->with('success', 'CorporateBody updated.');
    }

    public function destroy(string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->delete($iri, $user->getIri(), 'Deleted via OpenRiC UI');

        return redirect()->route('corporate-bodies.index')->with('success', 'CorporateBody deleted.');
    }
}
