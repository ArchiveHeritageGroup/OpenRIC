<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\{Request, RedirectResponse, View};
use Illuminate\Support\Facades\Auth;
use OpenRiC\AiGovernance\Services\AiGovernanceService;

class BiasController extends Controller
{
    public function __construct(
        private readonly AiGovernanceService $service
    ) {}

    public function index(Request $request): View
    {
        $query = \DB::table('bias_harm_register')
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
            ->orderByDesc('created_at');
        
        if ($request->get('resolved') === '1') {
            $query->where('resolved', true);
        } elseif ($request->get('resolved') === '0') {
            $query->where('resolved', false);
        }
        
        $records = $query->paginate(25);
        
        return view('ai-governance::bias.index', [
            'records' => $records,
            'resolved' => $request->get('resolved'),
        ]);
    }

    public function create(): View
    {
        return view('ai-governance::bias.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entity_iri' => 'nullable|string|max:500',
            'category' => 'required|in:harmful_language,culturally_sensitive,absent_communities,contested_description,power_imbalance,under_representation,metadata_gap',
            'description' => 'required|string',
            'ai_warning' => 'nullable|string',
            'mitigation_strategy' => 'nullable|string',
            'severity' => 'nullable|in:low,medium,high,critical',
        ]);

        $this->service->addBiasRecord($validated, Auth::id());

        return redirect()->route('ai-governance.bias.index')
            ->with('success', 'Bias/harm record added successfully.');
    }

    public function show(int $id): View
    {
        $record = \DB::table('bias_harm_register')->findOrFail($id);
        
        return view('ai-governance::bias.show', [
            'record' => $record,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'ai_warning' => 'nullable|string',
            'mitigation_strategy' => 'nullable|string',
            'severity' => 'nullable|in:low,medium,high,critical',
        ]);

        \DB::table('bias_harm_register')->where('id', $id)->update([
            'ai_warning' => $validated['ai_warning'] ?? null,
            'mitigation_strategy' => $validated['mitigation_strategy'] ?? null,
            'severity' => $validated['severity'] ?? 'medium',
            'updated_at' => now(),
        ]);

        return redirect()->route('ai-governance.bias.show', $id)
            ->with('success', 'Record updated successfully.');
    }

    public function resolve(int $id): RedirectResponse
    {
        $this->service->resolveBiasRecord($id, Auth::id());

        return redirect()->back()
            ->with('success', 'Record marked as resolved.');
    }
}
