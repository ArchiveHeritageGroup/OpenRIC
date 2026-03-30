<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\AiGovernance\Contracts\AiReadinessServiceInterface;

/**
 * AI Readiness Service — Modules 1, 5, 6, 8.
 *
 * Readiness profiles, bias register, derivative packaging, readiness checklists.
 */
class AiReadinessService implements AiReadinessServiceInterface
{
    // ── Module 1: Readiness Profiles ────────────────────────────────

    public function getProfile(int $id): ?object
    {
        return DB::table('ai_readiness_profiles')->where('id', $id)->first();
    }

    public function getProfileByCollection(string $collectionIri): ?object
    {
        return DB::table('ai_readiness_profiles')->where('collection_iri', $collectionIri)->first();
    }

    public function listProfiles(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = DB::table('ai_readiness_profiles');

        if (!empty($filters['completeness'])) {
            $query->where('digitisation_completeness', $filters['completeness']);
        }
        if (!empty($filters['corpus_status'])) {
            $query->where('corpus_status', $filters['corpus_status']);
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('collection_title', 'ILIKE', $like)
                  ->orWhere('known_gaps', 'ILIKE', $like)
                  ->orWhere('representational_bias_notes', 'ILIKE', $like);
            });
        }

        $total = $query->count();
        $results = $query->orderByDesc('updated_at')->offset($offset)->limit($limit)->get();

        return ['results' => $results, 'total' => $total];
    }

    public function createProfile(array $data): int
    {
        $data['languages_present'] = isset($data['languages_present']) ? json_encode($data['languages_present']) : null;
        $data['metadata_standards'] = isset($data['metadata_standards']) ? json_encode($data['metadata_standards']) : null;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return (int) DB::table('ai_readiness_profiles')->insertGetId($data);
    }

    public function updateProfile(int $id, array $data): void
    {
        if (isset($data['languages_present'])) {
            $data['languages_present'] = json_encode($data['languages_present']);
        }
        if (isset($data['metadata_standards'])) {
            $data['metadata_standards'] = json_encode($data['metadata_standards']);
        }
        $data['updated_at'] = now();
        DB::table('ai_readiness_profiles')->where('id', $id)->update($data);
    }

    public function deleteProfile(int $id): void
    {
        DB::table('ai_readiness_profiles')->where('id', $id)->delete();
    }

    public function getCompletenessOptions(): array
    {
        return [
            'complete' => 'Complete — all items digitised',
            'partial' => 'Partial — some items digitised',
            'sampled' => 'Sampled — representative subset',
            'none' => 'None — no digitisation yet',
            'unknown' => 'Unknown — not assessed',
        ];
    }

    // ── Module 5: Bias Register ─────────────────────────────────────

    public function listBiasEntries(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = DB::table('ai_bias_register');

        if (!empty($filters['risk_type'])) {
            $query->where('risk_type', $filters['risk_type']);
        }
        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }
        if (isset($filters['is_resolved'])) {
            $query->where('is_resolved', (bool) $filters['is_resolved']);
        }
        if (!empty($filters['entity_iri'])) {
            $query->where('entity_iri', $filters['entity_iri']);
        }
        if (!empty($filters['collection_iri'])) {
            $query->where('collection_iri', $filters['collection_iri']);
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('description', 'ILIKE', $like)
                  ->orWhere('specific_content', 'ILIKE', $like)
                  ->orWhere('ai_warning', 'ILIKE', $like);
            });
        }

        $total = $query->count();
        $results = $query->orderByDesc('flagged_at')->offset($offset)->limit($limit)->get();

        return ['results' => $results, 'total' => $total];
    }

    public function getBiasEntry(int $id): ?object
    {
        return DB::table('ai_bias_register')->where('id', $id)->first();
    }

    public function createBiasEntry(array $data): int
    {
        $data['flagged_at'] = $data['flagged_at'] ?? now();
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return (int) DB::table('ai_bias_register')->insertGetId($data);
    }

    public function updateBiasEntry(int $id, array $data): void
    {
        $data['updated_at'] = now();
        DB::table('ai_bias_register')->where('id', $id)->update($data);
    }

    public function resolveBiasEntry(int $id, int $resolvedBy, string $notes): void
    {
        DB::table('ai_bias_register')->where('id', $id)->update([
            'is_resolved' => true,
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
            'updated_at' => now(),
        ]);
    }

    public function deleteBiasEntry(int $id): void
    {
        DB::table('ai_bias_register')->where('id', $id)->delete();
    }

    public function getBiasStats(): array
    {
        return [
            'total' => DB::table('ai_bias_register')->count(),
            'open' => DB::table('ai_bias_register')->where('is_resolved', false)->count(),
            'resolved' => DB::table('ai_bias_register')->where('is_resolved', true)->count(),
            'critical' => DB::table('ai_bias_register')->where('severity', 'critical')->where('is_resolved', false)->count(),
            'high' => DB::table('ai_bias_register')->where('severity', 'high')->where('is_resolved', false)->count(),
            'by_type' => DB::table('ai_bias_register')
                ->select('risk_type', DB::raw('COUNT(*) as count'))
                ->where('is_resolved', false)
                ->groupBy('risk_type')
                ->orderByDesc('count')
                ->get()
                ->toArray(),
            'by_severity' => DB::table('ai_bias_register')
                ->select('severity', DB::raw('COUNT(*) as count'))
                ->where('is_resolved', false)
                ->groupBy('severity')
                ->get()
                ->toArray(),
        ];
    }

    public function getRiskTypes(): array
    {
        return [
            'harmful_language' => 'Harmful legacy language',
            'culturally_sensitive' => 'Culturally sensitive content',
            'absent_community' => 'Absent or under-represented community',
            'contested_description' => 'Contested description',
            'colonial_bias' => 'Colonial bias',
            'gender_bias' => 'Gender bias',
            'racial_bias' => 'Racial bias',
            'ageism' => 'Ageism',
            'ableism' => 'Ableism',
            'religious_bias' => 'Religious bias',
            'other' => 'Other',
        ];
    }

    public function getSeverityLevels(): array
    {
        return ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];
    }

    public function getBiasEntriesForEntity(string $entityIri): array
    {
        return DB::table('ai_bias_register')
            ->where('entity_iri', $entityIri)
            ->orderByDesc('flagged_at')
            ->get()
            ->toArray();
    }

    public function getAiWarningsForEntity(string $entityIri): array
    {
        return DB::table('ai_bias_register')
            ->where('entity_iri', $entityIri)
            ->where('is_resolved', false)
            ->whereNotNull('ai_warning')
            ->pluck('ai_warning')
            ->toArray();
    }

    // ── Module 6: Derivative Packaging ──────────────────────────────

    public function listDerivatives(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = DB::table('ai_derivatives');

        if (!empty($filters['derivative_type'])) {
            $query->where('derivative_type', $filters['derivative_type']);
        }
        if (!empty($filters['format'])) {
            $query->where('format', $filters['format']);
        }
        if (isset($filters['is_current'])) {
            $query->where('is_current', (bool) $filters['is_current']);
        }
        if (!empty($filters['entity_iri'])) {
            $query->where('entity_iri', $filters['entity_iri']);
        }
        if (!empty($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        $total = $query->count();
        $results = $query->orderByDesc('created_at')->offset($offset)->limit($limit)->get();

        return ['results' => $results, 'total' => $total];
    }

    public function getDerivative(int $id): ?object
    {
        return DB::table('ai_derivatives')->where('id', $id)->first();
    }

    public function createDerivative(array $data): int
    {
        // Mark previous versions as not current
        if (!empty($data['entity_iri']) && !empty($data['derivative_type'])) {
            $maxVersion = DB::table('ai_derivatives')
                ->where('entity_iri', $data['entity_iri'])
                ->where('derivative_type', $data['derivative_type'])
                ->max('version') ?? 0;

            DB::table('ai_derivatives')
                ->where('entity_iri', $data['entity_iri'])
                ->where('derivative_type', $data['derivative_type'])
                ->update(['is_current' => false]);

            $data['version'] = $maxVersion + 1;
        }

        $data['is_current'] = true;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return (int) DB::table('ai_derivatives')->insertGetId($data);
    }

    public function updateDerivative(int $id, array $data): void
    {
        $data['updated_at'] = now();
        DB::table('ai_derivatives')->where('id', $id)->update($data);
    }

    public function deleteDerivative(int $id): void
    {
        DB::table('ai_derivatives')->where('id', $id)->delete();
    }

    public function getDerivativesForEntity(string $entityIri): array
    {
        return DB::table('ai_derivatives')
            ->where('entity_iri', $entityIri)
            ->orderBy('derivative_type')
            ->orderByDesc('version')
            ->get()
            ->toArray();
    }

    public function getCurrentDerivative(string $entityIri, string $derivativeType): ?object
    {
        return DB::table('ai_derivatives')
            ->where('entity_iri', $entityIri)
            ->where('derivative_type', $derivativeType)
            ->where('is_current', true)
            ->first();
    }

    public function getDerivativeTypes(): array
    {
        return [
            'ocr_text' => 'OCR Text',
            'normalized_metadata' => 'Normalised Metadata Export',
            'chunked_retrieval' => 'Chunked Retrieval Units',
            'redacted_copy' => 'Redacted Access Copy',
            'multilingual_alignment' => 'Multilingual Text Alignment',
            'cleaned_text' => 'Cleaned / Normalised Text',
            'structured_extract' => 'Structured Data Extract',
        ];
    }

    public function getDerivativeFormats(): array
    {
        return [
            'utf8_text' => 'UTF-8 Plain Text',
            'json' => 'JSON',
            'xml' => 'XML',
            'csv' => 'CSV',
            'turtle' => 'Turtle (RDF)',
            'jsonld' => 'JSON-LD',
            'pdf' => 'PDF',
        ];
    }

    public function getDerivativeStats(): array
    {
        return [
            'total' => DB::table('ai_derivatives')->count(),
            'current' => DB::table('ai_derivatives')->where('is_current', true)->count(),
            'total_size_bytes' => DB::table('ai_derivatives')->where('is_current', true)->sum('file_size_bytes'),
            'by_type' => DB::table('ai_derivatives')
                ->where('is_current', true)
                ->select('derivative_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(file_size_bytes) as total_bytes'))
                ->groupBy('derivative_type')
                ->orderByDesc('count')
                ->get()
                ->toArray(),
            'by_format' => DB::table('ai_derivatives')
                ->where('is_current', true)
                ->select('format', DB::raw('COUNT(*) as count'))
                ->groupBy('format')
                ->orderByDesc('count')
                ->get()
                ->toArray(),
        ];
    }

    // ── Module 8: Readiness Checklist ───────────────────────────────

    public function listChecklists(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = DB::table('ai_readiness_checklists');

        if (!empty($filters['status'])) {
            $query->where('checklist_status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('project_name', 'ILIKE', $like)
                  ->orWhere('use_case', 'ILIKE', $like);
            });
        }

        $total = $query->count();
        $results = $query->orderByDesc('updated_at')->offset($offset)->limit($limit)->get();

        return ['results' => $results, 'total' => $total];
    }

    public function getChecklist(int $id): ?object
    {
        return DB::table('ai_readiness_checklists')->where('id', $id)->first();
    }

    public function createChecklist(array $data): int
    {
        $data['checklist_status'] = 'not_started';
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return (int) DB::table('ai_readiness_checklists')->insertGetId($data);
    }

    public function updateChecklist(int $id, array $data): void
    {
        $data['updated_at'] = now();

        // Auto-compute status
        $checklist = $this->getChecklist($id);
        if ($checklist) {
            $merged = (object) array_merge((array) $checklist, $data);
            $items = [
                $merged->use_case_defined ?? false,
                $merged->corpus_completeness_documented ?? false,
                $merged->metadata_minimum_met ?? false,
                $merged->access_rules_structured ?? false,
                $merged->derivatives_prepared ?? false,
                $merged->evaluation_plan_approved ?? false,
                $merged->human_review_workflow_active ?? false,
            ];
            $completed = count(array_filter($items));
            if ($completed === 7) {
                $data['checklist_status'] = 'ready';
            } elseif ($completed > 0) {
                $data['checklist_status'] = 'in_progress';
            }
        }

        DB::table('ai_readiness_checklists')->where('id', $id)->update($data);
    }

    public function deleteChecklist(int $id): void
    {
        DB::table('ai_readiness_checklists')->where('id', $id)->delete();
    }

    public function approveChecklist(int $id, int $approvedBy): void
    {
        DB::table('ai_readiness_checklists')->where('id', $id)->update([
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'checklist_status' => 'ready',
            'updated_at' => now(),
        ]);
    }

    public function computeChecklistAutoChecks(int $id): array
    {
        $checklist = $this->getChecklist($id);
        if (!$checklist) {
            return [];
        }

        $autoChecks = [];

        // Check if readiness profile exists
        if ($checklist->readiness_profile_id) {
            $profile = $this->getProfile((int) $checklist->readiness_profile_id);
            $autoChecks['corpus_completeness_documented'] = $profile !== null && $profile->digitisation_completeness !== 'unknown';
        }

        // Check if AI rights restrictions exist
        $restrictionCount = DB::table('ai_rights_restrictions')->count();
        $autoChecks['access_rules_structured'] = $restrictionCount > 0;

        // Check if derivatives exist
        $derivativeCount = DB::table('ai_derivatives')->where('is_current', true)->count();
        $autoChecks['derivatives_prepared'] = $derivativeCount > 0;

        // Check if evaluation metrics exist
        $metricsCount = DB::table('ai_evaluation_metrics')->count();
        $autoChecks['evaluation_plan_approved'] = $metricsCount > 0;

        // Check if workflow is active
        try {
            $activeWorkflows = DB::table('workflows')->where('is_active', true)->count();
            $autoChecks['human_review_workflow_active'] = $activeWorkflows > 0;
        } catch (\Throwable) {
            $autoChecks['human_review_workflow_active'] = false;
        }

        return $autoChecks;
    }

    public function getChecklistStatusOptions(): array
    {
        return [
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'ready' => 'Ready',
            'blocked' => 'Blocked',
        ];
    }
}
