<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\AiGovernance\Contracts\AiEvaluationServiceInterface;

/**
 * AI Evaluation Dashboard — Module 4 + Module 7 (Multilingual AI Control).
 *
 * Computes, stores, and exports AI performance metrics.
 */
class AiEvaluationService implements AiEvaluationServiceInterface
{
    public function computeMetrics(string $useCase, string $periodStart, string $periodEnd): array
    {
        $query = DB::table('ai_output_log')
            ->where('output_type', $useCase)
            ->whereBetween('created_at', [$periodStart, $periodEnd . ' 23:59:59']);

        $total = (clone $query)->count();
        $approved = (clone $query)->where('status', 'approved')->count();
        $rejected = (clone $query)->where('status', 'rejected')->count();
        $pending = (clone $query)->where('status', 'pending_review')->count();

        $acceptanceRate = $total > 0 ? round(($approved / $total) * 100, 2) : null;

        $avgEditDistance = (clone $query)
            ->where('status', 'approved')
            ->whereNotNull('edit_distance')
            ->avg('edit_distance');

        $avgConfidence = (clone $query)->avg('confidence_score');

        // Sensitivity metrics (only for sensitivity_flag type)
        $sensitivityPrecision = null;
        $sensitivityRecall = null;
        $sensitivityTp = null;
        $sensitivityFp = null;
        if ($useCase === 'sensitivity_flag') {
            $sensitivityTp = (clone $query)->where('status', 'approved')->count();
            $sensitivityFp = (clone $query)->where('status', 'rejected')->count();
            $totalFlags = $sensitivityTp + $sensitivityFp;
            $sensitivityPrecision = $totalFlags > 0 ? round($sensitivityTp / $totalFlags, 4) : null;
            // Recall requires knowing total actual sensitive items — use approved as proxy
            $sensitivityRecall = null; // Cannot compute without ground truth count
        }

        // RAG retrieval precision
        $ragPrecision = null;
        if ($useCase === 'rag_response') {
            $ragOutputs = (clone $query)
                ->where('status', 'approved')
                ->whereNotNull('retrieved_records')
                ->get();
            if ($ragOutputs->count() > 0) {
                $totalRetrieved = $ragOutputs->sum('retrieved_record_count');
                // Use approved outputs as proxy for relevant retrievals
                $ragPrecision = $totalRetrieved > 0 ? round($ragOutputs->count() / $totalRetrieved, 4) : null;
            }
        }

        // User satisfaction
        $satisfactionData = DB::table('ai_satisfaction_ratings')
            ->join('ai_output_log', 'ai_satisfaction_ratings.ai_output_id', '=', 'ai_output_log.id')
            ->where('ai_output_log.output_type', $useCase)
            ->whereBetween('ai_output_log.created_at', [$periodStart, $periodEnd . ' 23:59:59']);
        $satisfactionAvg = (clone $satisfactionData)->avg('ai_satisfaction_ratings.rating');
        $satisfactionCount = (clone $satisfactionData)->count();

        // Traceability — % of outputs with retrieved_records populated
        $withSources = (clone $query)->whereNotNull('retrieved_records')
            ->whereRaw("jsonb_array_length(COALESCE(retrieved_records, '[]'::jsonb)) > 0")
            ->count();
        $traceabilityScore = $total > 0 ? round($withSources / $total, 4) : null;

        // Staff time saved estimate (30 min per manual description, 2 min per review)
        $staffTimeSaved = $approved * (30 * 60 - 2 * 60); // seconds

        // Per-model breakdown
        $modelBreakdown = (clone $query)
            ->select('model_name', DB::raw('COUNT(*) as total'), DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved"))
            ->groupBy('model_name')
            ->get()
            ->toArray();

        return [
            'use_case' => $useCase,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_outputs' => $total,
            'approved_outputs' => $approved,
            'rejected_outputs' => $rejected,
            'pending_outputs' => $pending,
            'acceptance_rate' => $acceptanceRate,
            'average_edit_distance' => $avgEditDistance ? round((float) $avgEditDistance, 2) : null,
            'average_confidence' => $avgConfidence ? round((float) $avgConfidence, 4) : null,
            'staff_time_saved_seconds' => $staffTimeSaved,
            'sensitivity_true_positives' => $sensitivityTp,
            'sensitivity_false_positives' => $sensitivityFp,
            'sensitivity_precision' => $sensitivityPrecision,
            'sensitivity_recall' => $sensitivityRecall,
            'rag_retrieval_precision' => $ragPrecision,
            'user_satisfaction_avg' => $satisfactionAvg ? round((float) $satisfactionAvg, 2) : null,
            'user_satisfaction_count' => $satisfactionCount,
            'traceability_score' => $traceabilityScore,
            'model_breakdown' => $modelBreakdown,
        ];
    }

    public function saveMetrics(array $data): int
    {
        $data['model_breakdown'] = isset($data['model_breakdown']) ? json_encode($data['model_breakdown']) : null;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return (int) DB::table('ai_evaluation_metrics')->insertGetId($data);
    }

    public function listMetrics(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = DB::table('ai_evaluation_metrics');

        if (!empty($filters['use_case'])) {
            $query->where('use_case', $filters['use_case']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('period_start', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('period_end', '<=', $filters['date_to']);
        }

        $total = $query->count();
        $results = $query->orderByDesc('period_end')->offset($offset)->limit($limit)->get();

        return ['results' => $results, 'total' => $total];
    }

    public function getLatestMetrics(): array
    {
        $useCases = DB::table('ai_evaluation_metrics')
            ->select('use_case')
            ->distinct()
            ->pluck('use_case');

        $latest = [];
        foreach ($useCases as $uc) {
            $metric = DB::table('ai_evaluation_metrics')
                ->where('use_case', $uc)
                ->orderByDesc('period_end')
                ->first();
            if ($metric) {
                $metric->model_breakdown = json_decode($metric->model_breakdown ?? '[]', true);
                $latest[$uc] = $metric;
            }
        }

        return $latest;
    }

    public function getMetricsTrend(string $useCase, int $months = 12): array
    {
        return DB::table('ai_evaluation_metrics')
            ->where('use_case', $useCase)
            ->where('period_start', '>=', now()->subMonths($months)->toDateString())
            ->orderBy('period_start')
            ->get()
            ->map(function ($row) {
                $row->model_breakdown = json_decode($row->model_breakdown ?? '[]', true);
                return $row;
            })
            ->toArray();
    }

    public function getDashboardSummary(): array
    {
        $totalOutputs = DB::table('ai_output_log')->count();
        $pendingReviews = DB::table('ai_output_log')->where('status', 'pending_review')->count();
        $approvedToday = DB::table('ai_output_log')
            ->where('status', 'approved')
            ->where('reviewed_at', '>=', now()->startOfDay())
            ->count();

        $overallAcceptance = $totalOutputs > 0
            ? round(DB::table('ai_output_log')->where('status', 'approved')->count() / $totalOutputs * 100, 1)
            : 0;

        $avgConfidence = DB::table('ai_output_log')->avg('confidence_score');
        $avgSatisfaction = DB::table('ai_satisfaction_ratings')->avg('rating');

        $byOutputType = DB::table('ai_output_log')
            ->select('output_type', DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected"),
                DB::raw("SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END) as pending"))
            ->groupBy('output_type')
            ->get()
            ->toArray();

        $byModel = DB::table('ai_output_log')
            ->select('model_name', DB::raw('COUNT(*) as total'))
            ->groupBy('model_name')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        $recentActivity = DB::table('ai_output_log')
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM-DD') as date, COUNT(*) as count")
            ->where('created_at', '>=', now()->subDays(30))
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM-DD')")
            ->orderBy('date')
            ->get()
            ->toArray();

        return [
            'total_outputs' => $totalOutputs,
            'pending_reviews' => $pendingReviews,
            'approved_today' => $approvedToday,
            'overall_acceptance_rate' => $overallAcceptance,
            'avg_confidence' => $avgConfidence ? round((float) $avgConfidence, 3) : null,
            'avg_satisfaction' => $avgSatisfaction ? round((float) $avgSatisfaction, 2) : null,
            'by_output_type' => $byOutputType,
            'by_model' => $byModel,
            'recent_activity' => $recentActivity,
        ];
    }

    public function exportMetricsCsv(array $filters = []): string
    {
        $data = $this->listMetrics($filters, 10000, 0);
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['Use Case', 'Period Start', 'Period End', 'Total', 'Approved', 'Rejected', 'Acceptance Rate', 'Avg Edit Distance', 'Avg Confidence', 'Satisfaction', 'Traceability']);

        foreach ($data['results'] as $row) {
            fputcsv($output, [
                $row->use_case, $row->period_start, $row->period_end,
                $row->total_outputs, $row->approved_outputs, $row->rejected_outputs,
                $row->acceptance_rate, $row->average_edit_distance, $row->average_confidence,
                $row->user_satisfaction_avg, $row->traceability_score,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public function getModelPerformance(?string $useCase = null): array
    {
        $query = DB::table('ai_output_log');
        if ($useCase) {
            $query->where('output_type', $useCase);
        }

        return $query
            ->select(
                'model_name',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected"),
                DB::raw('AVG(confidence_score) as avg_confidence'),
                DB::raw("AVG(CASE WHEN status = 'approved' THEN edit_distance END) as avg_edit_distance"),
                DB::raw('AVG(processing_time_ms) as avg_processing_ms')
            )
            ->groupBy('model_name')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    // ── Module 7: Multilingual AI Control ───────────────────────────

    public function listLanguageConfigs(): array
    {
        return DB::table('ai_language_config')
            ->orderBy('language_name')
            ->get()
            ->map(function ($row) {
                $row->reviewer_user_ids = json_decode($row->reviewer_user_ids ?? '[]', true);
                return $row;
            })
            ->toArray();
    }

    public function getLanguageConfig(string $languageCode): ?object
    {
        $config = DB::table('ai_language_config')->where('language_code', $languageCode)->first();
        if ($config) {
            $config->reviewer_user_ids = json_decode($config->reviewer_user_ids ?? '[]', true);
        }
        return $config;
    }

    public function saveLanguageConfig(string $languageCode, array $data): void
    {
        $data['reviewer_user_ids'] = isset($data['reviewer_user_ids']) ? json_encode($data['reviewer_user_ids']) : '[]';
        $data['updated_at'] = now();

        $existing = DB::table('ai_language_config')->where('language_code', $languageCode)->first();
        if ($existing) {
            DB::table('ai_language_config')->where('language_code', $languageCode)->update($data);
        } else {
            $data['language_code'] = $languageCode;
            $data['created_at'] = now();
            DB::table('ai_language_config')->insert($data);
        }
    }

    public function deleteLanguageConfig(string $languageCode): void
    {
        DB::table('ai_language_config')->where('language_code', $languageCode)->delete();
    }

    public function getReviewersByLanguage(string $languageCode): array
    {
        $config = $this->getLanguageConfig($languageCode);
        if (!$config || empty($config->reviewer_user_ids)) {
            return [];
        }

        return DB::table('users')
            ->whereIn('id', $config->reviewer_user_ids)
            ->select('id', 'name', 'email')
            ->get()
            ->toArray();
    }

    public function assignReviewer(string $languageCode, int $userId): void
    {
        $config = $this->getLanguageConfig($languageCode);
        $reviewers = $config ? $config->reviewer_user_ids : [];

        if (!in_array($userId, $reviewers)) {
            $reviewers[] = $userId;
            $this->saveLanguageConfig($languageCode, ['reviewer_user_ids' => $reviewers]);
        }
    }

    public function removeReviewer(string $languageCode, int $userId): void
    {
        $config = $this->getLanguageConfig($languageCode);
        if (!$config) {
            return;
        }

        $reviewers = array_values(array_filter($config->reviewer_user_ids, fn ($id) => $id !== $userId));
        $this->saveLanguageConfig($languageCode, ['reviewer_user_ids' => $reviewers]);
    }
}
