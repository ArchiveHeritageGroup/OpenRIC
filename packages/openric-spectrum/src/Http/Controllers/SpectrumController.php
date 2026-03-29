<?php

declare(strict_types=1);

namespace OpenRiC\Spectrum\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenRiC\Spectrum\Contracts\SpectrumServiceInterface;
use OpenRiC\Spectrum\Services\SpectrumService;

/**
 * Full Spectrum 5.1 controller — every action method for all 21 procedures,
 * dashboard, workflow, condition management, data quality, export, and more.
 *
 * Adapted from Heratio AhgSpectrum\Controllers\SpectrumController.
 */
class SpectrumController extends Controller
{
    public function __construct(
        protected SpectrumServiceInterface $spectrum,
    ) {}

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    protected function getCulture(): string
    {
        return app()->getLocale() ?: 'en';
    }

    /**
     * Resolve an information_object by its slug, with i18n title and repository name.
     */
    protected function getResourceBySlug(string $slug): ?object
    {
        $culture = $this->getCulture();

        $slugRecord = DB::table('slug')->where('slug', $slug)->first();
        if (!$slugRecord) {
            return null;
        }

        $resource = DB::table('information_object')->where('id', $slugRecord->object_id)->first();
        if (!$resource) {
            return null;
        }

        $resource->slug = $slug;

        $i18n = DB::table('information_object_i18n')
            ->where('id', $resource->id)
            ->where('culture', $culture)
            ->first();
        $resource->title = $i18n->title ?? null;

        if ($resource->repository_id) {
            $repoI18n = DB::table('actor_i18n')
                ->where('id', $resource->repository_id)
                ->where('culture', $culture)
                ->first();
            $resource->repositoryName = $repoI18n->authorized_form_of_name ?? null;
        }

        return $resource;
    }

    // ------------------------------------------------------------------
    // Per-object Spectrum index
    // ------------------------------------------------------------------

    public function index(Request $request)
    {
        $slug = $request->query('slug');
        $resource = null;
        $museumData = [];
        $grapData = null;

        if ($slug) {
            $resource = $this->getResourceBySlug($slug);
            if (!$resource) {
                abort(404);
            }

            if (Schema::hasTable('museum_metadata')) {
                $mm = DB::table('museum_metadata')->where('object_id', $resource->id)->first();
                if ($mm) {
                    $museumData = (array) $mm;
                }
            }

            if (Schema::hasTable('grap_heritage_asset')) {
                $grapData = DB::table('grap_heritage_asset')->where('object_id', $resource->id)->first();
            }
        }

        return view('spectrum::index', [
            'resource'   => $resource,
            'procedures' => $this->spectrum->getProcedures(),
            'museumData' => $museumData,
            'grapData'   => $grapData,
        ]);
    }

    // ------------------------------------------------------------------
    // Dashboard
    // ------------------------------------------------------------------

    public function dashboard(Request $request)
    {
        $repoId = $request->query('repository') ? (int) $request->query('repository') : null;

        return view('spectrum::dashboard', [
            'procedures'            => $this->spectrum->getProcedures(),
            'workflowStats'         => $this->spectrum->getWorkflowStatistics($repoId),
            'recentActivity'        => $this->spectrum->getRecentWorkflowActivity($repoId),
            'procedureStatusCounts' => $this->spectrum->getProcedureStatusCounts($repoId),
            'overallCompletion'     => $this->spectrum->calculateOverallCompletion($repoId),
            'repositories'          => $this->spectrum->getRepositoriesForFilter(),
            'selectedRepository'    => $request->query('repository', ''),
        ]);
    }

    // ------------------------------------------------------------------
    // Workflow (per-object)
    // ------------------------------------------------------------------

