<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\AiGovernance\Services\AiGovernanceService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AiGovernanceService $service
    ) {}

    /**
     * AI Governance Dashboard
     */
    public function index(): View
    {
        $stats = $this->service->getDashboardStats();
        
        $pendingOutputs = $this->service->getPendingOutputs(10);
        $unresolvedBias = $this->service->getUnresolvedBiasRecords(10);
        
        return view('ai-governance::dashboard.index', [
            'stats' => $stats,
            'pendingOutputs' => $pendingOutputs,
            'unresolvedBias' => $unresolvedBias,
        ]);
    }
}
