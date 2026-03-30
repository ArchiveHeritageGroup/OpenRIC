<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

/**
 * AI Governance Service - Core service for managing AI governance operations.
 * 
 * This service handles:
 * - AI rights and restrictions management
 * - AI output provenance logging
 * - Evaluation metrics tracking
 * - Bias/harm register management
 * - Readiness profile management
 */
class AiGovernanceService
{
    /**
     * Check if AI operations are allowed for an entity.
     */
    public function isAiAllowed(string $entityIri, string $operation = 'ai_allowed'): bool
    {
        $rights = DB::table('ai_rights')
            ->where('entity_iri', $entityIri)
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->first();

        return $rights && $rights->{$operation};
    }

    /**
     * Get AI rights for an entity.
     */
    public function getRights(string $entityIri): ?object
    {
        return DB::table('ai_rights')
            ->where('entity_iri', $entityIri)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Set AI rights for an entity.
     */
    public function setRights(string $entityIri, array $rights, int $userId): int
    {
        return DB::table('ai_rights')->insertGetId([
            'entity_iri' => $entityIri,
            'entity_type' => $rights['entity_type'] ?? null,
            'ai_allowed' => $rights['ai_allowed'] ?? false,
            'summarisation_allowed' => $rights['summarisation_allowed'] ?? false,
            'embedding_allowed' => $rights['embedding_allowed'] ?? false,
            'training_reuse_allowed' => $rights['training_reuse_allowed'] ?? false,
            'redaction_required' => $rights['redaction_required'] ?? false,
            'ai_review_notes' => $rights['ai_review_notes'] ?? null,
            'valid_from' => $rights['valid_from'] ?? null,
            'valid_until' => $rights['valid_until'] ?? null,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Log an AI output with provenance information.
     */
    public function logAiOutput(
        string $entityIri,
        string $action,
        string $outputText,
        array $options = []
    ): int {
        return DB::table('ai_output_log')->insertGetId([
            'entity_iri' => $entityIri,
            'action' => $action,
            'model_version' => $options['model_version'] ?? null,
            'prompt_pipeline' => $options['prompt_pipeline'] ?? null,
            'retrieved_records' => isset($options['retrieved_records']) 
                ? json_encode($options['retrieved_records']) : null,
            'confidence_score' => $options['confidence_score'] ?? null,
            'risk_flags' => isset($options['risk_flags']) 
                ? json_encode($options['risk_flags']) : null,
            'output_text' => $outputText,
            'version' => $options['version'] ?? 1,
            'parent_version' => $options['parent_version'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Approve an AI output.
     */
    public function approveOutput(int $outputId, int $userId): bool
    {
        return DB::table('ai_output_log')
            ->where('id', $outputId)
            ->update([
                'approved' => true,
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Get pending AI outputs for review.
     */
    public function getPendingOutputs(int $limit = 50): Collection
    {
        return DB::table('ai_output_log')
            ->where('approved', false)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get output history for an entity.
     */
    public function getOutputHistory(string $entityIri, int $limit = 20): Collection
    {
        return DB::table('ai_output_log')
            ->where('entity_iri', $entityIri)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Add a bias/harm record.
     */
    public function addBiasRecord(array $data, int $userId): int
    {
        return DB::table('bias_harm_register')->insertGetId([
            'entity_iri' => $data['entity_iri'] ?? null,
            'category' => $data['category'],
            'description' => $data['description'],
            'ai_warning' => $data['ai_warning'] ?? null,
            'mitigation_strategy' => $data['mitigation_strategy'] ?? null,
            'severity' => $data['severity'] ?? 'medium',
            'reported_by' => $userId,
            'resolved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Resolve a bias record.
     */
    public function resolveBiasRecord(int $recordId, int $userId): bool
    {
        return DB::table('bias_harm_register')
            ->where('id', $recordId)
            ->update([
                'resolved' => true,
                'resolved_at' => now(),
                'resolved_by' => $userId,
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Get unresolved bias records.
     */
    public function getUnresolvedBiasRecords(int $limit = 100): Collection
    {
        return DB::table('bias_harm_register')
            ->where('resolved', false)
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Record an evaluation metric.
     */
    public function recordMetric(array $data): int
    {
        return DB::table('ai_evaluation_metrics')->insertGetId([
            'entity_iri' => $data['entity_iri'] ?? null,
            'project_name' => $data['project_name'] ?? null,
            'metric_type' => $data['metric_type'],
            'metric_value' => $data['metric_value'],
            'sample_size' => $data['sample_size'] ?? 0,
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get metrics for a time period.
     */
    public function getMetrics(string $metricType, string $startDate, string $endDate): Collection
    {
        return DB::table('ai_evaluation_metrics')
            ->where('metric_type', $metricType)
            ->whereBetween('period_start', [$startDate, $endDate])
            ->orderBy('period_start')
            ->get();
    }

    /**
     * Get AI readiness profile for a collection.
     */
    public function getReadinessProfile(string $collectionIri): ?object
    {
        return DB::table('ai_readiness_profiles')
            ->where('collection_iri', $collectionIri)
            ->first();
    }

    /**
     * Create or update AI readiness profile.
     */
    public function saveReadinessProfile(string $collectionIri, array $data, int $userId): int
    {
        $existing = $this->getReadinessProfile($collectionIri);
        
        $profileData = [
            'collection_iri' => $collectionIri,
            'collection_title' => $data['collection_title'] ?? null,
            'digitization_completeness' => $data['digitization_completeness'] ?? 'not_started',
            'known_gaps' => $data['known_gaps'] ?? null,
            'excluded_records' => $data['excluded_records'] ?? null,
            'legal_exclusions' => $data['legal_exclusions'] ?? null,
            'privacy_exclusions' => $data['privacy_exclusions'] ?? null,
            'representational_bias_notes' => $data['representational_bias_notes'] ?? null,
            'corpus_completeness' => $data['corpus_completeness'] ?? 'partial',
            'last_reviewed_at' => now(),
            'reviewed_by' => $userId,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('ai_readiness_profiles')
                ->where('collection_iri', $collectionIri)
                ->update($profileData);
            return $existing->id;
        }

        $profileData['created_at'] = now();
        return DB::table('ai_readiness_profiles')->insertGetId($profileData);
    }

    /**
     * Get derivative profile for a collection.
     */
    public function getDerivativeProfile(string $collectionIri): ?object
    {
        return DB::table('ai_derivative_profiles')
            ->where('collection_iri', $collectionIri)
            ->where('active', true)
            ->first();
    }

    /**
     * Create or update derivative profile.
     */
    public function saveDerivativeProfile(string $collectionIri, array $data): int
    {
        $existing = $this->getDerivativeProfile($collectionIri);
        
        $profileData = [
            'collection_iri' => $collectionIri,
            'profile_name' => $data['profile_name'] ?? 'default',
            'cleaned_ocr_text' => $data['cleaned_ocr_text'] ?? false,
            'normalised_metadata_export' => $data['normalised_metadata_export'] ?? false,
            'chunked_retrieval_units' => $data['chunked_retrieval_units'] ?? false,
            'redacted_access_copies' => $data['redacted_access_copies'] ?? false,
            'multilingual_alignment' => $data['multilingual_alignment'] ?? false,
            'formats' => isset($data['formats']) ? json_encode($data['formats']) : '["pdf", "txt", "json"]',
            'chunk_size' => $data['chunk_size'] ?? 512,
            'chunk_overlap' => $data['chunk_overlap'] ?? 50,
            'description' => $data['description'] ?? null,
            'active' => true,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('ai_derivative_profiles')
                ->where('collection_iri', $collectionIri)
                ->update($profileData);
            return $existing->id;
        }

        $profileData['created_at'] = now();
        return DB::table('ai_derivative_profiles')->insertGetId($profileData);
    }

    /**
     * Get language AI settings.
     */
    public function getLanguageSettings(string $languageCode): ?object
    {
        return DB::table('language_ai_settings')
            ->where('language_code', $languageCode)
            ->first();
    }

    /**
     * Save language AI settings.
     */
    public function saveLanguageSettings(array $data): int
    {
        $existing = $this->getLanguageSettings($data['language_code']);
        
        $settingsData = [
            'language_code' => $data['language_code'],
            'language_name' => $data['language_name'],
            'ai_allowed' => $data['ai_allowed'] ?? true,
            'translation_allowed' => $data['translation_allowed'] ?? true,
            'embedding_enabled' => $data['embedding_enabled'] ?? true,
            'access_warning' => $data['access_warning'] ?? null,
            'reviewer_id' => $data['reviewer_id'] ?? null,
            'competency_required' => $data['competency_required'] ?? false,
            'competency_languages' => isset($data['competency_languages']) 
                ? json_encode($data['competency_languages']) : null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('language_ai_settings')
                ->where('language_code', $data['language_code'])
                ->update($settingsData);
            return $existing->id;
        }

        $settingsData['created_at'] = now();
        return DB::table('language_ai_settings')->insertGetId($settingsData);
    }

    /**
     * Get AI project readiness checklist.
     */
    public function getProjectReadiness(string $projectName): ?object
    {
        return DB::table('ai_project_readiness')
            ->where('project_name', $projectName)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Create or update project readiness.
     */
    public function saveProjectReadiness(array $data, int $userId): int
    {
        $existing = $this->getProjectReadiness($data['project_name']);
        
        $readinessData = [
            'project_name' => $data['project_name'],
            'use_case_description' => $data['use_case_description'],
            'corpus_completeness_documented' => $data['corpus_completeness_documented'] ?? false,
            'metadata_minimum_met' => $data['metadata_minimum_met'] ?? false,
            'access_rules_structured' => $data['access_rules_structured'] ?? false,
            'derivatives_prepared' => $data['derivatives_prepared'] ?? false,
            'evaluation_plan_approved' => $data['evaluation_plan_approved'] ?? false,
            'human_review_workflow_active' => $data['human_review_workflow_active'] ?? false,
            'status' => $data['status'] ?? 'draft',
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('ai_project_readiness')
                ->where('project_name', $data['project_name'])
                ->update($readinessData);
            return $existing->id;
        }

        $readinessData['created_by'] = $userId;
        $readinessData['created_at'] = now();
        return DB::table('ai_project_readiness')->insertGetId($readinessData);
    }

    /**
     * Submit project for approval.
     */
    public function submitForApproval(int $projectId): bool
    {
        return DB::table('ai_project_readiness')
            ->where('id', $projectId)
            ->update(['status' => 'pending_approval', 'updated_at' => now()]) > 0;
    }

    /**
     * Approve or reject project.
     */
    public function approveProject(int $projectId, int $userId, bool $approved, ?string $reason = null): bool
    {
        return DB::table('ai_project_readiness')
            ->where('id', $projectId)
            ->update([
                'status' => $approved ? 'approved' : 'rejected',
                'approved_by' => $userId,
                'approved_at' => now(),
                'rejection_reason' => $reason,
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Get dashboard summary statistics.
     */
    public function getDashboardStats(): array
    {
        return [
            'pending_outputs' => DB::table('ai_output_log')->where('approved', false)->count(),
            'unresolved_bias' => DB::table('bias_harm_register')->where('resolved', false)->count(),
            'critical_bias' => DB::table('bias_harm_register')
                ->where('resolved', false)
                ->where('severity', 'critical')
                ->count(),
            'pending_projects' => DB::table('ai_project_readiness')
                ->where('status', 'pending_approval')
                ->count(),
            'ai_restricted_entities' => DB::table('ai_rights')
                ->where('ai_allowed', false)
                ->count(),
            'ai_allowed_entities' => DB::table('ai_rights')
                ->where('ai_allowed', true)
                ->count(),
        ];
    }
}
