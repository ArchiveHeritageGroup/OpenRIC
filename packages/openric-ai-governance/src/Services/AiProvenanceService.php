<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\AiGovernance\Contracts\AiProvenanceServiceInterface;

/**
 * AI Output Provenance Log — Module 3.
 *
 * Tracks every AI-generated output with full provenance chain:
 * model, prompt, retrieved records, confidence, review status, edit distance.
 */
class AiProvenanceService implements AiProvenanceServiceInterface
{
    public function logOutput(array $data): int
    {
        $data['status'] = $data['status'] ?? 'pending_review';
        $data['retrieved_records'] = isset($data['retrieved_records']) ? json_encode($data['retrieved_records']) : null;
        $data['risk_flags'] = isset($data['risk_flags']) ? json_encode($data['risk_flags']) : null;
        $data['retrieved_record_count'] = isset($data['retrieved_records'])
            ? count(json_decode($data['retrieved_records'], true) ?? [])
            : 0;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return (int) DB::table('ai_output_log')->insertGetId($data);
    }

    public function getOutput(int $id): ?object
    {
        $output = DB::table('ai_output_log')->where('id', $id)->first();
        if ($output) {
            $output->retrieved_records = json_decode($output->retrieved_records ?? '[]', true);
            $output->risk_flags = json_decode($output->risk_flags ?? '[]', true);
            $output->ratings = $this->getOutputRatings($id);
        }
        return $output;
    }

    public function listOutputs(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = DB::table('ai_output_log');

        if (!empty($filters['entity_iri'])) {
            $query->where('entity_iri', $filters['entity_iri']);
        }
        if (!empty($filters['output_type'])) {
            $query->where('output_type', $filters['output_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['model_name'])) {
            $query->where('model_name', $filters['model_name']);
        }
        if (!empty($filters['reviewed_by'])) {
            $query->where('reviewed_by', (int) $filters['reviewed_by']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('raw_output', 'ILIKE', $like)
                  ->orWhere('approved_output', 'ILIKE', $like)
                  ->orWhere('pipeline_name', 'ILIKE', $like);
            });
        }
        if (!empty($filters['min_confidence'])) {
            $query->where('confidence_score', '>=', (float) $filters['min_confidence']);
        }
        if (!empty($filters['has_risk_flags'])) {
            $query->whereRaw("jsonb_array_length(COALESCE(risk_flags, '[]'::jsonb)) > 0");
        }

        $total = $query->count();
        $results = $query->orderByDesc('created_at')->offset($offset)->limit($limit)->get();

        foreach ($results as $row) {
            $row->retrieved_records = json_decode($row->retrieved_records ?? '[]', true);
            $row->risk_flags = json_decode($row->risk_flags ?? '[]', true);
        }

        return ['results' => $results, 'total' => $total];
    }

    public function getOutputsForEntity(string $entityIri, ?string $outputType = null): array
    {
        $query = DB::table('ai_output_log')->where('entity_iri', $entityIri);
        if ($outputType) {
            $query->where('output_type', $outputType);
        }

        return $query->orderByDesc('created_at')->get()->map(function ($row) {
            $row->retrieved_records = json_decode($row->retrieved_records ?? '[]', true);
            $row->risk_flags = json_decode($row->risk_flags ?? '[]', true);
            return $row;
        })->toArray();
    }

    public function reviewOutput(int $id, string $status, int $reviewedBy, ?string $approvedOutput = null, ?string $notes = null): void
    {
        $update = [
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'updated_at' => now(),
        ];

        if ($approvedOutput !== null) {
            $update['approved_output'] = $approvedOutput;
        }

        // If approving with edits, compute edit distance
        if ($status === 'approved' && $approvedOutput !== null) {
            $output = DB::table('ai_output_log')->where('id', $id)->first();
            if ($output && $output->raw_output) {
                $update['edit_distance'] = levenshtein(
                    substr($output->raw_output, 0, 255),
                    substr($approvedOutput, 0, 255)
                );
            }
        }

        // If approving without changes, set approved = raw and edit_distance = 0
        if ($status === 'approved' && $approvedOutput === null) {
            $output = DB::table('ai_output_log')->where('id', $id)->first();
            if ($output) {
                $update['approved_output'] = $output->raw_output;
                $update['edit_distance'] = 0;
            }
        }

        DB::table('ai_output_log')->where('id', $id)->update($update);
    }

    public function computeEditDistance(int $id): int
    {
        $output = DB::table('ai_output_log')->where('id', $id)->first();
        if (!$output || !$output->raw_output || !$output->approved_output) {
            return -1;
        }

        $distance = levenshtein(
            substr($output->raw_output, 0, 255),
            substr($output->approved_output, 0, 255)
        );

        DB::table('ai_output_log')->where('id', $id)->update([
            'edit_distance' => $distance,
            'updated_at' => now(),
        ]);

        return $distance;
    }

    public function getProvenanceChain(string $entityIri): array
    {
        return DB::table('ai_output_log')
            ->where('entity_iri', $entityIri)
            ->orderBy('created_at')
            ->get()
            ->map(function ($row) {
                $row->retrieved_records = json_decode($row->retrieved_records ?? '[]', true);
                $row->risk_flags = json_decode($row->risk_flags ?? '[]', true);
                return $row;
            })
            ->toArray();
    }

    public function getPendingReviews(int $limit = 50): array
    {
        return DB::table('ai_output_log')
            ->where('status', 'pending_review')
            ->orderBy('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->risk_flags = json_decode($row->risk_flags ?? '[]', true);
                return $row;
            })
            ->toArray();
    }

