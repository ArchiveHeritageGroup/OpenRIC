<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\{Request, RedirectResponse, View};
use OpenRiC\AiGovernance\Services\AiGovernanceService;

class DerivativesController extends Controller
{
    public function __construct(
        private readonly AiGovernanceService $service
    ) {}

    public function index(): View
    {
        $profiles = \DB::table('ai_derivative_profiles')
            ->where('active', true)
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('ai-governance::derivatives.index', [
            'profiles' => $profiles,
        ]);
    }

    public function create(): View
    {
        return view('ai-governance::derivatives.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'collection_iri' => 'required|string|max:500',
            'profile_name' => 'nullable|string|max:200',
            'cleaned_ocr_text' => 'boolean',
            'normalised_metadata_export' => 'boolean',
            'chunked_retrieval_units' => 'boolean',
            'redacted_access_copies' => 'boolean',
            'multilingual_alignment' => 'boolean',
            'formats' => 'nullable|string',
            'chunk_size' => 'nullable|integer|min:100|max:4096',
            'chunk_overlap' => 'nullable|integer|min:0|max:500',
            'description' => 'nullable|string',
        ]);

        $data = [
            'collection_iri' => $validated['collection_iri'],
            'profile_name' => $validated['profile_name'] ?? 'default',
            'cleaned_ocr_text' => $validated['cleaned_ocr_text'] ?? false,
            'normalised_metadata_export' => $validated['normalised_metadata_export'] ?? false,
            'chunked_retrieval_units' => $validated['chunked_retrieval_units'] ?? false,
            'redacted_access_copies' => $validated['redacted_access_copies'] ?? false,
            'multilingual_alignment' => $validated['multilingual_alignment'] ?? false,
            'formats' => $validated['formats'] ?? '["pdf", "txt", "json"]',
            'chunk_size' => $validated['chunk_size'] ?? 512,
            'chunk_overlap' => $validated['chunk_overlap'] ?? 50,
            'description' => $validated['description'] ?? null,
        ];

        $this->service->saveDerivativeProfile($validated['collection_iri'], $data);

        return redirect()->route('ai-governance.derivatives.index')
            ->with('success', 'Derivative profile created.');
    }

    public function edit(int $id): View
    {
        $profile = \DB::table('ai_derivative_profiles')->findOrFail($id);

        return view('ai-governance::derivatives.edit', [
            'profile' => $profile,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $profile = \DB::table('ai_derivative_profiles')->findOrFail($id);

        $validated = $request->validate([
            'cleaned_ocr_text' => 'boolean',
            'normalised_metadata_export' => 'boolean',
            'chunked_retrieval_units' => 'boolean',
            'redacted_access_copies' => 'boolean',
            'multilingual_alignment' => 'boolean',
            'formats' => 'nullable|string',
            'chunk_size' => 'nullable|integer|min:100|max:4096',
            'chunk_overlap' => 'nullable|integer|min:0|max:500',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $data = [
            'cleaned_ocr_text' => $validated['cleaned_ocr_text'] ?? false,
            'normalised_metadata_export' => $validated['normalised_metadata_export'] ?? false,
            'chunked_retrieval_units' => $validated['chunked_retrieval_units'] ?? false,
            'redacted_access_copies' => $validated['redacted_access_copies'] ?? false,
            'multilingual_alignment' => $validated['multilingual_alignment'] ?? false,
            'formats' => $validated['formats'] ?? '["pdf", "txt", "json"]',
            'chunk_size' => $validated['chunk_size'] ?? 512,
            'chunk_overlap' => $validated['chunk_overlap'] ?? 50,
            'description' => $validated['description'] ?? null,
            'active' => $validated['active'] ?? true,
        ];

        $this->service->saveDerivativeProfile($profile->collection_iri, $data);

        return redirect()->route('ai-governance.derivatives.index')
            ->with('success', 'Derivative profile updated.');
    }
}
