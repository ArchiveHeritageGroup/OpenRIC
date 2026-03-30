<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\{Request, RedirectResponse, View};
use OpenRiC\AiGovernance\Services\AiGovernanceService;

class LanguagesController extends Controller
{
    public function __construct(
        private readonly AiGovernanceService $service
    ) {}

    public function index(): View
    {
        $languages = \DB::table('language_ai_settings')
            ->orderBy('language_name')
            ->get();

        return view('ai-governance::languages.index', [
            'languages' => $languages,
        ]);
    }

    public function create(): View
    {
        return view('ai-governance::languages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language_code' => 'required|string|max:10',
            'language_name' => 'required|string|max:100',
            'ai_allowed' => 'boolean',
            'translation_allowed' => 'boolean',
            'embedding_enabled' => 'boolean',
            'access_warning' => 'nullable|string',
            'reviewer_id' => 'nullable|integer|exists:users,id',
            'competency_required' => 'boolean',
            'competency_languages' => 'nullable|string',
        ]);

        $data = [
            'language_code' => $validated['language_code'],
            'language_name' => $validated['language_name'],
            'ai_allowed' => $validated['ai_allowed'] ?? true,
            'translation_allowed' => $validated['translation_allowed'] ?? true,
            'embedding_enabled' => $validated['embedding_enabled'] ?? true,
            'access_warning' => $validated['access_warning'] ?? null,
            'reviewer_id' => $validated['reviewer_id'] ?? null,
            'competency_required' => $validated['competency_required'] ?? false,
            'competency_languages' => $validated['competency_languages'] ?? null,
        ];

        $this->service->saveLanguageSettings($data);

        return redirect()->route('ai-governance.languages.index')
            ->with('success', 'Language settings saved.');
    }

    public function edit(string $code): View
    {
        $language = \DB::table('language_ai_settings')
            ->where('language_code', $code)
            ->firstOrFail();

        return view('ai-governance::languages.edit', [
            'language' => $language,
        ]);
    }

    public function update(Request $request, string $code): RedirectResponse
    {
        $validated = $request->validate([
            'language_name' => 'required|string|max:100',
            'ai_allowed' => 'boolean',
            'translation_allowed' => 'boolean',
            'embedding_enabled' => 'boolean',
            'access_warning' => 'nullable|string',
            'reviewer_id' => 'nullable|integer|exists:users,id',
            'competency_required' => 'boolean',
            'competency_languages' => 'nullable|string',
        ]);

        $data = array_merge($validated, [
            'ai_allowed' => $validated['ai_allowed'] ?? true,
            'translation_allowed' => $validated['translation_allowed'] ?? true,
            'embedding_enabled' => $validated['embedding_enabled'] ?? true,
            'competency_required' => $validated['competency_required'] ?? false,
        ]);

        $this->service->saveLanguageSettings($data);

        return redirect()->route('ai-governance.languages.index')
            ->with('success', 'Language settings updated.');
    }
}
