<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\AiGovernance\Contracts\AiEvaluationServiceInterface;
use OpenRiC\AiGovernance\Contracts\AiProvenanceServiceInterface;
use OpenRiC\AiGovernance\Contracts\AiReadinessServiceInterface;
use OpenRiC\AiGovernance\Contracts\AiRightsServiceInterface;

/**
 * AI Governance Controller — full CRUD for all 8 modules of the
 * AI Preparedness Control Framework.
 */
class AiGovernanceController extends Controller
{
    public function __construct(
        private readonly AiReadinessServiceInterface $readiness,
        private readonly AiRightsServiceInterface $rights,
        private readonly AiProvenanceServiceInterface $provenance,
        private readonly AiEvaluationServiceInterface $evaluation,
    ) {}

    // ════════════════════════════════════════════════════════════════
    //  Dashboard
    // ════════════════════════════════════════════════════════════════

    public function dashboard(): View
    {
        $profileData = $this->readiness->listProfiles([], 1, 0);
        $profileCount = $profileData['total'];

        $restrictionData = $this->rights->listRestrictions([], 1, 0);
        $restrictionCount = $restrictionData['total'];

        $pendingReviews = $this->provenance->getPendingReviews(1);
        $pendingCount = count($pendingReviews);
        $outputData = $this->provenance->listOutputs([], 1, 0);
        $outputCount = $outputData['total'];

        $evalSummary = $this->evaluation->getDashboardSummary();

        $biasStats = $this->readiness->getBiasStats();

        $derivativeStats = $this->readiness->getDerivativeStats();

        $langConfigs = $this->evaluation->listLanguageConfigs();
        $langCount = count($langConfigs);

        $checklistData = $this->readiness->listChecklists([], 1, 0);
        $checklistCount = $checklistData['total'];
        $readyChecklists = $this->readiness->listChecklists(['status' => 'ready'], 1, 0)['total'];

        return view('ai-governance::dashboard', compact(
            'profileCount',
            'restrictionCount',
            'pendingCount',
            'outputCount',
            'evalSummary',
            'biasStats',
            'derivativeStats',
            'langCount',
            'checklistCount',
            'readyChecklists',
        ));
    }

    // ════════════════════════════════════════════════════════════════
    //  Module 1: Readiness Profiles
    // ════════════════════════════════════════════════════════════════

    public function readinessProfiles(Request $request): View
    {
        $filters = $request->only(['completeness', 'corpus_status', 'search']);
        $page = max(1, (int) $request->get('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $data = $this->readiness->listProfiles($filters, $limit, $offset);
        $completenessOptions = $this->readiness->getCompletenessOptions();

        return view('ai-governance::readiness-profile', [
            'items' => $data['results'],
            'total' => $data['total'],
            'page' => $page,
            'limit' => $limit,
            'filters' => $filters,
            'completenessOptions' => $completenessOptions,
            'editing' => null,
            'mode' => 'list',
        ]);
    }

    public function readinessProfileCreate(): View
    {
        $completenessOptions = $this->readiness->getCompletenessOptions();

        return view('ai-governance::readiness-profile', [
            'items' => collect([]),
            'total' => 0,
            'page' => 1,
            'limit' => 25,
            'filters' => [],
            'completenessOptions' => $completenessOptions,
            'editing' => null,
            'mode' => 'create',
        ]);
    }

    public function readinessProfileStore(Request $request): RedirectResponse
    {
        $request->validate([
            'collection_iri' => 'required|string|max:1000',
            'collection_title' => 'required|string|max:500',
            'digitisation_completeness' => 'required|string|max:50',
        ]);

        $data = $request->only([
            'collection_iri', 'collection_title', 'digitisation_completeness', 'corpus_status',
            'known_gaps', 'excluded_records', 'legal_privacy_exclusions',
            'representational_bias_notes', 'data_quality_notes', 'format_notes',
            'estimated_item_count', 'digitised_item_count', 'described_item_count',
        ]);

        if ($request->filled('languages_present')) {
            $data['languages_present'] = array_map('trim', explode(',', $request->input('languages_present')));
        }
        if ($request->filled('metadata_standards')) {
            $data['metadata_standards'] = array_map('trim', explode(',', $request->input('metadata_standards')));
        }

        $data['assessed_by'] = $request->user()?->id;
        $data['last_assessed_at'] = now();

        $this->readiness->createProfile($data);

        return redirect()->route('ai-governance.readiness-profiles')->with('success', 'Readiness profile created.');
    }

    public function readinessProfileEdit(int $id): View|RedirectResponse
    {
        $profile = $this->readiness->getProfile($id);
        if (!$profile) {
            return redirect()->route('ai-governance.readiness-profiles')->with('error', 'Profile not found.');
        }

        $completenessOptions = $this->readiness->getCompletenessOptions();

        return view('ai-governance::readiness-profile', [
            'items' => collect([]),
            'total' => 0,
            'page' => 1,
            'limit' => 25,
            'filters' => [],
            'completenessOptions' => $completenessOptions,
            'editing' => $profile,
            'mode' => 'edit',
        ]);
    }

    public function readinessProfileUpdate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'collection_iri' => 'required|string|max:1000',
            'collection_title' => 'required|string|max:500',
            'digitisation_completeness' => 'required|string|max:50',
        ]);