    public function getReviewerStats(int $userId): array
    {
        $base = DB::table('ai_output_log')->where('reviewed_by', $userId);

        return [
            'total_reviewed' => (clone $base)->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
            'avg_edit_distance' => (clone $base)->where('status', 'approved')->avg('edit_distance'),
            'avg_review_time_hours' => DB::table('ai_output_log')
                ->where('reviewed_by', $userId)
                ->whereNotNull('reviewed_at')
                ->selectRaw("AVG(EXTRACT(EPOCH FROM (reviewed_at - created_at)) / 3600) as avg_hours")
                ->value('avg_hours'),
            'by_output_type' => DB::table('ai_output_log')
                ->where('reviewed_by', $userId)
                ->select('output_type', DB::raw('COUNT(*) as count'))
                ->groupBy('output_type')
                ->get()
                ->toArray(),
        ];
    }

    public function rateOutput(int $outputId, int $userId, int $rating, ?string $comment = null): void
    {
        DB::table('ai_satisfaction_ratings')->updateOrInsert(
            ['ai_output_id' => $outputId, 'user_id' => $userId],
            ['rating' => max(1, min(5, $rating)), 'comment' => $comment, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function getOutputRatings(int $outputId): array
    {
        return DB::table('ai_satisfaction_ratings')
            ->where('ai_output_id', $outputId)
            ->leftJoin('users', 'ai_satisfaction_ratings.user_id', '=', 'users.id')
            ->select('ai_satisfaction_ratings.*', 'users.name as user_name')
            ->get()
            ->toArray();
    }

    public function getOutputTypes(): array
    {
        return [
            'description_draft' => 'Description Draft',
            'sensitivity_flag' => 'Sensitivity Flag',
            'keyword_suggestion' => 'Keyword Suggestion',
            'summary' => 'Summary',
            'embedding' => 'Embedding',
            'rag_response' => 'RAG Response',
            'classification' => 'Classification',
            'translation' => 'Translation',
        ];
    }

    public function getStatusOptions(): array
    {
        return [
            'pending_review' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'auto_applied' => 'Auto-applied',
            'superseded' => 'Superseded',
        ];
    }

    public function getModelNames(): array
    {
        return DB::table('ai_output_log')
            ->select('model_name')
            ->distinct()
            ->orderBy('model_name')
            ->pluck('model_name')
            ->toArray();
    }
}
