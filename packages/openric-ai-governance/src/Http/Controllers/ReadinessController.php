<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\{Request, RedirectResponse, View};
use Illuminate\Support\Facades\Auth;
use OpenRiC\AiGovernance\Services\AiGovernanceService;

class ReadinessController extends Controller
{
    public function __construct(
        private readonly AiGovernanceService $service
    ) {}

    public function index(Request $request): View
    {
        $profiles = \DB::table('ai_readiness_profiles')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('ai-governance::readiness.index', [
            'profiles' => $profiles,
        ]);
    }

    public function create(): View
    {
        return view('ai-governance::readiness.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'collection_iri' => 'required|string|max:500',
            'collection_title' => 'nullable|string|max:500',
            'digitization_completeness' => 'nullable|in:complete,partial,not_started',
            'known_gaps' => 'nullable|string',
            'excluded_records' => 'nullable|string',
            'legal_exclusions' => 'nullable|string',
            'privacy_exclusions' => 'nullable|string',
            'representational_bias_notes' => 'nullable|string',
            'corpus_completeness' => 'nullable|in:complete,partial,sampled,biased',
        ]);

        $this->service->saveReadinessProfile(
            $validated['collection_iri'],
            $validated,
            Auth::id()
        );

        return redirect()->route('ai-governance.readiness.index')
            ->with('success', 'AI readiness profile created.');
    }

    public function edit(int $id): View
    {
        $profile = \DB::table('ai_readiness_profiles')->findOrFail($id);

        return view('ai-governance::readiness.edit', [
            'profile' => $profile,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $profile = \DB::table('ai_readiness_profiles')->findOrFail($id);

        $validated = $request->validate([
            'collection_title' => 'nullable|string|max:500',
            'digitization_completeness' => 'nullable|in:complete,partial,not_started',
            'known_gaps' => 'nullable|string',
            'excluded_records' => 'nullable|string',
            'legal_exclusions' => 'nullable|string',
            'privacy_exclusions' => 'nullable|string',
            'representational_bias_notes' => 'nullable|string',
            'corpus_completeness' => 'nullable|in:complete,partial,sampled,biased',
        ]);

        $this->service->saveReadinessProfile(
            $profile->collection_iri,
            $validated,
            Auth::id()
        );

        return redirect()->route('ai-governance.readiness.index')
            ->with('success', 'Profile updated.');
    }

    public function byCollection(string $iri): View
    {
        $profile = $this->service->getReadinessProfile(urldecode($iri));

        return view('ai-governance::readiness.by-collection', [
            'collectionIri' => urldecode($iri),
            'profile' => $profile,
        ]);
    }

    // Project Readiness methods
    public function projects(): View
    {
        $projects = \DB::table('ai_project_readiness')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('ai-governance::readiness.projects', [
            'projects' => $projects,
        ]);
    }

    public function createProject(): View
    {
        return view('ai-governance::readiness.project-create');
    }

    public function storeProject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_name' => 'required|string|max:200|unique:ai_project_readiness,project_name',
            'use_case_description' => 'required|string',
        ]);

        $this->service->saveProjectReadiness($validated, Auth::id());

        return redirect()->route('ai-governance.projects.index')
            ->with('success', 'AI project created.');
    }

    public function showProject(int $id): View
    {
        $project = \DB::table('ai_project_readiness')->findOrFail($id);

        return view('ai-governance::readiness.project-show', [
            'project' => $project,
        ]);
    }

    public function updateProject(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'corpus_completeness_documented' => 'boolean',
            'metadata_minimum_met' => 'boolean',
            'access_rules_structured' => 'boolean',
            'derivatives_prepared' => 'boolean',
            'evaluation_plan_approved' => 'boolean',
            'human_review_workflow_active' => 'boolean',
        ]);

        $updateData = [];
        foreach (['corpus_completeness_documented', 'metadata_minimum_met', 'access_rules_structured', 
                  'derivatives_prepared', 'evaluation_plan_approved', 'human_review_workflow_active'] as $field) {
            $updateData[$field] = $validated[$field] ?? false;
        }
        $updateData['updated_at'] = now();

        \DB::table('ai_project_readiness')->where('id', $id)->update($updateData);

        return redirect()->route('ai-governance.projects.show', $id)
            ->with('success', 'Project updated.');
    }

    public function submitProject(int $id): RedirectResponse
    {
        $this->service->submitForApproval($id);

        return redirect()->route('ai-governance.projects.show', $id)
            ->with('success', 'Project submitted for approval.');
    }

    public function approveProject(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'approved' => 'required|boolean',
            'reason' => 'nullable|string',
        ]);

        $this->service->approveProject(
            $id,
            Auth::id(),
            $validated['approved'],
            $validated['reason'] ?? null
        );

        return redirect()->route('ai-governance.projects.show', $id)
            ->with('success', $validated['approved'] ? 'Project approved.' : 'Project rejected.');
    }
}
