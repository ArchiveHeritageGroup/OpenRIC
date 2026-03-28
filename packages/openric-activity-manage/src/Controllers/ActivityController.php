<?php

declare(strict_types=1);

namespace OpenRiC\ActivityManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\ActivityManage\Contracts\ActivityServiceInterface;

class ActivityController extends Controller
{
    public function __construct(
        private readonly ActivityServiceInterface $service,
    ) {}

    public function index(Request $request): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $filters = $request->only(['q', 'activity_type', 'agent_iri', 'record_iri']);
        $result = $this->service->browse($filters, $limit, $offset);

        return view('activity-manage::activities.index', [
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

        return view('activity-manage::activities.show', [
            'entity' => $entity,
            'viewMode' => session('openric_view_mode', config('openric.default_view', 'ric')),
        ]);
    }

    public function create(): View
    {
        return view('activity-manage::activities.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'            => 'required|string|max:1000',
            'identifier'       => 'nullable|string|max:255',
            'activity_type'    => 'nullable|string|max:2048',
            'date_iri'         => 'nullable|string|max:2048',
            'beginning_date'   => 'nullable|string|max:255',
            'end_date'         => 'nullable|string|max:255',
            'descriptive_note' => 'nullable|string|max:5000',
            'participant_iris'   => 'nullable|array',
            'participant_iris.*' => 'string|max:2048',
            'record_iris'        => 'nullable|array',
            'record_iris.*'      => 'string|max:2048',
        ]);

        $user = Auth::user();
        $iri = $this->service->create($data, $user->getIri(), 'Created via OpenRiC UI');

        return redirect()
            ->route('activities.show', ['iri' => urlencode($iri)])
            ->with('success', 'Activity created.');
    }

    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        return view('activity-manage::activities.edit', ['entity' => $entity]);
    }

    public function update(Request $request, string $iri): RedirectResponse
    {
        $data = $request->validate([
            'title'            => 'required|string|max:1000',
            'identifier'       => 'nullable|string|max:255',
            'activity_type'    => 'nullable|string|max:2048',
            'date_iri'         => 'nullable|string|max:2048',
            'beginning_date'   => 'nullable|string|max:255',
            'end_date'         => 'nullable|string|max:255',
            'descriptive_note' => 'nullable|string|max:5000',
            'participant_iris'   => 'nullable|array',
            'participant_iris.*' => 'string|max:2048',
            'record_iris'        => 'nullable|array',
            'record_iris.*'      => 'string|max:2048',
        ]);

        $user = Auth::user();
        $this->service->update($iri, $data, $user->getIri(), 'Updated via OpenRiC UI');

        return redirect()
            ->route('activities.show', ['iri' => urlencode($iri)])
            ->with('success', 'Activity updated.');
    }

    public function destroy(string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->delete($iri, $user->getIri(), 'Deleted via OpenRiC UI');

        return redirect()
            ->route('activities.index')
            ->with('success', 'Activity deleted.');
    }

    public function forAgent(Request $request, string $agentIri): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $result = $this->service->getForAgent($agentIri, $limit, $offset);

        return view('activity-manage::activities.for-agent', [
            'items' => $result['items'],
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
            'agentIri' => $agentIri,
        ]);
    }

    public function forRecord(Request $request, string $recordIri): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $result = $this->service->getForRecord($recordIri, $limit, $offset);

        return view('activity-manage::activities.for-record', [
            'items' => $result['items'],
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
            'recordIri' => $recordIri,
        ]);
    }
}
