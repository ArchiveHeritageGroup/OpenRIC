<?php

declare(strict_types=1);

namespace OpenRiC\AgentManage\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\AgentManage\Contracts\PersonServiceInterface;
use OpenRiC\Core\Contracts\StandardsMappingServiceInterface;

/**
 * Person controller — adapted from Heratio ActorController (1,461 lines).
 *
 * Heratio actions mapped:
 *   1. index       — Browse persons with filters, sorting, pagination
 *   2. show        — View person detail (RiC or ISAAR(CPF) lens)
 *   3. create      — Create form
 *   4. store       — Store (POST) with all ISAAR(CPF) fields
 *   5. edit        — Edit form
 *   6. update      — Update (PUT) with all ISAAR(CPF) fields
 *   7. destroy     — Delete
 *   8. relationships — View related agents and records
 *   9. autocomplete — Search suggestions (JSON)
 *  10. print       — Printable view
 */
class PersonController extends Controller
{
    public function __construct(
        private readonly PersonServiceInterface $service,
        private readonly StandardsMappingServiceInterface $mappingService,
    ) {}

    /**
     * #1 — Browse persons with filters, sorting, pagination.
     */
    public function index(Request $request): View
    {
        $page = max(1, (int) $request->input('page', 1));
        $limit = (int) $request->input('limit', 25);
        $offset = ($page - 1) * $limit;

        $filters = $request->only(['q', 'sort', 'direction']);
        $result = $this->service->browse($filters, $limit, $offset);
        $totalPages = $limit > 0 ? (int) ceil($result['total'] / $limit) : 1;

        return view('agent-manage::persons.index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'filters' => $filters,
        ]);
    }

    /**
     * #2 — View person detail.
     */
    public function show(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404, 'Person not found');
        }

        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));

        if ($viewMode === 'traditional') {
            $isaar = $this->mappingService->renderIsaarCpf($entity['properties'] ?? []);

            return view('agent-manage::persons.show-traditional', [
                'entity' => $entity,
                'isaar' => $isaar,
                'viewMode' => $viewMode,
                'otherNames' => $entity['other_names'] ?? [],
                'relatedAgents' => $entity['related_agents'] ?? [],
                'relatedRecords' => $entity['related_records'] ?? [],
                'relatedFunctions' => $entity['related_functions'] ?? [],
                'occupations' => $entity['occupations'] ?? [],
            ]);
        }

        return view('agent-manage::persons.show', [
            'entity' => $entity,
            'viewMode' => $viewMode,
            'otherNames' => $entity['other_names'] ?? [],
            'relatedAgents' => $entity['related_agents'] ?? [],
            'relatedRecords' => $entity['related_records'] ?? [],
            'relatedFunctions' => $entity['related_functions'] ?? [],
            'occupations' => $entity['occupations'] ?? [],
        ]);
    }

    /**
     * #3 — Create form.
     */
    public function create(): View
    {
        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));

        if ($viewMode === 'traditional') {
            return view('agent-manage::persons.create-traditional');
        }

        return view('agent-manage::persons.create');
    }

    /**
     * #4 — Store (POST) with all ISAAR(CPF) fields.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->validationRules());
        $user = Auth::user();

        if ($request->input('_form_type') === 'isaar_cpf') {
            $data = array_merge($data, ['_rico_properties' => $this->mappingService->isaarCpfToRico($request->all())]);
        }

        $iri = $this->service->create($data, $user->getIri(), 'Created via OpenRiC UI');

        return redirect()->route('persons.show', ['iri' => urlencode($iri)])
            ->with('success', 'Person created.');
    }

    /**
     * #5 — Edit form.
     */
    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404, 'Person not found');
        }

        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));

        if ($viewMode === 'traditional') {
            $isaar = $this->mappingService->renderIsaarCpf($entity['properties'] ?? []);

            return view('agent-manage::persons.edit-traditional', [
                'entity' => $entity,
                'isaar' => $isaar,
            ]);
        }

        return view('agent-manage::persons.edit', ['entity' => $entity]);
    }

    /**
     * #6 — Update (PUT) with all ISAAR(CPF) fields.
     */
    public function update(Request $request, string $iri): RedirectResponse
    {
        $data = $request->validate($this->validationRules());
        $user = Auth::user();

        if ($request->input('_form_type') === 'isaar_cpf') {
            $data = array_merge($data, ['_rico_properties' => $this->mappingService->isaarCpfToRico($request->all())]);
        }

        $this->service->update($iri, $data, $user->getIri(), 'Updated via OpenRiC UI');

        return redirect()->route('persons.show', ['iri' => urlencode($iri)])
            ->with('success', 'Person updated.');
    }

    /**
     * #7 — Delete.
     */
    public function destroy(string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->delete($iri, $user->getIri(), 'Deleted via OpenRiC UI');

        return redirect()->route('persons.index')->with('success', 'Person deleted.');
    }

    /**
     * #8 — View relationships (AJAX or page).
     */
    public function relationships(string $iri, Request $request): View|JsonResponse
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404, 'Person not found');
        }

        $data = [
            'entity' => $entity,
            'relatedAgents' => $entity['related_agents'] ?? [],
            'relatedRecords' => $entity['related_records'] ?? [],
            'relatedFunctions' => $entity['related_functions'] ?? [],
            'occupations' => $entity['occupations'] ?? [],
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('agent-manage::persons.relationships', $data);
    }

    /**
     * #9 — Autocomplete (JSON).
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = $this->service->autocomplete($query, 10);

        return response()->json($results);
    }

    /**
     * #10 — Printable view.
     */
    public function print(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404, 'Person not found');
        }

        $isaar = $this->mappingService->renderIsaarCpf($entity['properties'] ?? []);

        return view('agent-manage::persons.print', [
            'entity' => $entity,
            'isaar' => $isaar,
            'otherNames' => $entity['other_names'] ?? [],
            'relatedAgents' => $entity['related_agents'] ?? [],
            'relatedRecords' => $entity['related_records'] ?? [],
        ]);
    }

    /**
     * ISAAR(CPF) validation rules — all 19 fields from Heratio.
     */
    private function validationRules(): array
    {
        return [
            // 5.1 Identity
            'authorized_form_of_name' => 'required|string|max:1000',
            'identifier' => 'nullable|string|max:255',
            'corporate_body_identifiers' => 'nullable|string|max:500',

            // 5.2 Description
            'dates_of_existence' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'date_of_death' => 'nullable|date',
            'history' => 'nullable|string|max:65535',
            'places' => 'nullable|string|max:65535',
            'legal_status' => 'nullable|string|max:65535',
            'functions' => 'nullable|string|max:65535',
            'mandates' => 'nullable|string|max:65535',
            'internal_structures' => 'nullable|string|max:65535',
            'general_context' => 'nullable|string|max:65535',

            // 5.4 Control
            'description_identifier' => 'nullable|string|max:255',
            'institution_responsible_identifier' => 'nullable|string|max:2048',
            'rules' => 'nullable|string|max:65535',
            'sources' => 'nullable|string|max:65535',
            'revision_history' => 'nullable|string|max:65535',
            'maintenance_notes' => 'nullable|string|max:65535',
            'description_status' => 'nullable|string|max:100',
            'description_detail' => 'nullable|string|max:100',
            'source_standard' => 'nullable|string|max:255',
        ];
    }
}
