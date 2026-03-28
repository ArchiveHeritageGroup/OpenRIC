<?php

declare(strict_types=1);

namespace OpenRiC\Rights\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Rights\Contracts\RightsServiceInterface;

/**
 * RightsController — admin UI for rights statements, embargoes, TK Labels.
 *
 * Adapted from Heratio ahg-extended-rights RightsController + RightsAdminController (540 lines).
 */
class RightsController extends Controller
{
    protected RightsServiceInterface $service;

    public function __construct(RightsServiceInterface $service)
    {
        $this->service = $service;
    }

    // =========================================================================
    // RIGHTS STATEMENTS
    // =========================================================================

    public function index(Request $request): View
    {
        $entityIri = $request->input('entity_iri', '');
        $stats = $this->service->getRightsStats();
        $statements = $entityIri ? $this->service->getRightsForEntity($entityIri) : [];
        $embargoes = $entityIri ? $this->service->getEmbargoes($entityIri) : [];
        $tkLabels = $entityIri ? $this->service->getTkLabels($entityIri) : [];
        $expiringEmbargoes = $this->service->getExpiringEmbargoes(30);

        return view('rights::index', compact(
            'entityIri', 'stats', 'statements', 'embargoes', 'tkLabels', 'expiringEmbargoes'
        ));
    }

    public function show(int $id): View
    {
        $statement = $this->service->getRightsStatement($id);
        if (!$statement) {
            abort(404, 'Rights statement not found.');
        }

        return view('rights::show', compact('statement'));
    }

    public function create(Request $request): View
    {
        $entityIri = $request->input('entity_iri', '');

        return view('rights::create', compact('entityIri'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'entity_iri'   => 'required|string|max:2048',
            'rights_basis' => 'required|string|in:copyright,license,statute,other',
            'terms'        => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        $this->service->createRightsStatement($request->only([
            'entity_iri', 'rights_basis', 'rights_holder_name', 'rights_holder_iri',
            'start_date', 'end_date', 'documentation_iri', 'terms', 'notes',
        ]));

        return redirect()
            ->route('rights.index', ['entity_iri' => $request->input('entity_iri')])
            ->with('success', 'Rights statement created.');
    }

    public function edit(int $id): View
    {
        $statement = $this->service->getRightsStatement($id);
        if (!$statement) {
            abort(404);
        }

        return view('rights::edit', compact('statement'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'rights_basis' => 'required|string|in:copyright,license,statute,other',
        ]);

        $this->service->updateRightsStatement($id, $request->only([
            'rights_basis', 'rights_holder_name', 'rights_holder_iri',
            'start_date', 'end_date', 'documentation_iri', 'terms', 'notes',
        ]));

        $statement = $this->service->getRightsStatement($id);

        return redirect()
            ->route('rights.index', ['entity_iri' => $statement->entity_iri ?? ''])
            ->with('success', 'Rights statement updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $statement = $this->service->getRightsStatement($id);
        $entityIri = $statement->entity_iri ?? '';

        $this->service->deleteRightsStatement($id);

        return redirect()
            ->route('rights.index', ['entity_iri' => $entityIri])
            ->with('success', 'Rights statement deleted.');
    }

    // =========================================================================
    // EMBARGOES
    // =========================================================================

    public function embargoes(Request $request): View
    {
        $entityIri = $request->input('entity_iri', '');
        $embargoes = $entityIri ? $this->service->getEmbargoes($entityIri) : [];
        $expiring = $this->service->getExpiringEmbargoes(30);

        return view('rights::embargoes', compact('entityIri', 'embargoes', 'expiring'));
    }

    public function createEmbargo(Request $request): RedirectResponse
    {
        $request->validate([
            'entity_iri'    => 'required|string|max:2048',
            'embargo_start' => 'required|date',
            'reason'        => 'nullable|string',
        ]);

        $this->service->createEmbargo($request->only([
            'entity_iri', 'reason', 'embargo_start', 'embargo_end',
        ]));

        return redirect()
            ->route('rights.embargoes', ['entity_iri' => $request->input('entity_iri')])
            ->with('success', 'Embargo created.');
    }

    public function liftEmbargoAction(int $id): RedirectResponse
    {
        $this->service->liftEmbargo($id, (int) auth()->id(), 'Manually lifted by administrator');

        return redirect()->back()->with('success', 'Embargo lifted.');
    }

    // =========================================================================
    // TK LABELS
    // =========================================================================

    public function tkLabels(Request $request): View
    {
        $entityIri = $request->input('entity_iri', '');
        $labels = $entityIri ? $this->service->getTkLabels($entityIri) : [];

        return view('rights::tk-labels', compact('entityIri', 'labels'));
    }

    public function assignTkLabel(Request $request): RedirectResponse
    {
        $request->validate([
            'entity_iri' => 'required|string|max:2048',
            'label_type' => 'required|string|max:100',
        ]);

        $this->service->assignTkLabel($request->only([
            'entity_iri', 'label_type', 'label_iri',
        ]));

        return redirect()
            ->route('rights.tk-labels', ['entity_iri' => $request->input('entity_iri')])
            ->with('success', 'TK Label assigned.');
    }

    public function removeTkLabelAction(int $id): RedirectResponse
    {
        $this->service->removeTkLabel($id);

        return redirect()->back()->with('success', 'TK Label removed.');
    }
}