    public function workflow(Request $request)
    {
        // Handle POST (transition execution)
        if ($request->isMethod('post')) {
            return $this->executeWorkflowTransition($request);
        }

        $slug = $request->query('slug');
        $procedureType = $request->query('procedure_type', SpectrumService::PROC_ACQUISITION);

        $resource = null;
        $procedures = $this->spectrum->getProcedures();
        $procedureStatuses = [];
        $currentProcedure = null;
        $timeline = [];
        $procedureTimeline = [];
        $progress = ['total' => 0, 'completed' => 0, 'inProgress' => 0, 'overdue' => 0, 'notStarted' => 0, 'percentComplete' => 0];
        $canEdit = false;

        if ($slug) {
            $resource = $this->getResourceBySlug($slug);
            if (!$resource) {
                abort(404);
            }

            $procedureStatuses = $this->spectrum->getWorkflowStatesForRecord($resource->id);
            $currentProcedure = $procedureStatuses[$procedureType] ?? null;

            // Timeline from workflow_history
            if (Schema::hasTable('spectrum_workflow_history')) {
                $history = DB::table('spectrum_workflow_history as h')
                    ->leftJoin('user as u', 'h.user_id', '=', 'u.id')
                    ->where('h.record_id', $resource->id)
                    ->select('h.*', 'u.username as user_name')
                    ->orderBy('h.created_at', 'desc')
                    ->get()
                    ->toArray();

                $timeline = $history;
                $procedureTimeline = array_filter($history, fn($e) => $e->procedure_type === $procedureType);
            }

            // Progress calculation
            $total = count($procedures);
            $completed = 0;
            $inProgress = 0;
            $overdue = 0;
            foreach ($procedures as $procId => $procDef) {
                $st = $procedureStatuses[$procId] ?? null;
                if ($st) {
                    $cs = $st->current_state;
                    if (in_array($cs, ['completed', 'verified', 'closed', 'confirmed'])) {
                        $completed++;
                    } elseif (in_array($cs, ['in_progress', 'pending_review'])) {
                        $inProgress++;
                    }
                }
            }
            $progress = [
                'total'           => $total,
                'completed'       => $completed,
                'inProgress'      => $inProgress,
                'overdue'         => $overdue,
                'notStarted'      => $total - $completed - $inProgress - $overdue,
                'percentComplete' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            ];

            $canEdit = Auth::check();
        }

        // Status options
        $statusOptions = $this->spectrum->getStatusLabels();

        // Workflow config for available transitions
        $workflowConfig = $this->spectrum->getWorkflowConfig($procedureType);

        // Users for assignment dropdown
        $users = DB::table('user')->select('id', 'username', 'email')->orderBy('username')->get();

        return view('spectrum::workflow', [
            'resource'            => $resource,
            'procedureType'       => $procedureType,
            'procedures'          => $procedures,
            'procedureStatuses'   => $procedureStatuses,
            'currentProcedure'    => $currentProcedure,
            'timeline'            => $timeline,
            'procedureTimeline'   => $procedureTimeline,
            'progress'            => $progress,
            'statusOptions'       => $statusOptions,
            'statusColors'        => $this->spectrum->getStatusColors(),
            'canEdit'             => $canEdit,
            'workflowConfig'      => $workflowConfig,
            'users'               => $users,
        ]);
    }

    /**
     * Execute a workflow transition (POST handler).
     */
    protected function executeWorkflowTransition(Request $request)
    {
        $request->validate([
            'slug'           => 'required|string',
            'procedure_type' => 'required|string',
            'transition_key' => 'required|string',
            'from_state'     => 'required|string',
        ]);

        $slug = $request->input('slug');
        $procedureType = $request->input('procedure_type');
        $transitionKey = $request->input('transition_key');
        $fromState = $request->input('from_state');
        $assignedTo = $request->input('assigned_to') ? (int) $request->input('assigned_to') : null;
        $note = $request->input('note');

        $resource = $this->getResourceBySlug($slug);
        if (!$resource) {
            abort(404);
        }

        // Resolve the to_state from the workflow config
        $config = $this->spectrum->getWorkflowConfig($procedureType);
        $transitions = $config['transitions'] ?? [];
        $toState = $transitions[$transitionKey]['to'] ?? $fromState;

        $this->spectrum->executeTransition(
            $resource->id,
            $procedureType,
            $transitionKey,
            $fromState,
            $toState,
            $assignedTo,
            $note
        );

        return redirect()
            ->route('spectrum.workflow', ['slug' => $slug, 'procedure_type' => $procedureType])
            ->with('success', 'Workflow transition executed successfully.');
    }

