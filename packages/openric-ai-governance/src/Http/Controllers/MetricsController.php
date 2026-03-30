<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\{Request, RedirectResponse, View};
use OpenRiC\AiGovernance\Services\AiGovernanceService;

class MetricsController extends Controller
{
    public function __construct(
        private readonly AiGovernanceService $service
    ) {}

    public function index(Request $request): View
    {
        $metricTypes = [
            'acceptance_rate' => 'Description Acceptance Rate',
            'edit_distance' => 'Edit Distance (AI draft vs approved)',
            'precision_recall' => 'Sensitivity Flag Precision/Recall',
            'rag_precision' => 'RAG Retrieval Precision',
            'trust_score' => 'User Trust Score',
            'description_quality' => 'Description Quality Score',
            'translation_accuracy' => 'Translation Accuracy',
        ];

        $metrics = \DB::table('ai_evaluation_metrics')
            ->selectRaw('metric_type, COUNT(*) as count, AVG(metric_value) as avg_value, MAX(period_end) as latest')
            ->groupBy('metric_type')
            ->get();

        return view('ai-governance::metrics.index', [
            'metrics' => $metrics,
            'metricTypes' => $metricTypes,
        ]);
    }

    public function create(): View
    {
        $metricTypes = [
            'acceptance_rate' => 'Description Acceptance Rate',
            'edit_distance' => 'Edit Distance (AI draft vs approved)',
            'precision_recall' => 'Sensitivity Flag Precision/Recall',
            'rag_precision' => 'RAG Retrieval Precision',
            'trust_score' => 'User Trust Score',
            'description_quality' => 'Description Quality Score',
            'translation_accuracy' => 'Translation Accuracy',
        ];

        return view('ai-governance::metrics.create', [
            'metricTypes' => $metricTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'metric_type' => 'required|in:acceptance_rate,edit_distance,precision_recall,rag_precision,trust_score,description_quality,translation_accuracy',
            'metric_value' => 'required|numeric|min:0|max:100',
            'sample_size' => 'nullable|integer|min:0',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'project_name' => 'nullable|string|max:200',
            'notes' => 'nullable|string',
        ]);

        $this->service->recordMetric($validated);

        return redirect()->route('ai-governance.metrics.index')
            ->with('success', 'Metric recorded successfully.');
    }

    public function show(string $metricType): View
    {
        $metrics = \DB::table('ai_evaluation_metrics')
            ->where('metric_type', $metricType)
            ->orderBy('period_start')
            ->get();

        return view('ai-governance::metrics.show', [
            'metricType' => $metricType,
            'metrics' => $metrics,
        ]);
    }
}
