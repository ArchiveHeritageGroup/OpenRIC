<?php

declare(strict_types=1);

namespace OpenRiC\AgentManage\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\AgentManage\Contracts\CorporateBodyServiceInterface;
use OpenRiC\Core\Contracts\StandardsMappingServiceInterface;

/**
 * Corporate body controller — adapted from Heratio ActorController (1,461 lines).
 * Same action set as PersonController, different RDF type and view namespace.
 */
class CorporateBodyController extends Controller
{
    public function __construct(
        private readonly CorporateBodyServiceInterface $service,
        private readonly StandardsMappingServiceInterface $mappingService,
    ) {}

    public function index(Request $request): View
    {
        $page = max(1, (int) $request->input('page', 1));
        $limit = (int) $request->input('limit', 25);
        $offset = ($page - 1) * $limit;
        $filters = $request->only(['q', 'sort', 'direction']);
        $result = $this->service->browse($filters, $limit, $offset);
        $totalPages = $limit > 0 ? (int) ceil($result['total'] / $limit) : 1;

        return view('agent-manage::corporate-bodies.index', [
            'items' => $result['items'], 'total' => $result['total'],
            'page' => $page, 'limit' => $limit, 'totalPages' => $totalPages, 'filters' => $filters,
        ]);
    }

    public function show(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) { abort(404, 'Corporate body not found'); }
        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));

        if ($viewMode === 'traditional') {
            $isaar = $this->mappingService->renderIsaarCpf($entity['properties'] ?? []);
            return view('agent-manage::corporate-bodies.show-traditional', [
                'entity' => $entity, 'isaar' => $isaar, 'viewMode' => $viewMode,
                'otherNames' => $entity['other_names'] ?? [], 'relatedAgents' => $entity['related_agents'] ?? [],
                'relatedRecords' => $entity['related_records'] ?? [], 'relatedFunctions' => $entity['related_functions'] ?? [],
                'occupations' => $entity['occupations'] ?? [],
            ]);
        }

        return view('agent-manage::corporate-bodies.show', [
            'entity' => $entity, 'viewMode' => $viewMode,
            'otherNames' => $entity['other_names'] ?? [], 'relatedAgents' => $entity['related_agents'] ?? [],
            'relatedRecords' => $entity['related_records'] ?? [], 'relatedFunctions' => $entity['related_functions'] ?? [],
            'occupations' => $entity['occupations'] ?? [],
        ]);
    }

    public function create(): View
    {
        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));
        return $viewMode === 'traditional'
            ? view('agent-manage::corporate-bodies.create-traditional')
            : view('agent-manage::corporate-bodies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->validationRules());
        $user = Auth::user();
        if ($request->input('_form_type') === 'isaar_cpf') {
            $data = array_merge($data, ['_rico_properties' => $this->mappingService->isaarCpfToRico($request->all())]);
        }
        $iri = $this->service->create($data, $user->getIri(), 'Created via OpenRiC UI');
        return redirect()->route('corporate-bodies.show', ['iri' => urlencode($iri)])->with('success', 'Corporate body created.');
    }

    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) { abort(404); }
        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));
        if ($viewMode === 'traditional') {
            $isaar = $this->mappingService->renderIsaarCpf($entity['properties'] ?? []);
            return view('agent-manage::corporate-bodies.edit-traditional', ['entity' => $entity, 'isaar' => $isaar]);
        }
        return view('agent-manage::corporate-bodies.edit', ['entity' => $entity]);
    }

    public function update(Request $request, string $iri): RedirectResponse
    {
        $data = $request->validate($this->validationRules());
        $user = Auth::user();
        if ($request->input('_form_type') === 'isaar_cpf') {
            $data = array_merge($data, ['_rico_properties' => $this->mappingService->isaarCpfToRico($request->all())]);
        }
        $this->service->update($iri, $data, $user->getIri(), 'Updated via OpenRiC UI');
        return redirect()->route('corporate-bodies.show', ['iri' => urlencode($iri)])->with('success', 'Corporate body updated.');
    }

    public function destroy(string $iri): RedirectResponse
    {
        Auth::user() && $this->service->delete($iri, Auth::user()->getIri(), 'Deleted via OpenRiC UI');
        return redirect()->route('corporate-bodies.index')->with('success', 'Corporate body deleted.');
    }

    public function relationships(string $iri, Request $request): View|JsonResponse
    {
        $entity = $this->service->find($iri);
        if ($entity === null) { abort(404); }
        $data = [
            'entity' => $entity, 'relatedAgents' => $entity['related_agents'] ?? [],
            'relatedRecords' => $entity['related_records'] ?? [], 'relatedFunctions' => $entity['related_functions'] ?? [],
        ];
        return $request->wantsJson() ? response()->json($data) : view('agent-manage::corporate-bodies.relationships', $data);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        return response()->json(strlen($query) < 2 ? [] : $this->service->autocomplete($query, 10));
    }

    public function print(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) { abort(404); }
        $isaar = $this->mappingService->renderIsaarCpf($entity['properties'] ?? []);
        return view('agent-manage::corporate-bodies.print', [
            'entity' => $entity, 'isaar' => $isaar,
            'otherNames' => $entity['other_names'] ?? [], 'relatedAgents' => $entity['related_agents'] ?? [],
        ]);
    }

    private function validationRules(): array
    {
        return [
            'authorized_form_of_name' => 'required|string|max:1000', 'identifier' => 'nullable|string|max:255',
            'corporate_body_identifiers' => 'nullable|string|max:500', 'dates_of_existence' => 'nullable|string|max:500',
            'date_of_establishment' => 'nullable|date', 'date_of_termination' => 'nullable|date',
            'history' => 'nullable|string|max:65535', 'places' => 'nullable|string|max:65535',
            'legal_status' => 'nullable|string|max:65535', 'functions' => 'nullable|string|max:65535',
            'mandates' => 'nullable|string|max:65535', 'internal_structures' => 'nullable|string|max:65535',
            'general_context' => 'nullable|string|max:65535', 'description_identifier' => 'nullable|string|max:255',
            'institution_responsible_identifier' => 'nullable|string|max:2048', 'rules' => 'nullable|string|max:65535',
            'sources' => 'nullable|string|max:65535', 'revision_history' => 'nullable|string|max:65535',
            'maintenance_notes' => 'nullable|string|max:65535', 'description_status' => 'nullable|string|max:100',
            'description_detail' => 'nullable|string|max:100', 'source_standard' => 'nullable|string|max:255',
        ];
    }
}