        $data = $request->only([
            'collection_iri', 'collection_title', 'digitisation_completeness', 'corpus_status',
            'known_gaps', 'excluded_records', 'legal_privacy_exclusions',
            'representational_bias_notes', 'data_quality_notes', 'format_notes',
            'estimated_item_count', 'digitised_item_count', 'described_item_count',
        ]);

        if ($request->filled('languages_present')) {
            $data['languages_present'] = array_map('trim', explode(',', $request->input('languages_present')));
        }
        if ($request->filled('metadata_standards')) {
            $data['metadata_standards'] = array_map('trim', explode(',', $request->input('metadata_standards')));
        }

        $data['assessed_by'] = $request->user()?->id;
        $data['last_assessed_at'] = now();

        $this->readiness->updateProfile($id, $data);

        return redirect()->route('ai-governance.readiness-profiles')->with('success', 'Readiness profile updated.');
    }

    public function readinessProfileDelete(int $id): RedirectResponse
    {
        $this->readiness->deleteProfile($id);

        return redirect()->route('ai-governance.readiness-profiles')->with('success', 'Readiness profile deleted.');
    }

    // ════════════════════════════════════════════════════════════════
    //  Module 2: Rights Matrix
    // ════════════════════════════════════════════════════════════════

    public function rightsMatrix(Request $request): View
    {
        $filters = $request->only(['scope', 'search', 'ai_allowed', 'training_blocked']);
        $page = max(1, (int) $request->get('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $data = $this->rights->listRestrictions($filters, $limit, $offset);
        $operationTypes = $this->rights->getOperationTypes();
        $scopeTypes = $this->rights->getScopeTypes();

        return view('ai-governance::rights-matrix', [
            'items' => $data['results'],
            'total' => $data['total'],
            'page' => $page,
            'limit' => $limit,
            'filters' => $filters,
            'operationTypes' => $operationTypes,
            'scopeTypes' => $scopeTypes,
            'editing' => null,
            'mode' => 'list',
        ]);
    }

    public function rightsMatrixCreate(): View
    {
        $operationTypes = $this->rights->getOperationTypes();
        $scopeTypes = $this->rights->getScopeTypes();

        return view('ai-governance::rights-matrix', [
            'items' => collect([]),
            'total' => 0,
            'page' => 1,
            'limit' => 25,
            'filters' => [],
            'operationTypes' => $operationTypes,
            'scopeTypes' => $scopeTypes,
            'editing' => null,
            'mode' => 'create',
        ]);
    }

    public function rightsMatrixStore(Request $request): RedirectResponse
    {
        $request->validate([
            'applies_to_iri' => 'required|string|max:1000',
            'restriction_scope' => 'required|string|in:entity,collection,global',
        ]);

        $data = [
            'applies_to_iri' => $request->input('applies_to_iri'),
            'restriction_scope' => $request->input('restriction_scope'),
            'ai_allowed' => $request->boolean('ai_allowed'),
            'summarisation_allowed' => $request->boolean('summarisation_allowed'),
            'embedding_indexing_allowed' => $request->boolean('embedding_indexing_allowed'),
            'training_reuse_allowed' => $request->boolean('training_reuse_allowed'),
            'redaction_required_before_ai' => $request->boolean('redaction_required_before_ai'),
            'rag_retrieval_allowed' => $request->boolean('rag_retrieval_allowed'),
            'translation_allowed' => $request->boolean('translation_allowed'),
            'sensitivity_scan_allowed' => $request->boolean('sensitivity_scan_allowed'),
            'restriction_notes' => $request->input('restriction_notes'),
            'legal_basis' => $request->input('legal_basis'),
            'restriction_expires_at' => $request->input('restriction_expires_at'),
            'set_by' => $request->user()?->id,
        ];

        $this->rights->createRestriction($data);

        return redirect()->route('ai-governance.rights-matrix')->with('success', 'Restriction created.');
    }

    public function rightsMatrixEdit(int $id): View|RedirectResponse
    {
        $restriction = $this->rights->getRestriction($id);
        if (!$restriction) {
            return redirect()->route('ai-governance.rights-matrix')->with('error', 'Restriction not found.');
        }

        $operationTypes = $this->rights->getOperationTypes();
        $scopeTypes = $this->rights->getScopeTypes();

        return view('ai-governance::rights-matrix', [
            'items' => collect([]),
            'total' => 0,
            'page' => 1,
            'limit' => 25,
            'filters' => [],
            'operationTypes' => $operationTypes,
            'scopeTypes' => $scopeTypes,
            'editing' => $restriction,
            'mode' => 'edit',
        ]);
    }

    public function rightsMatrixUpdate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'applies_to_iri' => 'required|string|max:1000',
            'restriction_scope' => 'required|string|in:entity,collection,global',
        ]);

        $data = [
            'applies_to_iri' => $request->input('applies_to_iri'),
            'restriction_scope' => $request->input('restriction_scope'),
            'ai_allowed' => $request->boolean('ai_allowed'),
            'summarisation_allowed' => $request->boolean('summarisation_allowed'),
            'embedding_indexing_allowed' => $request->boolean('embedding_indexing_allowed'),
            'training_reuse_allowed' => $request->boolean('training_reuse_allowed'),
            'redaction_required_before_ai' => $request->boolean('redaction_required_before_ai'),
            'rag_retrieval_allowed' => $request->boolean('rag_retrieval_allowed'),
            'translation_allowed' => $request->boolean('translation_allowed'),
            'sensitivity_scan_allowed' => $request->boolean('sensitivity_scan_allowed'),
            'restriction_notes' => $request->input('restriction_notes'),
            'legal_basis' => $request->input('legal_basis'),
            'restriction_expires_at' => $request->input('restriction_expires_at'),
            'set_by' => $request->user()?->id,
        ];

        $this->rights->updateRestriction($id, $data);

        return redirect()->route('ai-governance.rights-matrix')->with('success', 'Restriction updated.');
    }

    public function rightsMatrixDelete(int $id): RedirectResponse
    {
        $this->rights->deleteRestriction($id);

        return redirect()->route('ai-governance.rights-matrix')->with('success', 'Restriction deleted.');
    }

    public function rightsMatrixBulk(Request $request): RedirectResponse
    {
        $request->validate([
            'entity_iris' => 'required|string',
        ]);

        $iris = array_filter(array_map('trim', explode("\n", $request->input('entity_iris'))));
        if (empty($iris)) {
            return redirect()->route('ai-governance.rights-matrix')->with('error', 'No IRIs provided.');
        }

        $restrictions = [
            'ai_allowed' => $request->boolean('ai_allowed'),
            'summarisation_allowed' => $request->boolean('summarisation_allowed'),
            'embedding_indexing_allowed' => $request->boolean('embedding_indexing_allowed'),
            'training_reuse_allowed' => $request->boolean('training_reuse_allowed'),
            'redaction_required_before_ai' => $request->boolean('redaction_required_before_ai'),
            'rag_retrieval_allowed' => $request->boolean('rag_retrieval_allowed'),
            'translation_allowed' => $request->boolean('translation_allowed'),
            'sensitivity_scan_allowed' => $request->boolean('sensitivity_scan_allowed'),
            'restriction_notes' => $request->input('restriction_notes'),
            'legal_basis' => $request->input('legal_basis'),
            'set_by' => $request->user()?->id,
        ];

        $affected = $this->rights->bulkApply($iris, $restrictions);

        return redirect()->route('ai-governance.rights-matrix')->with('success', "Restrictions applied to {$affected} entities.");
    }

    // ════════════════════════════════════════════════════════════════
    //  Module 3: Provenance Log
    // ════════════════════════════════════════════════════════════════

    public function provenanceLog(Request $request): View
    {
        $filters = $request->only([
            'entity_iri', 'output_type', 'status', 'model_name',
            'date_from', 'date_to', 'search', 'min_confidence', 'has_risk_flags',
        ]);
        $page = max(1, (int) $request->get('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $data = $this->provenance->listOutputs($filters, $limit, $offset);
        $outputTypes = $this->provenance->getOutputTypes();
        $statusOptions = $this->provenance->getStatusOptions();
        $modelNames = $this->provenance->getModelNames();

        return view('ai-governance::provenance-log', [
            'items' => $data['results'],
            'total' => $data['total'],
            'page' => $page,
            'limit' => $limit,
            'filters' => $filters,
            'outputTypes' => $outputTypes,
            'statusOptions' => $statusOptions,
            'modelNames' => $modelNames,
        ]);
    }

    public function provenanceDetail(int $id): View|RedirectResponse
    {
        $output = $this->provenance->getOutput($id);
        if (!$output) {
            return redirect()->route('ai-governance.provenance-log')->with('error', 'Output not found.');
        }

        $statusOptions = $this->provenance->getStatusOptions();
        $outputTypes = $this->provenance->getOutputTypes();
        $chain = $this->provenance->getProvenanceChain($output->entity_iri);

        return view('ai-governance::provenance-detail', [
            'output' => $output,
            'statusOptions' => $statusOptions,
            'outputTypes' => $outputTypes,
            'chain' => $chain,
        ]);
    }

    public function provenanceReview(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'status' => 'required|string|in:approved,rejected,superseded',
        ]);

        $this->provenance->reviewOutput(
            $id,
            $request->input('status'),
            (int) $request->user()?->id,
            $request->input('approved_output'),
            $request->input('review_notes'),
        );

        return redirect()->route('ai-governance.provenance-detail', $id)->with('success', 'Review saved.');
    }

    public function provenanceRate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $this->provenance->rateOutput(
            $id,
            (int) $request->user()?->id,
            (int) $request->input('rating'),
            $request->input('comment'),
        );

        return redirect()->route('ai-governance.provenance-detail', $id)->with('success', 'Rating saved.');
    }

    // ════════════════════════════════════════════════════════════════
    //  Module 4: Evaluation Dashboard
    // ════════════════════════════════════════════════════════════════

    public function evaluationDashboard(Request $request): View
    {
        $summary = $this->evaluation->getDashboardSummary();
        $latestMetrics = $this->evaluation->getLatestMetrics();
        $modelPerformance = $this->evaluation->getModelPerformance($request->input('use_case'));
        $outputTypes = $this->provenance->getOutputTypes();

        return view('ai-governance::evaluation-dashboard', [
            'summary' => $summary,
            'latestMetrics' => $latestMetrics,
            'modelPerformance' => $modelPerformance,
            'outputTypes' => $outputTypes,
        ]);
    }

    public function evaluationCompute(Request $request): RedirectResponse
    {
        $request->validate([
            'use_case' => 'required|string|max:100',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $metrics = $this->evaluation->computeMetrics(
            $request->input('use_case'),
            $request->input('period_start'),
            $request->input('period_end'),
        );

        $saveData = $metrics;
        unset($saveData['model_breakdown']);
        $saveData['model_breakdown'] = $metrics['model_breakdown'];
        $saveData['computed_by'] = $request->user()?->id;

        $this->evaluation->saveMetrics($saveData);

        return redirect()->route('ai-governance.evaluation')->with('success', 'Metrics computed and saved.');
    }

    public function evaluationTrend(string $useCase): View
    {
        $trend = $this->evaluation->getMetricsTrend($useCase, 12);
        $outputTypes = $this->provenance->getOutputTypes();

        return view('ai-governance::evaluation-dashboard', [
            'summary' => $this->evaluation->getDashboardSummary(),
            'latestMetrics' => $this->evaluation->getLatestMetrics(),
            'modelPerformance' => $this->evaluation->getModelPerformance($useCase),
            'outputTypes' => $outputTypes,
            'trend' => $trend,
            'trendUseCase' => $useCase,
        ]);
    }

    public function evaluationExport(Request $request): Response
    {
        $filters = $request->only(['use_case', 'date_from', 'date_to']);
        $csv = $this->evaluation->exportMetricsCsv($filters);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="ai-evaluation-metrics.csv"',
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    //  Module 5: Bias Register
    // ════════════════════════════════════════════════════════════════

    public function biasRegister(Request $request): View
    {
        $filters = $request->only(['risk_type', 'severity', 'is_resolved', 'entity_iri', 'collection_iri', 'search']);
        $page = max(1, (int) $request->get('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $data = $this->readiness->listBiasEntries($filters, $limit, $offset);
        $riskTypes = $this->readiness->getRiskTypes();
        $severityLevels = $this->readiness->getSeverityLevels();
        $biasStats = $this->readiness->getBiasStats();

        return view('ai-governance::bias-register', [
            'items' => $data['results'],
            'total' => $data['total'],
            'page' => $page,
            'limit' => $limit,
            'filters' => $filters,
            'riskTypes' => $riskTypes,
            'severityLevels' => $severityLevels,
            'biasStats' => $biasStats,
        ]);
    }

    public function biasEntryCreate(): View
    {
        $riskTypes = $this->readiness->getRiskTypes();
        $severityLevels = $this->readiness->getSeverityLevels();

        return view('ai-governance::bias-entry', [
            'editing' => null,
            'riskTypes' => $riskTypes,
            'severityLevels' => $severityLevels,
            'mode' => 'create',
        ]);
    }

    public function biasEntryStore(Request $request): RedirectResponse
    {
        $request->validate([
            'risk_type' => 'required|string|max:100',
            'severity' => 'required|string|in:low,medium,high,critical',
            'description' => 'required|string',
        ]);

        $data = $request->only([
            'entity_iri', 'collection_iri', 'risk_type', 'severity',
            'description', 'specific_content', 'mitigation_notes', 'ai_warning',
        ]);
        $data['requires_redaction'] = $request->boolean('requires_redaction');
        $data['flagged_by'] = $request->user()?->id;

        $this->readiness->createBiasEntry($data);

        return redirect()->route('ai-governance.bias-register')->with('success', 'Bias entry created.');
    }

    public function biasEntryEdit(int $id): View|RedirectResponse
    {
        $entry = $this->readiness->getBiasEntry($id);
        if (!$entry) {
            return redirect()->route('ai-governance.bias-register')->with('error', 'Entry not found.');
        }

        $riskTypes = $this->readiness->getRiskTypes();
        $severityLevels = $this->readiness->getSeverityLevels();

        return view('ai-governance::bias-entry', [
            'editing' => $entry,
            'riskTypes' => $riskTypes,
            'severityLevels' => $severityLevels,
            'mode' => 'edit',
        ]);
    }

    public function biasEntryUpdate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'risk_type' => 'required|string|max:100',
            'severity' => 'required|string|in:low,medium,high,critical',
            'description' => 'required|string',
        ]);

        $data = $request->only([
            'entity_iri', 'collection_iri', 'risk_type', 'severity',
            'description', 'specific_content', 'mitigation_notes', 'ai_warning',
        ]);
        $data['requires_redaction'] = $request->boolean('requires_redaction');

        $this->readiness->updateBiasEntry($id, $data);

        return redirect()->route('ai-governance.bias-register')->with('success', 'Bias entry updated.');
    }

    public function biasEntryResolve(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'resolution_notes' => 'required|string',
        ]);

        $this->readiness->resolveBiasEntry($id, (int) $request->user()?->id, $request->input('resolution_notes'));

        return redirect()->route('ai-governance.bias-register')->with('success', 'Bias entry resolved.');
    }

    public function biasEntryDelete(int $id): RedirectResponse
    {
        $this->readiness->deleteBiasEntry($id);

        return redirect()->route('ai-governance.bias-register')->with('success', 'Bias entry deleted.');
    }

    // ════════════════════════════════════════════════════════════════
    //  Module 6: Derivatives
    // ════════════════════════════════════════════════════════════════

    public function derivatives(Request $request): View
    {
        $filters = $request->only(['derivative_type', 'format', 'is_current', 'entity_iri', 'language']);
        $page = max(1, (int) $request->get('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $data = $this->readiness->listDerivatives($filters, $limit, $offset);
        $derivativeTypes = $this->readiness->getDerivativeTypes();
        $derivativeFormats = $this->readiness->getDerivativeFormats();
        $derivativeStats = $this->readiness->getDerivativeStats();

        return view('ai-governance::derivatives', [
            'items' => $data['results'],
            'total' => $data['total'],
            'page' => $page,
            'limit' => $limit,
            'filters' => $filters,
            'derivativeTypes' => $derivativeTypes,
            'derivativeFormats' => $derivativeFormats,
            'derivativeStats' => $derivativeStats,
        ]);
    }

    public function derivativeCreate(): View
    {
        $derivativeTypes = $this->readiness->getDerivativeTypes();
        $derivativeFormats = $this->readiness->getDerivativeFormats();

        return view('ai-governance::derivatives', [
            'items' => collect([]),
            'total' => 0,
            'page' => 1,
            'limit' => 25,
            'filters' => [],
            'derivativeTypes' => $derivativeTypes,
            'derivativeFormats' => $derivativeFormats,
            'derivativeStats' => [],
            'mode' => 'create',
        ]);
    }

    public function derivativeStore(Request $request): RedirectResponse
    {
        $request->validate([
            'entity_iri' => 'required|string|max:1000',
            'derivative_type' => 'required|string|max:100',
            'format' => 'required|string|max:50',
            'file_path' => 'required|string|max:1000',
        ]);

        $data = $request->only([
            'entity_iri', 'derivative_type', 'format', 'file_path',
            'file_size_bytes', 'checksum_sha256', 'source_file_path',
            'source_checksum_sha256', 'processing_notes', 'processing_tool', 'language',
        ]);
        $data['created_by'] = $request->user()?->id;

        $this->readiness->createDerivative($data);

        return redirect()->route('ai-governance.derivatives')->with('success', 'Derivative created.');
    }

    public function derivativeDelete(int $id): RedirectResponse
    {
        $this->readiness->deleteDerivative($id);

        return redirect()->route('ai-governance.derivatives')->with('success', 'Derivative deleted.');
    }

    // ════════════════════════════════════════════════════════════════
    //  Module 7: Multilingual Config
    // ════════════════════════════════════════════════════════════════

    public function multilingual(): View
    {
        $configs = $this->evaluation->listLanguageConfigs();

        return view('ai-governance::multilingual', [
            'configs' => $configs,
        ]);
    }

    public function multilingualSave(Request $request, string $languageCode): RedirectResponse
    {
        $request->validate([
            'language_name' => 'required|string|max:100',
        ]);

        $data = [
            'language_name' => $request->input('language_name'),
            'embedding_enabled' => $request->boolean('embedding_enabled'),
            'translation_enabled' => $request->boolean('translation_enabled'),
            'rag_enabled' => $request->boolean('rag_enabled'),
            'ocr_enabled' => $request->boolean('ocr_enabled'),
            'sensitivity_scan_enabled' => $request->boolean('sensitivity_scan_enabled'),
            'embedding_model' => $request->input('embedding_model'),
            'translation_model' => $request->input('translation_model'),
            'access_warnings' => $request->input('access_warnings'),
        ];

        if ($request->filled('reviewer_user_ids')) {
            $data['reviewer_user_ids'] = array_map('intval', array_filter(explode(',', $request->input('reviewer_user_ids'))));
        }

        $this->evaluation->saveLanguageConfig($languageCode, $data);

        return redirect()->route('ai-governance.multilingual')->with('success', "Language config for '{$languageCode}' saved.");
    }

    // ════════════════════════════════════════════════════════════════
    //  Module 8: Readiness Checklist
    // ════════════════════════════════════════════════════════════════

    public function readinessChecklist(Request $request): View
    {
        $filters = $request->only(['status', 'search']);
        $page = max(1, (int) $request->get('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $data = $this->readiness->listChecklists($filters, $limit, $offset);
        $statusOptions = $this->readiness->getChecklistStatusOptions();

        return view('ai-governance::readiness-checklist', [
            'items' => $data['results'],
            'total' => $data['total'],
            'page' => $page,
            'limit' => $limit,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'editing' => null,
            'mode' => 'list',
            'autoChecks' => [],
        ]);
    }

    public function readinessChecklistCreate(): View
    {
        $statusOptions = $this->readiness->getChecklistStatusOptions();

        return view('ai-governance::readiness-checklist', [
            'items' => collect([]),
            'total' => 0,
            'page' => 1,
            'limit' => 25,
            'filters' => [],
            'statusOptions' => $statusOptions,
            'editing' => null,
            'mode' => 'create',
            'autoChecks' => [],
        ]);
    }

    public function readinessChecklistStore(Request $request): RedirectResponse
    {
        $request->validate([
            'project_name' => 'required|string|max:255',
            'use_case' => 'required|string|max:255',
        ]);

        $data = $request->only([
            'project_name', 'project_description', 'use_case',
            'use_case_notes', 'metadata_notes', 'access_rules_notes',
            'derivatives_notes', 'evaluation_plan', 'workflow_notes',
            'readiness_profile_id',
        ]);
        $data['use_case_defined'] = $request->boolean('use_case_defined');
        $data['corpus_completeness_documented'] = $request->boolean('corpus_completeness_documented');
        $data['metadata_minimum_met'] = $request->boolean('metadata_minimum_met');
        $data['access_rules_structured'] = $request->boolean('access_rules_structured');
        $data['derivatives_prepared'] = $request->boolean('derivatives_prepared');
        $data['evaluation_plan_approved'] = $request->boolean('evaluation_plan_approved');
        $data['human_review_workflow_active'] = $request->boolean('human_review_workflow_active');
        $data['created_by'] = $request->user()?->id;

        $this->readiness->createChecklist($data);

        return redirect()->route('ai-governance.readiness-checklist')->with('success', 'Checklist created.');
    }

    public function readinessChecklistEdit(int $id): View|RedirectResponse
    {
        $checklist = $this->readiness->getChecklist($id);
        if (!$checklist) {
            return redirect()->route('ai-governance.readiness-checklist')->with('error', 'Checklist not found.');
        }

        $statusOptions = $this->readiness->getChecklistStatusOptions();
        $autoChecks = $this->readiness->computeChecklistAutoChecks($id);

        return view('ai-governance::readiness-checklist', [
            'items' => collect([]),
            'total' => 0,
            'page' => 1,
            'limit' => 25,
            'filters' => [],
            'statusOptions' => $statusOptions,
            'editing' => $checklist,
            'mode' => 'edit',
            'autoChecks' => $autoChecks,
        ]);
    }

    public function readinessChecklistUpdate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'project_name' => 'required|string|max:255',
            'use_case' => 'required|string|max:255',
        ]);

        $data = $request->only([
            'project_name', 'project_description', 'use_case',
            'use_case_notes', 'metadata_notes', 'access_rules_notes',
            'derivatives_notes', 'evaluation_plan', 'workflow_notes',
            'readiness_profile_id',
        ]);
        $data['use_case_defined'] = $request->boolean('use_case_defined');
        $data['corpus_completeness_documented'] = $request->boolean('corpus_completeness_documented');
        $data['metadata_minimum_met'] = $request->boolean('metadata_minimum_met');
        $data['access_rules_structured'] = $request->boolean('access_rules_structured');
        $data['derivatives_prepared'] = $request->boolean('derivatives_prepared');
        $data['evaluation_plan_approved'] = $request->boolean('evaluation_plan_approved');
        $data['human_review_workflow_active'] = $request->boolean('human_review_workflow_active');

        $this->readiness->updateChecklist($id, $data);

        return redirect()->route('ai-governance.readiness-checklist')->with('success', 'Checklist updated.');
    }

    public function readinessChecklistApprove(Request $request, int $id): RedirectResponse
    {
        $this->readiness->approveChecklist($id, (int) $request->user()?->id);

        return redirect()->route('ai-governance.readiness-checklist.edit', $id)->with('success', 'Checklist approved.');
    }

    public function readinessChecklistAutoCheck(int $id): RedirectResponse
    {
        $autoChecks = $this->readiness->computeChecklistAutoChecks($id);

        if (!empty($autoChecks)) {
            $this->readiness->updateChecklist($id, $autoChecks);
        }

        return redirect()->route('ai-governance.readiness-checklist.edit', $id)->with('success', 'Auto-checks applied: ' . count($autoChecks) . ' items checked.');
    }
}
