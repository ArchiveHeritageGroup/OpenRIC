<?php

declare(strict_types=1);

namespace OpenRiC\ActivityManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\ActivityManage\Contracts\RiCFunctionServiceInterface;

class RiCFunctionController extends Controller
{
    public function __construct(
        private readonly RiCFunctionServiceInterface $service,
    ) {}

    public function index(Request $request): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $filters = $request->only(['q', 'function_type', 'agent_iri']);
        $result = $this->service->browse($filters, $limit, $offset);

        return view('activity-manage::functions.index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
            'filters' => $filters,
        ]);
    }

    public function show(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        return view('activity-manage::functions.show', [
            'entity' => $entity,
            'viewMode' => session('openric_view_mode', config('openric.default_view', 'ric')),
        ]);
    }

    public function create(): View
    {
        return view('activity-manage::functions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'            => 'required|string|max:1000',
            'identifier'       => 'nullable|string|max:255',
            'function_type'    => 'nullable|string|max:2048',
            'date_iri'         => 'nullable|string|max:2048',
            'descriptive_note' => 'nullable|string|max:5000',
            'performs_iris'    => 'nullable|array',
            'performs_iris.*'   => 'string|max:2048',
        ]);

        $user = Auth::user();
        $iri = $this->service->create($data, $user->getIri(), 'Created via OpenRiC UI');

        return redirect()
            ->route('functions.show', ['iri' => urlencode($iri)])
            ->with('success', 'Function created.');
    }

    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        return view('activity-manage::functions.edit', ['entity' => $entity]);
    }

    public function update(Request $request, string $iri): RedirectResponse
    {
        $data = $request->validate([
            'title'            => 'required|string|max:1000',
            'identifier'       => 'nullable|string|max:255',
            'function_type'    => 'nullable|string|max:2048',
            'date_iri'         => 'nullable|string|max:2048',
            'descriptive_note' => 'nullable|string|max:5000',
            'performs_iris'    => 'nullable|array',
            'performs_iris.*'   => 'string|max:2048',
        ]);

        $user = Auth::user();
        $this->service->update($iri, $data, $user->getIri(), 'Updated via OpenRiC UI');

        return redirect()
            ->route('functions.show', ['iri' => urlencode($iri)])
            ->with('success', 'Function updated.');
    }

    public function destroy(string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->delete($iri, $user->getIri(), 'Deleted via OpenRiC UI');

        return redirect()
            ->route('functions.index')
            ->with('success', 'Function deleted.');
    }

    public function forAgent(Request $request, string $agentIri): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $result = $this->service->getForAgent($agentIri, $limit, $offset);

        return view('activity-manage::functions.for-agent', [
            'items' => $result['items'],
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
            'agentIri' => $agentIri,
        ]);
    }
}
