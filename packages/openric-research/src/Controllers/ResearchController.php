<?php

declare(strict_types=1);

namespace OpenRiC\Research\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\Research\Contracts\ResearchServiceInterface;

/**
 * ResearchController — workspace, annotation, citation CRUD via web UI.
 *
 * Adapted from Heratio ahg-research ResearchController.
 */
class ResearchController extends Controller
{
    protected ResearchServiceInterface $service;

    public function __construct(ResearchServiceInterface $service)
    {
        $this->service = $service;
    }

    // =========================================================================
    // WORKSPACES
    // =========================================================================

    public function workspaces(): View
    {
        $userId = (int) Auth::id();
        $workspaces = $this->service->getWorkspaces($userId);
        $stats = $this->service->getDashboardStats($userId);

        return view('research::workspaces.index', compact('workspaces', 'stats'));
    }

    public function workspace(int $id): View|RedirectResponse
    {
        $workspace = $this->service->getWorkspace($id);

        if (!$workspace) {
            return redirect()->route('research.workspaces')->with('error', 'Workspace not found.');
        }

        if ((int) $workspace->user_id !== (int) Auth::id() && !$workspace->is_public) {
            abort(403, 'You do not have access to this workspace.');
        }

        $annotations = [];
        $citations = [];

        // Load annotations and citations for each item in the workspace
        foreach ($workspace->items as $item) {
            $annotations[$item->entity_iri] = $this->service->getAnnotationsForEntity(
                $item->entity_iri,
                (int) Auth::id()
            );
            $citations[$item->entity_iri] = $this->service->getCitations(
                $item->entity_iri,
                (int) Auth::id()
            );
        }

        return view('research::workspaces.show', compact('workspace', 'annotations', 'citations'));
    }

    public function createWorkspace(): View
    {
        return view('research::workspaces.create');
    }

    public function storeWorkspace(Request $request): RedirectResponse
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'is_public'   => 'nullable|boolean',
        ]);

        $workspaceId = $this->service->createWorkspace([
            'user_id'     => (int) Auth::id(),
            'name'        => $request->input('name'),
            'description' => $request->input('description'),
            'is_public'   => $request->boolean('is_public'),
        ]);

        return redirect()
            ->route('research.workspace', $workspaceId)
            ->with('success', 'Workspace created successfully.');
    }

    // =========================================================================
    // WORKSPACE ITEMS
    // =========================================================================

    public function addItem(Request $request, int $workspaceId): RedirectResponse
    {
        $request->validate([
            'entity_iri'  => 'required|string|max:2048',
            'entity_type' => 'required|string|max:100',
            'title'       => 'required|string|max:1024',
        ]);

        $workspace = $this->service->getWorkspace($workspaceId);
        if (!$workspace || (int) $workspace->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $this->service->addItemToWorkspace($workspaceId, [
            'entity_iri'  => $request->input('entity_iri'),
            'entity_type' => $request->input('entity_type'),
            'title'       => $request->input('title'),
        ]);

        return redirect()
            ->route('research.workspace', $workspaceId)
            ->with('success', 'Item added to workspace.');
    }

    public function removeItem(Request $request, int $workspaceId): RedirectResponse
    {
        $request->validate([
            'entity_iri' => 'required|string|max:2048',
        ]);

        $workspace = $this->service->getWorkspace($workspaceId);
        if (!$workspace || (int) $workspace->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $this->service->removeItemFromWorkspace($workspaceId, $request->input('entity_iri'));

        return redirect()
            ->route('research.workspace', $workspaceId)
            ->with('success', 'Item removed from workspace.');
    }

    // =========================================================================
    // ANNOTATIONS
    // =========================================================================

    public function annotations(string $entityIri): View
    {
        $userId = (int) Auth::id();
        $annotations = $this->service->getAnnotationsForEntity($entityIri, $userId);
        $assessments = $this->service->getAssessments($entityIri);

        return view('research::annotations.index', compact('entityIri', 'annotations', 'assessments'));
    }

    public function addAnnotation(Request $request): RedirectResponse
    {
        $request->validate([
            'entity_iri'      => 'required|string|max:2048',
            'annotation_type' => 'required|string|max:50',
            'content'         => 'required|string',
            'is_public'       => 'nullable|boolean',
        ]);

        $entityIri = $request->input('entity_iri');

        $this->service->createAnnotation([
            'user_id'         => (int) Auth::id(),
            'entity_iri'      => $entityIri,
            'annotation_type' => $request->input('annotation_type'),
            'content'         => $request->input('content'),
            'is_public'       => $request->boolean('is_public'),
        ]);

        return redirect()
            ->route('research.annotations', ['entityIri' => urlencode($entityIri)])
            ->with('success', 'Annotation added.');
    }

    // =========================================================================
    // CITATIONS
    // =========================================================================

    public function citations(string $entityIri): View
    {
        $userId = (int) Auth::id();
        $citations = $this->service->getCitations($entityIri, $userId);

        return view('research::citations.index', compact('entityIri', 'citations'));
    }

    public function addCitation(Request $request): RedirectResponse
    {
        $request->validate([
            'entity_iri'     => 'required|string|max:2048',
            'citation_style' => 'required|string|max:50',
            'citation_text'  => 'required|string|max:4000',
        ]);

        $entityIri = $request->input('entity_iri');

        $this->service->addCitation([
            'user_id'        => (int) Auth::id(),
            'entity_iri'     => $entityIri,
            'citation_style' => $request->input('citation_style'),
            'citation_text'  => $request->input('citation_text'),
        ]);

        return redirect()
            ->route('research.citations', ['entityIri' => urlencode($entityIri)])
            ->with('success', 'Citation added.');
    }
}
