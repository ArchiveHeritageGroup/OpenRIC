<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\{Request, RedirectResponse, View};
use Illuminate\Support\Facades\Auth;
use OpenRiC\AiGovernance\Services\AiGovernanceService;

class RightsController extends Controller
{
    public function __construct(
        private readonly AiGovernanceService $service
    ) {}

    /**
     * List all AI rights entries
     */
    public function index(Request $request): View
    {
        $query = \DB::table('ai_rights')->orderByDesc('created_at');
        
        if ($request->has('ai_allowed')) {
            $query->where('ai_allowed', $request->boolean('ai_allowed'));
        }
        
        $rights = $query->paginate(25);
        
        return view('ai-governance::rights.index', [
            'rights' => $rights,
        ]);
    }

    /**
     * Show create form
     */
    public function create(): View
    {
        return view('ai-governance::rights.create');
    }

    /**
     * Store new AI rights entry
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entity_iri' => 'required|string|max:500',
            'entity_type' => 'nullable|string|max:50',
            'ai_allowed' => 'boolean',
            'summarisation_allowed' => 'boolean',
            'embedding_allowed' => 'boolean',
            'training_reuse_allowed' => 'boolean',
            'redaction_required' => 'boolean',
            'ai_review_notes' => 'nullable|string',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $validated['ai_allowed'] = $validated['ai_allowed'] ?? false;
        $validated['summarisation_allowed'] = $validated['summarisation_allowed'] ?? false;
        $validated['embedding_allowed'] = $validated['embedding_allowed'] ?? false;
        $validated['training_reuse_allowed'] = $validated['training_reuse_allowed'] ?? false;
        $validated['redaction_required'] = $validated['redaction_required'] ?? false;

        $this->service->setRights(
            $validated['entity_iri'],
            $validated,
            Auth::id()
        );

        return redirect()->route('ai-governance.rights.index')
            ->with('success', 'AI rights entry created successfully.');
    }

    /**
     * Show rights entry
     */
    public function show(int $id): View
    {
        $rights = \DB::table('ai_rights')->findOrFail($id);
        
        return view('ai-governance::rights.show', [
            'rights' => $rights,
        ]);
    }

    /**
     * Update rights entry
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'ai_allowed' => 'boolean',
            'summarisation_allowed' => 'boolean',
            'embedding_allowed' => 'boolean',
            'training_reuse_allowed' => 'boolean',
            'redaction_required' => 'boolean',
            'ai_review_notes' => 'nullable|string',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
        ]);

        \DB::table('ai_rights')->where('id', $id)->update([
            'ai_allowed' => $validated['ai_allowed'] ?? false,
            'summarisation_allowed' => $validated['summarisation_allowed'] ?? false,
            'embedding_allowed' => $validated['embedding_allowed'] ?? false,
            'training_reuse_allowed' => $validated['training_reuse_allowed'] ?? false,
            'redaction_required' => $validated['redaction_required'] ?? false,
            'ai_review_notes' => $validated['ai_review_notes'] ?? null,
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'updated_at' => now(),
        ]);

        return redirect()->route('ai-governance.rights.show', $id)
            ->with('success', 'AI rights updated successfully.');
    }

    /**
     * Delete rights entry
     */
    public function destroy(int $id): RedirectResponse
    {
        \DB::table('ai_rights')->where('id', $id)->delete();
        
        return redirect()->route('ai-governance.rights.index')
            ->with('success', 'AI rights entry deleted.');
    }

    /**
     * Get rights by entity IRI
     */
    public function byEntity(string $iri): View
    {
        $rights = \DB::table('ai_rights')
            ->where('entity_iri', urldecode($iri))
            ->orderByDesc('created_at')
            ->get();
        
        return view('ai-governance::rights.by-entity', [
            'entityIri' => urldecode($iri),
            'rights' => $rights,
        ]);
    }
}
