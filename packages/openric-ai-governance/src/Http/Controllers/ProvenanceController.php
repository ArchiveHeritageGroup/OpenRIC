<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\{Request, RedirectResponse, View, JsonResponse};
use Illuminate\Support\Facades\Auth;
use OpenRiC\AiGovernance\Services\AiGovernanceService;

class ProvenanceController extends Controller
{
    public function __construct(
        private readonly AiGovernanceService $service
    ) {}

    public function index(Request $request): View
    {
        $outputs = \DB::table('ai_output_log')
            ->orderByDesc('created_at')
            ->paginate(25);
        
        return view('ai-governance::provenance.index', [
            'outputs' => $outputs,
        ]);
    }

    public function pending(): View
    {
        $outputs = $this->service->getPendingOutputs(50);
        
        return view('ai-governance::provenance.pending', [
            'outputs' => $outputs,
        ]);
    }

    public function show(int $id): View
    {
        $output = \DB::table('ai_output_log')->findOrFail($id);
        
        return view('ai-governance::provenance.show', [
            'output' => $output,
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        $this->service->approveOutput($id, Auth::id());
        
        return redirect()->back()
            ->with('success', 'AI output approved.');
    }

    public function byEntity(string $iri): View
    {
        $outputs = $this->service->getOutputHistory(urldecode($iri), 50);
        
        return view('ai-governance::provenance.by-entity', [
            'entityIri' => urldecode($iri),
            'outputs' => $outputs,
        ]);
    }
}
