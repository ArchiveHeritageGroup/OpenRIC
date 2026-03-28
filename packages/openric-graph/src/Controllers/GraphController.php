<?php

declare(strict_types=1);

namespace OpenRiC\Graph\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Graph\Contracts\GraphServiceInterface;

class GraphController extends Controller
{
    public function __construct(
        private readonly GraphServiceInterface $graphService,
    ) {}

    public function entity(string $iri): View
    {
        $graphData = $this->graphService->getEntityGraph($iri);

        return view('graph::graph.entity', [
            'iri' => $iri,
            'graphData' => $graphData,
        ]);
    }

    public function entityJson(string $iri): JsonResponse
    {
        $depth = (int) request()->get('depth', 1);
        $limit = (int) request()->get('limit', 50);

        return response()->json(
            $this->graphService->getEntityGraph($iri, $depth, $limit)
        );
    }

    public function overview(): View
    {
        $graphData = $this->graphService->getOverviewGraph();

        return view('graph::graph.overview', [
            'graphData' => $graphData,
        ]);
    }

    public function agentNetwork(): View
    {
        $graphData = $this->graphService->getAgentNetwork();

        return view('graph::graph.agent-network', [
            'graphData' => $graphData,
        ]);
    }

    public function timeline(Request $request): View
    {
        $timeline = $this->graphService->getTimeline(
            $request->only(['type', 'date_from', 'date_to']),
            (int) $request->get('limit', 200)
        );

        return view('graph::graph.timeline', [
            'timeline' => $timeline,
        ]);
    }
}