    // ------------------------------------------------------------------
    // General Procedures (institution-level, record_id = 0)
    // ------------------------------------------------------------------

    public function general(Request $request)
    {
        $procedures = $this->spectrum->getProcedures();
        $procedureStatuses = [];
        $recentHistory = [];

        if (Schema::hasTable('spectrum_workflow_state')) {
            $states = DB::table('spectrum_workflow_state')->where('record_id', 0)->get();
            foreach ($states as $state) {
                $procedureStatuses[$state->procedure_type] = $state->current_state;
            }
        }

        if (Schema::hasTable('spectrum_workflow_history')) {
            $recentHistory = DB::table('spectrum_workflow_history as h')
                ->leftJoin('user as u', 'h.user_id', '=', 'u.id')
                ->where('h.record_id', 0)
                ->select('h.*', 'u.username as user_name')
                ->orderBy('h.created_at', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
        }

        return view('spectrum::general', [
            'procedures'        => $procedures,
            'procedureStatuses' => $procedureStatuses,
            'recentHistory'     => $recentHistory,
        ]);
    }

    public function generalWorkflow(Request $request)
    {
        // Handle POST (transition execution for general procedures)
        if ($request->isMethod('post')) {
            return $this->executeGeneralWorkflowTransition($request);
        }

        $procedureType = $request->query('procedure_type', SpectrumService::PROC_ACQUISITION);
        $procedures = $this->spectrum->getProcedures();
        $canEdit = Auth::check();

        $workflowConfig = $this->spectrum->getWorkflowConfig($procedureType);
        $currentState = null;
        $history = [];

        if (Schema::hasTable('spectrum_workflow_state')) {
            $ws = DB::table('spectrum_workflow_state')
                ->where('record_id', 0)
                ->where('procedure_type', $procedureType)
                ->first();
            $currentState = $ws->current_state ?? null;
        }

        if (Schema::hasTable('spectrum_workflow_history')) {
            $history = DB::table('spectrum_workflow_history as h')
                ->leftJoin('user as u', 'h.user_id', '=', 'u.id')
                ->where('h.record_id', 0)
                ->where('h.procedure_type', $procedureType)
                ->select('h.*', 'u.username as user_name')
                ->orderBy('h.created_at', 'desc')
                ->get()
                ->toArray();
        }

        $users = DB::table('user')->select('id', 'username', 'email')->orderBy('username')->get();

        return view('spectrum::general-workflow', [
            'procedureType'  => $procedureType,
            'procedures'     => $procedures,
            'isGeneral'      => true,
            'recordId'       => 0,
            'canEdit'        => $canEdit,
            'workflowConfig' => $workflowConfig,
            'currentState'   => $currentState,
            'history'        => $history,
            'users'          => $users,
            'statusColors'   => $this->spectrum->getStatusColors(),
        ]);
    }

    /**
     * Execute a general-level workflow transition (POST handler).
     */
    protected function executeGeneralWorkflowTransition(Request $request)
    {
        $request->validate([
            'procedure_type' => 'required|string',
            'transition_key' => 'required|string',
            'from_state'     => 'required|string',
        ]);

        $procedureType = $request->input('procedure_type');
        $transitionKey = $request->input('transition_key');
        $fromState = $request->input('from_state');
        $assignedTo = $request->input('assigned_to') ? (int) $request->input('assigned_to') : null;
        $note = $request->input('note');

        $config = $this->spectrum->getWorkflowConfig($procedureType);
        $transitions = $config['transitions'] ?? [];
        $toState = $transitions[$transitionKey]['to'] ?? $fromState;

        $this->spectrum->executeTransition(0, $procedureType, $transitionKey, $fromState, $toState, $assignedTo, $note);

        return redirect()
            ->route('spectrum.general-workflow', ['procedure_type' => $procedureType])
            ->with('success', 'General workflow transition executed successfully.');
    }

    // ------------------------------------------------------------------
    // My Tasks
    // ------------------------------------------------------------------

    public function myTasks(Request $request)
    {
        $userId = Auth::id();
        $procedureTypeFilter = $request->query('procedure_type');

        $workflowConfigs = [];
        if (Schema::hasTable('spectrum_workflow_config')) {
            $configs = DB::table('spectrum_workflow_config')->where('is_active', true)->get();
            foreach ($configs as $config) {
                $workflowConfigs[$config->procedure_type] = json_decode($config->config_json, true);
            }
        }

        return view('spectrum::my-tasks', [
            'tasks'           => $this->spectrum->getTasksForUser($userId, $procedureTypeFilter),
            'procedures'      => $this->spectrum->getProcedures(),
            'workflowConfigs' => $workflowConfigs,
            'unreadCount'     => $this->spectrum->getUnreadNotificationCount($userId),
            'procedureTypes'  => $this->spectrum->getAssignedProcedureTypes($userId),
            'currentFilter'   => $procedureTypeFilter,
            'statusColors'    => $this->spectrum->getStatusColors(),
        ]);
    }

    // ------------------------------------------------------------------
    // Label
    // ------------------------------------------------------------------

    public function label(Request $request)
    {
        $slug = $request->query('slug');
        $resource = null;

        if ($slug) {
            $resource = $this->getResourceBySlug($slug);
            if (!$resource) {
                abort(404);
            }
        }

        return view('spectrum::label', [
            'resource'  => $resource,
            'labelType' => $request->query('type', 'full'),
            'labelSize' => $request->query('size', 'medium'),
        ]);
    }

    // ------------------------------------------------------------------
    // Object Entry browse
    // ------------------------------------------------------------------

    public function objectEntry(Request $request)
    {
        return view('spectrum::object-entry', [
            'entries' => $this->spectrum->getObjectEntries(),
        ]);
    }

    // ------------------------------------------------------------------
    // Acquisitions browse
    // ------------------------------------------------------------------

    public function acquisitions(Request $request)
    {
        return view('spectrum::acquisitions', [
            'acquisitions' => $this->spectrum->getAcquisitions(),
        ]);
    }

    // ------------------------------------------------------------------
    // Loans browse
    // ------------------------------------------------------------------

    public function loans(Request $request)
    {
        $loans = $this->spectrum->getLoans();

        return view('spectrum::loans', [
            'loansIn'  => $loans['loansIn'],
            'loansOut' => $loans['loansOut'],
        ]);
    }

    // ------------------------------------------------------------------
    // Movements browse
    // ------------------------------------------------------------------

    public function movements(Request $request)
    {
        return view('spectrum::movements', [
            'movements' => $this->spectrum->getMovements(),
        ]);
    }

    // ------------------------------------------------------------------
    // Conditions browse
    // ------------------------------------------------------------------

    public function conditions(Request $request)
    {
        return view('spectrum::conditions', [
            'checks' => $this->spectrum->getConditionChecks(),
        ]);
    }

    // ------------------------------------------------------------------
    // Conservation browse
    // ------------------------------------------------------------------

    public function conservation(Request $request)
    {
        return view('spectrum::conservation', [
            'treatments' => $this->spectrum->getConservationTreatments(),
        ]);
    }

    // ------------------------------------------------------------------
    // Valuations browse
    // ------------------------------------------------------------------

    public function valuations(Request $request)
    {
        return view('spectrum::valuations', [
            'valuations' => $this->spectrum->getValuations(),
        ]);
    }

    // ------------------------------------------------------------------
    // Condition Admin
    // ------------------------------------------------------------------

    public function conditionAdmin(Request $request)
    {
        $culture = $this->getCulture();
        $recentEvents = [];
        $stats = $this->spectrum->getConditionStats();
        $pendingScheduled = [];

        if (Schema::hasTable('spectrum_condition_check')) {
            $recentEvents = DB::table('spectrum_condition_check as c')
                ->leftJoin('information_object as io', 'c.object_id', '=', 'io.id')
                ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                    $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->select('c.*', 'i18n.title', 's.slug')
                ->orderBy('c.check_date', 'desc')
                ->limit(20)
                ->get()
                ->toArray();

            $pendingScheduled = DB::table('spectrum_condition_check')
                ->where('workflow_state', 'scheduled')
                ->whereNotNull('next_check_date')
                ->where('next_check_date', '<=', now()->addDays(30))
                ->orderBy('next_check_date')
                ->limit(10)
                ->get()
                ->toArray();
        }

        return view('spectrum::condition-admin', [
            'recentEvents'     => $recentEvents,
            'stats'            => $stats,
            'pendingScheduled' => $pendingScheduled,
        ]);
    }

    // ------------------------------------------------------------------
    // Condition Photos
    // ------------------------------------------------------------------

    public function conditionPhotos(Request $request)
    {
        $slug = $request->query('slug');
        $resource = null;
        $conditionCheck = null;
        $conditionCheckId = $request->query('condition_id');
        $photos = [];
        $photosByType = [];
        $conditionChecks = [];

        if ($slug) {
            $resource = $this->getResourceBySlug($slug);
            if (!$resource) {
                abort(404);
            }

            if (Schema::hasTable('spectrum_condition_check')) {
                if ($conditionCheckId) {
                    $conditionCheck = DB::table('spectrum_condition_check')
                        ->where('id', $conditionCheckId)
                        ->first();
                }
                if (!$conditionCheck) {
                    $conditionCheck = $this->spectrum->getOrCreateConditionCheck($resource->id);
                }
                if ($conditionCheck) {
                    $conditionCheckId = $conditionCheck->id;
                }

                $conditionChecks = $this->spectrum->getConditionChecksForObject($resource->id);
            }

            if ($conditionCheckId) {
                $photoData = $this->spectrum->getConditionPhotos((int) $conditionCheckId);
                $photos = $photoData['photos'];
                $photosByType = $photoData['photosByType'];
            }
        }

        return view('spectrum::condition-photos', [
            'resource'         => $resource,
            'conditionCheck'   => $conditionCheck ? (array) $conditionCheck : null,
            'conditionCheckId' => $conditionCheckId,
            'photos'           => $photos,
            'photosByType'     => $photosByType,
            'photoTypes'       => SpectrumService::PHOTO_TYPES,
            'conditionChecks'  => $conditionChecks,
        ]);
    }

    // ------------------------------------------------------------------
    // Condition Risk
    // ------------------------------------------------------------------

    public function conditionRisk(Request $request)
    {
        return view('spectrum::condition-risk', [
            'riskItems'  => $this->spectrum->getRiskItems(),
            'riskMatrix' => [],
            'trends'     => [],
        ]);
    }

    // ------------------------------------------------------------------
    // Data Quality
    // ------------------------------------------------------------------

    public function dataQuality(Request $request)
    {
        $metrics = $this->spectrum->getDataQualityMetrics();

        return view('spectrum::data-quality', $metrics);
    }

    // ------------------------------------------------------------------
    // GRAP Dashboard
    // ------------------------------------------------------------------

    public function grapDashboard(Request $request)
    {
        $slug = $request->query('slug');
        $resource = null;
        $grapData = null;
        $totalAssets = 0;
        $valuedAssets = 0;
        $pendingValuation = 0;
        $totalValue = 0;
        $assetRegisterComplete = false;
        $valuationsCurrent = false;
        $conditionComplete = false;
        $depreciationRecorded = false;
        $insuranceComplete = false;

        if ($slug) {
            $resource = $this->getResourceBySlug($slug);
            if (!$resource) {
                abort(404);
            }

            if (Schema::hasTable('grap_heritage_asset')) {
                $grapData = DB::table('grap_heritage_asset')
                    ->where('object_id', $resource->id)
                    ->first();
            }

            if (Schema::hasTable('spectrum_grap_data')) {
                $gd = DB::table('spectrum_grap_data')
                    ->where('information_object_id', $resource->id)
                    ->first();

                if ($gd) {
                    $totalAssets = 1;
                    $valuedAssets = $gd->carrying_amount ? 1 : 0;
                    $pendingValuation = $gd->carrying_amount ? 0 : 1;
                    $totalValue = (float) ($gd->carrying_amount ?? 0);
                    $assetRegisterComplete = (bool) $gd->initial_recognition_date;
                    $valuationsCurrent = $gd->last_revaluation_date && strtotime($gd->last_revaluation_date) > strtotime('-5 years');
                    $insuranceComplete = (float) ($gd->insurance_coverage_actual ?? 0) > 0;
                    $depreciationRecorded = (float) ($gd->accumulated_depreciation ?? 0) > 0;
                }
            }
        }

        // Summary stats for institution-level GRAP dashboard (no slug)
        $grapSummary = [];
        if (!$slug && Schema::hasTable('spectrum_grap_data')) {
            $grapSummary = [
                'total_assets'       => DB::table('spectrum_grap_data')->count(),
                'total_value'        => DB::table('spectrum_grap_data')->sum('carrying_amount'),
                'pending_valuation'  => DB::table('spectrum_grap_data')->whereNull('last_revaluation_date')->count(),
                'depreciation_total' => DB::table('spectrum_grap_data')->sum('accumulated_depreciation'),
            ];
        }

        return view('spectrum::grap-dashboard', [
            'resource'              => $resource,
            'grapData'              => $grapData,
            'grapSummary'           => $grapSummary,
            'totalAssets'           => $totalAssets,
            'valuedAssets'          => $valuedAssets,
            'pendingValuation'      => $pendingValuation,
            'totalValue'            => $totalValue,
            'assetRegisterComplete' => $assetRegisterComplete,
            'valuationsCurrent'     => $valuationsCurrent,
            'conditionComplete'     => $conditionComplete,
            'depreciationRecorded'  => $depreciationRecorded,
            'insuranceComplete'     => $insuranceComplete,
        ]);
    }

    // ------------------------------------------------------------------
    // Export
    // ------------------------------------------------------------------

    public function export(Request $request)
    {
        $format = $request->query('format', 'csv');
        $type = $request->query('type', 'condition');
        $slug = $request->query('slug');

        // If download requested, stream the file
        if ($request->query('download')) {
            $objectId = null;
            if ($slug) {
                $slugRecord = DB::table('slug')->where('slug', $slug)->first();
                $objectId = $slugRecord->object_id ?? null;
            }

            return $this->spectrum->exportData($format, $type, $objectId);
        }

        $exportTypes = [
            'condition' => 'Condition Check History',
            'valuation' => 'Valuation History',
            'movement'  => 'Movement/Location History',
            'loan'      => 'Loan History',
            'workflow'  => 'Workflow History',
        ];

        $objectId = null;
        $identifier = null;

        if ($slug) {
            $slugRecord = DB::table('slug')->where('slug', $slug)->first();
            if ($slugRecord) {
                $objectId = $slugRecord->object_id;
                $object = DB::table('information_object as io')
                    ->leftJoin('information_object_i18n as i18n', function ($j) {
                        $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->where('io.id', $objectId)
                    ->select('io.identifier', 'i18n.title')
                    ->first();
                $identifier = $object ? ($object->title ?: $object->identifier) : $slug;
            }
        }

        $counts = [
            'movements'  => Schema::hasTable('spectrum_movement') ? DB::table('spectrum_movement')->when($objectId, fn($q) => $q->where('object_id', $objectId))->count() : 0,
            'conditions' => Schema::hasTable('spectrum_condition_check') ? DB::table('spectrum_condition_check')->when($objectId, fn($q) => $q->where('object_id', $objectId))->count() : 0,
            'valuations' => Schema::hasTable('spectrum_valuation') ? DB::table('spectrum_valuation')->when($objectId, fn($q) => $q->where('object_id', $objectId))->count() : 0,
            'loansIn'    => Schema::hasTable('spectrum_loan_in') ? DB::table('spectrum_loan_in')->when($objectId, fn($q) => $q->where('object_id', $objectId))->count() : 0,
            'loansOut'   => Schema::hasTable('spectrum_loan_out') ? DB::table('spectrum_loan_out')->when($objectId, fn($q) => $q->where('object_id', $objectId))->count() : 0,
        ];

        return view('spectrum::export', [
            'exportTypes' => $exportTypes,
            'format'      => $format,
            'slug'        => $slug,
            'identifier'  => $identifier,
            'counts'      => $counts,
        ]);
    }

    public function spectrumExport(Request $request)
    {
        return $this->export($request);
    }

    // ------------------------------------------------------------------
    // Security Compliance
    // ------------------------------------------------------------------

    public function securityCompliance(Request $request)
    {
        $stats = [
            'classified_objects' => 0,
            'pending_reviews'    => 0,
            'cleared_users'      => 0,
            'access_logs_today'  => 0,
        ];
        $retentionSchedules = [];
        $recentLogs = [];

        if (Schema::hasTable('security_classification')) {
            $stats['classified_objects'] = DB::table('security_classification')->count();
        }
        if (Schema::hasTable('security_clearance_history')) {
            $stats['cleared_users'] = DB::table('security_clearance_history')->where('action', 'granted')->count();
        }
        if (Schema::hasTable('security_access_log')) {
            $stats['access_logs_today'] = DB::table('security_access_log')
                ->whereDate('created_at', date('Y-m-d'))
                ->count();
        }
        if (Schema::hasTable('security_retention_schedule')) {
            $retentionSchedules = DB::table('security_retention_schedule')->get()->toArray();
        }
        if (Schema::hasTable('security_compliance_log')) {
            $recentLogs = DB::table('security_compliance_log')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
        }

        return view('spectrum::security-compliance', [
            'stats'              => $stats,
            'pendingReviews'     => [],
            'retentionSchedules' => $retentionSchedules,
            'recentLogs'         => $recentLogs,
        ]);
    }

    // ------------------------------------------------------------------
    // Privacy Compliance
    // ------------------------------------------------------------------

    public function privacyCompliance(Request $request)
    {
        $complianceScore = 75;
        $ropaCount = 0;
        $dsarStats = ['total' => 0, 'pending' => 0, 'overdue' => 0, 'completed' => 0];
        $breachStats = ['total' => 0, 'open' => 0, 'notified' => 0, 'closed' => 0];

        if (Schema::hasTable('privacy_processing_activity')) {
            $ropaCount = DB::table('privacy_processing_activity')->count();
        }
        if (Schema::hasTable('privacy_dsar_request')) {
            $dsarStats = [
                'total'     => DB::table('privacy_dsar_request')->count(),
                'pending'   => DB::table('privacy_dsar_request')->where('status', 'pending')->count(),
                'overdue'   => DB::table('privacy_dsar_request')
                    ->where('status', '!=', 'completed')
                    ->where('deadline_date', '<', date('Y-m-d'))->count(),
                'completed' => DB::table('privacy_dsar_request')->where('status', 'completed')->count(),
            ];
        }
        if (Schema::hasTable('privacy_breach_incident')) {
            $breachStats = [
                'total'    => DB::table('privacy_breach_incident')->count(),
                'open'     => DB::table('privacy_breach_incident')->where('status', 'open')->count(),
                'notified' => DB::table('privacy_breach_incident')->where('regulator_notified', true)->count(),
                'closed'   => DB::table('privacy_breach_incident')->where('status', 'closed')->count(),
            ];
        }

        return view('spectrum::privacy-compliance', [
            'complianceScore' => $complianceScore,
            'ropaCount'       => $ropaCount,
            'dsarStats'       => $dsarStats,
            'breachStats'     => $breachStats,
            'recentActivity'  => [],
        ]);
    }

    // ------------------------------------------------------------------
    // Privacy Admin
    // ------------------------------------------------------------------

    public function privacyAdmin(Request $request)
    {
        $complianceScore = 75;
        $ropaCount = 0;
        $dsarStats = ['pending' => 0, 'overdue' => 0, 'completed' => 0];
        $breachStats = ['open' => 0, 'closed' => 0];

        if (Schema::hasTable('privacy_processing_activity')) {
            $ropaCount = DB::table('privacy_processing_activity')->count();
        }
        if (Schema::hasTable('privacy_dsar_request')) {
            $dsarStats = [
                'pending'   => DB::table('privacy_dsar_request')->where('status', 'pending')->count(),
                'overdue'   => DB::table('privacy_dsar_request')
                    ->where('status', '!=', 'completed')
                    ->where('deadline_date', '<', date('Y-m-d'))->count(),
                'completed' => DB::table('privacy_dsar_request')->where('status', 'completed')->count(),
            ];
        }
        if (Schema::hasTable('privacy_breach_incident')) {
            $breachStats = [
                'open'   => DB::table('privacy_breach_incident')->where('status', 'open')->count(),
                'closed' => DB::table('privacy_breach_incident')->where('status', 'closed')->count(),
            ];
        }

        return view('spectrum::privacy-admin', [
            'complianceScore' => $complianceScore,
            'ropaCount'       => $ropaCount,
            'dsarStats'       => $dsarStats,
            'breachStats'     => $breachStats,
        ]);
    }

    // ------------------------------------------------------------------
    // Privacy ROPA
    // ------------------------------------------------------------------

    public function privacyRopa(Request $request)
    {
        $activities = [];

        if (Schema::hasTable('privacy_processing_activity')) {
            $activities = DB::table('privacy_processing_activity')
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        }

        return view('spectrum::privacy-ropa', [
            'activities' => $activities,
        ]);
    }

    // ------------------------------------------------------------------
    // Privacy DSAR
    // ------------------------------------------------------------------

    public function privacyDsar(Request $request)
    {
        $requests = [];
        $stats = ['total' => 0, 'pending' => 0, 'overdue' => 0, 'completed' => 0];

        if (Schema::hasTable('privacy_dsar_request')) {
            $requests = DB::table('privacy_dsar_request')
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();

            $stats = [
                'total'     => count($requests),
                'pending'   => DB::table('privacy_dsar_request')->where('status', 'pending')->count(),
                'overdue'   => DB::table('privacy_dsar_request')
                    ->where('status', '!=', 'completed')
                    ->where('deadline_date', '<', date('Y-m-d'))->count(),
                'completed' => DB::table('privacy_dsar_request')->where('status', 'completed')->count(),
            ];
        }

        return view('spectrum::privacy-dsar', [
            'requests' => $requests,
            'stats'    => $stats,
        ]);
    }

    // ------------------------------------------------------------------
    // Privacy Breaches
    // ------------------------------------------------------------------

    public function privacyBreaches(Request $request)
    {
        $breaches = [];
        $stats = [];

        if (Schema::hasTable('privacy_breach_incident')) {
            $breaches = DB::table('privacy_breach_incident')
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();

            $stats = [
                'total'    => count($breaches),
                'open'     => DB::table('privacy_breach_incident')->where('status', 'open')->count(),
                'notified' => DB::table('privacy_breach_incident')->where('regulator_notified', true)->count(),
                'closed'   => DB::table('privacy_breach_incident')->where('status', 'closed')->count(),
            ];
        }

        return view('spectrum::privacy-breaches', [
            'breaches' => $breaches,
            'stats'    => $stats,
        ]);
    }

    // ------------------------------------------------------------------
    // Privacy Templates
    // ------------------------------------------------------------------

    public function privacyTemplates(Request $request)
    {
        $templates = [];

        if (Schema::hasTable('privacy_template')) {
            $templates = DB::table('privacy_template')
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->toArray();
        }

        return view('spectrum::privacy-templates', [
            'templates' => $templates,
        ]);
    }
}
