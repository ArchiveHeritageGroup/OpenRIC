<?php

declare(strict_types=1);

namespace OpenRiC\ActivityManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\ActivityManage\Contracts\MandateServiceInterface;

class MandateController extends Controller
{
    public function __construct(
        private readonly MandateServiceInterface $service,
    ) {}

    public function index(Request $request): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $filters = $request->only(['q', 'mandate_type', 'agent_iri']);
        $result = $this->service->browse($filters, $limit, $offset);

        return view('activity-manage::mandates.index', [
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

        return view('activity-manage::mandates.show', [
            'entity' => $entity,
            'viewMode' => session('openric_view_mode', config('openric.default_view', 'ric')),
        ]);
    }

    public function create(): View
    {
        return view('activity-manage::mandates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'              => 'required|string|max:1000',
            'identifier'         => 'nullable|string|max:255',
            'mandate_type'       => 'nullable|string|max:2048',
            'date_iri'           => 'nullable|string|max:2048',
            'beginning_date'     => 'nullable|string|max:255',
            'end_date'           => 'nullable|string|max:255',
            'descriptive_note'   => 'nullable|string|max:5000',
            'regulated_by_iris'   => 'nullable|array',
            'regulated_by_iris.*' => 'string|max:2048',
        ]);

        $user = Auth::user();
        $iri = $this->service->create($data, $user->getIri(), 'Created via OpenRiC UI');

        return redirect()
            ->route('mandates.show', ['iri' => urlencode($iri)])
            ->with('success', 'Mandate created.');
    }

    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        return view('activity-manage::mandates.edit', ['entity' => $entity]);
    }

    public function update(Request $request, string $iri): RedirectResponse
    {
        $data = $request->validate([
            'title'              => 'required|string|max:1000',
            'identifier'         => 'nullable|string|max:255',
            'mandate_type'       => 'nullable|string|max:2048',
            'date_iri'           => 'nullable|string|max:2048',
            'beginning_date'     => 'nullable|string|max:255',
            'end_date'           => 'nullable|string|max:255',
            'descriptive_note'   => 'nullable|string|max:5000',
            'regulated_by_iris'   => 'nullable|array',
            'regulated_by_iris.*' => 'string|max:2048',
        ]);

        $user = Auth::user();
        $this->service->update($iri, $data, $user->getIri(), 'Updated via OpenRiC UI');

        return redirect()
            ->route('mandates.show', ['iri' => urlencode($iri)])
            ->with('success', 'Mandate updated.');
    }

    public function destroy(string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->delete($iri, $user->getIri(), 'Deleted via OpenRiC UI');

        return redirect()
            ->route('mandates.index')
            ->with('success', 'Mandate deleted.');
    }

    public function forAgent(Request $request, string $agentIri): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $result = $this->service->getForAgent($agentIri, $limit, $offset);

        return view('activity-manage::mandates.for-agent', [
            'items' => $result['items'],
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
            'agentIri' => $agentIri,
        ]);
    }
}
