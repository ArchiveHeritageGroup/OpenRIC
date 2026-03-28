<?php

declare(strict_types=1);

namespace OpenRiC\Core\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\Core\Contracts\RelationshipServiceInterface;

class RelationshipController extends Controller
{
    public function __construct(
        private readonly RelationshipServiceInterface $relationshipService,
    ) {}

    public function index(Request $request): View
    {
        $iri = $request->get('iri', '');
        $relationships = [];

        if ($iri !== '') {
            $relationships = $this->relationshipService->getRelationships($iri);
        }

        return view('openric-core::relationships.index', [
            'iri' => $iri,
            'relationships' => $relationships,
            'predicates' => $this->relationshipService->getAvailablePredicates(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_iri' => 'required|string',
            'predicate' => 'required|string',
            'object_iri' => 'required|string',
        ]);

        $user = Auth::user();

        $this->relationshipService->createRelationship(
            $data['subject_iri'],
            $data['predicate'],
            $data['object_iri'],
            $user->getIri(),
            'Relationship created via OpenRiC UI'
        );

        return redirect()
            ->route('relationships.index', ['iri' => $data['subject_iri']])
            ->with('success', 'Relationship created.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_iri' => 'required|string',
            'predicate' => 'required|string',
            'object_iri' => 'required|string',
        ]);

        $user = Auth::user();

        $this->relationshipService->deleteRelationship(
            $data['subject_iri'],
            $data['predicate'],
            $data['object_iri'],
            $user->getIri(),
            'Relationship deleted via OpenRiC UI'
        );

        return redirect()
            ->route('relationships.index', ['iri' => $data['subject_iri']])
            ->with('success', 'Relationship removed.');
    }
}
