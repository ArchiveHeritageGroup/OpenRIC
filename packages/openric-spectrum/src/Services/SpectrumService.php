<?php

declare(strict_types=1);

namespace OpenRiC\Spectrum\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenRiC\Spectrum\Contracts\SpectrumServiceInterface;

/**
 * Full Spectrum 5.1 service — procedure definitions, status management,
 * condition checks, workflow execution, statistics, and data export.
 *
 * Adapted from Heratio ahg-spectrum for PostgreSQL / OpenRiC.
 */
class SpectrumService implements SpectrumServiceInterface
{
    // ------------------------------------------------------------------
    // Spectrum 5.1 procedure constants
    // ------------------------------------------------------------------

    public const PROC_OBJECT_ENTRY          = 'object_entry';
    public const PROC_ACQUISITION           = 'acquisition';
    public const PROC_LOCATION              = 'location_movement';
    public const PROC_INVENTORY             = 'inventory_control';
    public const PROC_CATALOGUING           = 'cataloguing';
    public const PROC_CONDITION             = 'condition_checking';
    public const PROC_CONSERVATION          = 'conservation';
    public const PROC_RISK                  = 'risk_management';
    public const PROC_INSURANCE             = 'insurance';
    public const PROC_VALUATION             = 'valuation';
    public const PROC_AUDIT                 = 'audit';
    public const PROC_RIGHTS               = 'rights_management';
    public const PROC_REPRODUCTION          = 'reproduction';
    public const PROC_LOAN_IN              = 'loans_in';
    public const PROC_LOAN_OUT             = 'loans_out';
    public const PROC_LOSS                 = 'loss_damage';
    public const PROC_DEACCESSION          = 'deaccession';
    public const PROC_DISPOSAL             = 'disposal';
    public const PROC_DOCUMENTATION        = 'documentation_planning';
    public const PROC_EXIT                 = 'object_exit';
    public const PROC_RETROSPECTIVE        = 'retrospective_documentation';

    // ------------------------------------------------------------------
    // Status constants
    // ------------------------------------------------------------------

    public const STATUS_NOT_STARTED    = 'not_started';
    public const STATUS_IN_PROGRESS    = 'in_progress';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_COMPLETED      = 'completed';
    public const STATUS_ON_HOLD        = 'on_hold';
    public const STATUS_OVERDUE        = 'overdue';

    // ------------------------------------------------------------------
    // Photo type constants
    // ------------------------------------------------------------------

    public const PHOTO_TYPES = [
        'overall' => 'Overall View',
        'detail'  => 'Detail',
        'damage'  => 'Damage/Deterioration',
        'before'  => 'Before Treatment',
        'after'   => 'After Treatment',
        'other'   => 'Other',
    ];

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    protected function getCulture(): string
    {
        return app()->getLocale() ?: 'en';
    }

    // ------------------------------------------------------------------
    // Procedure definitions
    // ------------------------------------------------------------------

    /** @inheritDoc */
    public function getProcedures(): array
    {
        return [
            self::PROC_OBJECT_ENTRY => [
                'label'       => 'Object Entry',
                'description' => 'Recording information about objects entering the museum temporarily or for acquisition consideration.',
                'category'    => 'pre-entry',
                'icon'        => 'fa-sign-in',
            ],
            self::PROC_ACQUISITION => [
                'label'       => 'Acquisition',
                'description' => 'Formally acquiring objects for the permanent collection.',
                'category'    => 'acquisition',
                'icon'        => 'fa-plus-circle',
            ],
            self::PROC_LOCATION => [
                'label'       => 'Location & Movement',
                'description' => 'Tracking object locations and movements within and outside the museum.',
                'category'    => 'location',
                'icon'        => 'fa-map-marker',
            ],
            self::PROC_INVENTORY => [
                'label'       => 'Inventory Control',
                'description' => 'Verifying and reconciling object locations and records.',
                'category'    => 'control',
                'icon'        => 'fa-list-alt',
            ],
            self::PROC_CATALOGUING => [
                'label'       => 'Cataloguing',
                'description' => 'Creating and maintaining catalogue records.',
                'category'    => 'documentation',
                'icon'        => 'fa-book',
            ],
            self::PROC_CONDITION => [
                'label'       => 'Condition Checking',
                'description' => 'Recording and monitoring object condition.',
                'category'    => 'care',
                'icon'        => 'fa-heartbeat',
            ],
            self::PROC_CONSERVATION => [
                'label'       => 'Conservation',
                'description' => 'Planning and documenting conservation treatments.',
                'category'    => 'care',
                'icon'        => 'fa-medkit',
            ],
            self::PROC_VALUATION => [
                'label'       => 'Valuation',
                'description' => 'Recording object valuations for insurance and reporting.',
                'category'    => 'financial',
                'icon'        => 'fa-dollar-sign',
            ],
            self::PROC_INSURANCE => [
                'label'       => 'Insurance',
                'description' => 'Managing insurance for collections.',
                'category'    => 'financial',
                'icon'        => 'fa-shield-alt',
            ],
            self::PROC_LOAN_IN => [
                'label'       => 'Loans In',
                'description' => 'Borrowing objects from other institutions or individuals.',
                'category'    => 'loans',
                'icon'        => 'fa-arrow-circle-down',
            ],
            self::PROC_LOAN_OUT => [
                'label'       => 'Loans Out',
                'description' => 'Lending objects to other institutions.',
                'category'    => 'loans',
                'icon'        => 'fa-arrow-circle-up',
            ],
            self::PROC_LOSS => [
                'label'       => 'Loss & Damage',
                'description' => 'Recording and responding to loss or damage.',
                'category'    => 'risk',
                'icon'        => 'fa-exclamation-triangle',
            ],
            self::PROC_DEACCESSION => [
                'label'       => 'Deaccession',
                'description' => 'Formally removing objects from the collection.',
                'category'    => 'disposal',
                'icon'        => 'fa-minus-circle',
            ],
            self::PROC_DISPOSAL => [
                'label'       => 'Disposal',
                'description' => 'Physically disposing of deaccessioned objects.',
                'category'    => 'disposal',
                'icon'        => 'fa-trash',
            ],
            self::PROC_EXIT => [
                'label'       => 'Object Exit',
                'description' => 'Recording objects leaving the museum.',
                'category'    => 'exit',
                'icon'        => 'fa-sign-out',
            ],
            self::PROC_RISK => [
                'label'       => 'Risk Management',
                'description' => 'Identifying and mitigating collection risks.',
                'category'    => 'risk',
                'icon'        => 'fa-shield-alt',
            ],
            self::PROC_AUDIT => [
                'label'       => 'Audit',
                'description' => 'Auditing collections and procedures.',
                'category'    => 'control',
                'icon'        => 'fa-clipboard-check',
            ],
            self::PROC_RIGHTS => [
                'label'       => 'Rights Management',
                'description' => 'Managing rights and reproductions.',
                'category'    => 'documentation',
                'icon'        => 'fa-copyright',
            ],
            self::PROC_REPRODUCTION => [
                'label'       => 'Reproduction',
                'description' => 'Managing reproduction requests.',
                'category'    => 'documentation',
                'icon'        => 'fa-copy',
            ],
            self::PROC_DOCUMENTATION => [
                'label'       => 'Documentation Planning',
                'description' => 'Planning documentation projects.',
                'category'    => 'documentation',
                'icon'        => 'fa-file-alt',
            ],
            self::PROC_RETROSPECTIVE => [
                'label'       => 'Retrospective Documentation',
                'description' => 'Documenting existing collections retrospectively.',
                'category'    => 'documentation',
                'icon'        => 'fa-history',
            ],
        ];
    }

    /** @inheritDoc */
    public function getProcedure(string $procedureKey): ?array
    {
        return $this->getProcedures()[$procedureKey] ?? null;
    }

    /** @inheritDoc */
    public function getProceduresByCategory(): array
    {
        $grouped = [];
        foreach ($this->getProcedures() as $key => $def) {
            $grouped[$def['category']][] = $key;
        }

        return $grouped;
    }

    // ------------------------------------------------------------------
    // Status / workflow
    // ------------------------------------------------------------------

    /** @inheritDoc */
    public function getStatuses(): array
    {
        return [
            self::STATUS_NOT_STARTED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_COMPLETED,
            self::STATUS_ON_HOLD,
            self::STATUS_OVERDUE,
        ];
    }

    /** @inheritDoc */
    public function getStatusColors(): array
    {
        return [
            self::STATUS_NOT_STARTED    => '#95a5a6',
            self::STATUS_IN_PROGRESS    => '#3498db',
            self::STATUS_PENDING_REVIEW => '#f39c12',
            self::STATUS_COMPLETED      => '#27ae60',
            self::STATUS_ON_HOLD        => '#9b59b6',
            self::STATUS_OVERDUE        => '#e74c3c',
        ];
    }

    /** @inheritDoc */
    public function getStatusLabels(): array
    {
        return [
            self::STATUS_NOT_STARTED    => 'Not Started',
            self::STATUS_IN_PROGRESS    => 'In Progress',
            self::STATUS_PENDING_REVIEW => 'Pending Review',
            self::STATUS_COMPLETED      => 'Completed',
            self::STATUS_ON_HOLD        => 'On Hold',
            self::STATUS_OVERDUE        => 'Overdue',
        ];
    }

    /** @inheritDoc */
    public function getWorkflowState(int $recordId, string $procedureType): ?object
    {
        if (!Schema::hasTable('spectrum_workflow_state')) {
            return null;
        }

        return DB::table('spectrum_workflow_state')
            ->where('record_id', $recordId)
            ->where('procedure_type', $procedureType)
            ->first();
    }

    /** @inheritDoc */
    public function getWorkflowStatesForRecord(int $recordId): array
    {
        if (!Schema::hasTable('spectrum_workflow_state')) {
            return [];
        }

        $states = DB::table('spectrum_workflow_state')
            ->where('record_id', $recordId)
            ->get();

        $keyed = [];
        foreach ($states as $state) {
            $keyed[$state->procedure_type] = $state;
        }

        return $keyed;
    }

    /** @inheritDoc */
    public function getWorkflowConfig(string $procedureType): ?array
    {
        if (!Schema::hasTable('spectrum_workflow_config')) {
            return null;
        }

        $config = DB::table('spectrum_workflow_config')
            ->where('procedure_type', $procedureType)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            return null;
        }

        return json_decode($config->config_json, true);
    }

    /** @inheritDoc */
    public function getFinalStates(string $procedureType): array
    {
        $config = $this->getWorkflowConfig($procedureType);

        return $config['final_states'] ?? [];
    }

    /** @inheritDoc */
    public function isFinalState(string $procedureType, string $state): bool
    {
        return in_array($state, $this->getFinalStates($procedureType), true);
    }

    /** @inheritDoc */
    public function getAvailableTransitions(string $procedureType, string $currentState): array
    {
        $config = $this->getWorkflowConfig($procedureType);
        if (!$config) {
            return [];
        }

        $transitions = $config['transitions'] ?? [];
        $available = [];

        foreach ($transitions as $transKey => $transDef) {
            if (isset($transDef['from']) && in_array($currentState, $transDef['from'], true)) {
                $available[$transKey] = $transDef;
            }
        }

        return $available;
    }

    /** @inheritDoc */
    public function executeTransition(
        int $recordId,
        string $procedureType,
        string $transitionKey,
        string $fromState,
        string $toState,
        ?int $assignedTo = null,
        ?string $note = null
    ): bool {
        $userId = Auth::id();
        $now = now();

        return DB::transaction(function () use ($recordId, $procedureType, $transitionKey, $fromState, $toState, $assignedTo, $note, $userId, $now) {
            // Upsert workflow state
            $existing = $this->getWorkflowState($recordId, $procedureType);

            if ($existing) {
                DB::table('spectrum_workflow_state')
                    ->where('id', $existing->id)
                    ->update([
                        'current_state' => $toState,
                        'assigned_to'   => $assignedTo,
                        'assigned_by'   => $userId,
                        'assigned_at'   => $assignedTo ? $now : null,
                        'updated_at'    => $now,
                    ]);
            } else {
                DB::table('spectrum_workflow_state')->insert([
                    'record_id'      => $recordId,
                    'procedure_type' => $procedureType,
                    'current_state'  => $toState,
                    'assigned_to'    => $assignedTo,
                    'assigned_by'    => $userId,
                    'assigned_at'    => $assignedTo ? $now : null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }

            // Record history
            if (Schema::hasTable('spectrum_workflow_history')) {
                DB::table('spectrum_workflow_history')->insert([
                    'record_id'      => $recordId,
                    'procedure_type' => $procedureType,
                    'transition_key' => $transitionKey,
                    'from_state'     => $fromState,
                    'to_state'       => $toState,
                    'user_id'        => $userId,
                    'note'           => $note,
                    'created_at'     => $now,
                ]);
            }

            // Notification for assignee
            if ($assignedTo && Schema::hasTable('spectrum_notification')) {
                DB::table('spectrum_notification')->insert([
                    'user_id'    => $assignedTo,
                    'type'       => 'task_assigned',
                    'message'    => "You have been assigned the {$procedureType} procedure (record #{$recordId}).",
                    'data'       => json_encode([
                        'record_id'      => $recordId,
                        'procedure_type' => $procedureType,
                        'transition_key' => $transitionKey,
                    ]),
                    'created_at' => $now,
                ]);
            }

            return true;
        });
    }

    // ------------------------------------------------------------------
    // Condition checking
    // ------------------------------------------------------------------

    /** @inheritDoc */
    public function getOrCreateConditionCheck(int $objectId): ?object
    {
        if (!Schema::hasTable('spectrum_condition_check')) {
            return null;
        }

        $check = DB::table('spectrum_condition_check')
            ->where('object_id', $objectId)
            ->orderBy('check_date', 'desc')
            ->first();

        if (!$check) {
            $newId = DB::table('spectrum_condition_check')->insertGetId([
                'object_id'                 => $objectId,
                'condition_check_reference' => 'CC-' . date('Ymd') . '-' . $objectId,
                'check_date'                => now(),
                'checked_by'                => Auth::user()->username ?? 'system',
                'created_at'                => now(),
            ]);
            $check = DB::table('spectrum_condition_check')->where('id', $newId)->first();
        }

        return $check;
    }

    /** @inheritDoc */
    public function getConditionChecksForObject(int $objectId): array
    {
        if (!Schema::hasTable('spectrum_condition_check')) {
            return [];
        }

        return DB::table('spectrum_condition_check')
            ->where('object_id', $objectId)
            ->orderBy('check_date', 'desc')
            ->get()
            ->toArray();
    }

    /** @inheritDoc */
    public function getConditionPhotos(int $conditionCheckId): array
    {
        $photos = [];
        $photosByType = [];

        if (!Schema::hasTable('spectrum_condition_photo')) {
            return ['photos' => $photos, 'photosByType' => $photosByType];
        }

        $rawPhotos = DB::table('spectrum_condition_photo')
            ->where('condition_check_id', $conditionCheckId)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($rawPhotos as $photo) {
            $arr = (array) $photo;
            $photos[] = $arr;
            $type = $photo->photo_type ?? 'other';
            $photosByType[$type][] = $arr;
        }

        return ['photos' => $photos, 'photosByType' => $photosByType];
    }

    /** @inheritDoc */
    public function getRiskItems(): array
    {
        if (!Schema::hasTable('spectrum_condition_check')) {
            return [];
        }

        $culture = $this->getCulture();

        return DB::table('spectrum_condition_check as c')
            ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                $j->on('c.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'c.object_id', '=', 's.object_id')
            ->whereIn('c.overall_condition', ['critical', 'poor'])
            ->select('c.*', 'i18n.title', 's.slug')
            ->orderBy('c.check_date', 'desc')
            ->get()
            ->toArray();
    }

    /** @inheritDoc */
    public function getConditionStats(): array
    {
        $stats = ['total_checks' => 0, 'critical' => 0, 'poor' => 0];

        if (!Schema::hasTable('spectrum_condition_check')) {
            return $stats;
        }

        $stats['total_checks'] = DB::table('spectrum_condition_check')->count();
        $stats['critical']     = DB::table('spectrum_condition_check')->where('overall_condition', 'critical')->count();
        $stats['poor']         = DB::table('spectrum_condition_check')->where('overall_condition', 'poor')->count();

        return $stats;
    }

    // ------------------------------------------------------------------
    // Dashboard / statistics
    // ------------------------------------------------------------------

    /** @inheritDoc */
    public function getWorkflowStatistics(?int $repositoryId = null): array
    {
        $stats = [
            'total_objects'          => 0,
            'objects_with_workflows' => 0,
            'completed_procedures'   => 0,
            'in_progress_procedures' => 0,
            'pending_procedures'     => 0,
        ];

        try {
            $stats['total_objects'] = DB::table('information_object')->count();

            if (Schema::hasTable('spectrum_workflow_state')) {
                $query = DB::table('spectrum_workflow_state');

                if ($repositoryId) {
                    $query->join('information_object as io', 'spectrum_workflow_state.record_id', '=', 'io.id')
                          ->where('io.repository_id', $repositoryId);
                }

                $stats['objects_with_workflows'] = (clone $query)
                    ->distinct('record_id')
                    ->count('record_id');

                $statusCounts = (clone $query)
                    ->select('current_state', DB::raw('COUNT(*) as count'))
                    ->groupBy('current_state')
                    ->get();

                foreach ($statusCounts as $row) {
                    if (in_array($row->current_state, ['completed', 'verified', 'closed', 'confirmed'])) {
                        $stats['completed_procedures'] += $row->count;
                    } elseif ($row->current_state === 'pending') {
                        $stats['pending_procedures'] += $row->count;
                    } else {
                        $stats['in_progress_procedures'] += $row->count;
                    }
                }
            }
        } catch (\Exception $e) {
            // Tables may not exist yet
        }

        return $stats;
    }

    /** @inheritDoc */
    public function getRecentWorkflowActivity(?int $repositoryId = null, int $limit = 20): array
    {
        if (!Schema::hasTable('spectrum_workflow_history')) {
            return [];
        }

        try {
            $query = DB::table('spectrum_workflow_history as h')
                ->join('slug as s', 'h.record_id', '=', 's.object_id')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('h.record_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
                })
                ->leftJoin('user as u', 'h.user_id', '=', 'u.id')
                ->select('h.*', 's.slug', 'ioi.title as object_title', 'u.username as user_name');

            if ($repositoryId) {
                $query->join('information_object as io', 'h.record_id', '=', 'io.id')
                      ->where('io.repository_id', $repositoryId);
            }

            return $query->orderBy('h.created_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /** @inheritDoc */
    public function getProcedureStatusCounts(?int $repositoryId = null): array
    {
        if (!Schema::hasTable('spectrum_workflow_state')) {
            return [];
        }

        $counts = [];

        try {
            $query = DB::table('spectrum_workflow_state')
                ->select('procedure_type', 'current_state', DB::raw('COUNT(*) as count'));

            if ($repositoryId) {
                $query->join('information_object as io', 'spectrum_workflow_state.record_id', '=', 'io.id')
                      ->where('io.repository_id', $repositoryId);
            }

            $results = $query->groupBy('procedure_type', 'current_state')->get();

            foreach ($results as $row) {
                if (!isset($counts[$row->procedure_type])) {
                    $counts[$row->procedure_type] = [];
                }
                $counts[$row->procedure_type][$row->current_state] = $row->count;
            }
        } catch (\Exception $e) {
            // Table may not exist
        }

        return $counts;
    }

    /** @inheritDoc */
    public function calculateOverallCompletion(?int $repositoryId = null): array
    {
        if (!Schema::hasTable('spectrum_workflow_state')) {
            return ['percentage' => 0, 'completed' => 0, 'total' => 0];
        }

        try {
            $query = DB::table('spectrum_workflow_state');

            if ($repositoryId) {
                $query->join('information_object as io', 'spectrum_workflow_state.record_id', '=', 'io.id')
                      ->where('io.repository_id', $repositoryId);
            }

            $total = (clone $query)->count();
            if ($total === 0) {
                return ['percentage' => 0, 'completed' => 0, 'total' => 0];
            }

            $completed = (clone $query)
                ->whereIn('current_state', ['completed', 'verified', 'closed', 'confirmed', 'documented'])
                ->count();

            return [
                'percentage' => (int) round(($completed / $total) * 100),
                'completed'  => $completed,
                'total'      => $total,
            ];
        } catch (\Exception $e) {
            return ['percentage' => 0, 'completed' => 0, 'total' => 0];
        }
    }

    /** @inheritDoc */
    public function getDataQualityMetrics(): array
    {
        $totalObjects = DB::table('information_object')->where('id', '!=', 1)->count();

        $missingTitles = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->where('io.id', '!=', 1)
            ->whereNull('i18n.title')
            ->count();

        $missingDates = DB::table('information_object')
            ->where('id', '!=', 1)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('event')
                    ->whereColumn('event.object_id', 'information_object.id');
            })
            ->count();

        $missingRepository = DB::table('information_object')
            ->where('id', '!=', 1)
            ->whereNull('repository_id')
            ->count();

        $missingDigitalObjects = DB::table('information_object')
            ->where('id', '!=', 1)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('digital_object')
                    ->whereColumn('digital_object.object_id', 'information_object.id');
            })
            ->count();

        $issues = $missingTitles + $missingDates + $missingRepository;
        $qualityScore = $totalObjects > 0 ? (int) round((1 - ($issues / ($totalObjects * 3))) * 100) : 100;

        return [
            'totalObjects'         => $totalObjects,
            'missingTitles'        => $missingTitles,
            'missingDates'         => $missingDates,
            'missingRepository'    => $missingRepository,
            'missingDigitalObjects' => $missingDigitalObjects,
            'qualityScore'         => $qualityScore,
        ];
    }

    // ------------------------------------------------------------------
    // Browse queries
    // ------------------------------------------------------------------

    /** @inheritDoc */
    public function getObjectEntries(int $perPage = 25): mixed
    {
        if (!Schema::hasTable('spectrum_object_entry')) {
            return collect();
        }

        $culture = $this->getCulture();

        return DB::table('spectrum_object_entry as e')
            ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                $j->on('e.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'e.object_id', '=', 's.object_id')
            ->select('e.*', 'i18n.title as object_title', 's.slug')
            ->orderBy('e.entry_date', 'desc')
            ->paginate($perPage);
    }

    /** @inheritDoc */
    public function getAcquisitions(int $perPage = 25): mixed
    {
        if (!Schema::hasTable('spectrum_acquisition')) {
            return collect();
        }

        $culture = $this->getCulture();

        return DB::table('spectrum_acquisition as a')
            ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                $j->on('a.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'a.object_id', '=', 's.object_id')
            ->select('a.*', 'i18n.title as object_title', 's.slug')
            ->orderBy('a.acquisition_date', 'desc')
            ->paginate($perPage);
    }

    /** @inheritDoc */
    public function getLoans(): array
    {
        $loansIn = collect();
        $loansOut = collect();
        $culture = $this->getCulture();

        if (Schema::hasTable('spectrum_loan_in')) {
            $loansIn = DB::table('spectrum_loan_in as l')
                ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                    $j->on('l.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                })
                ->leftJoin('slug as s', 'l.object_id', '=', 's.object_id')
                ->select('l.*', 'i18n.title as object_title', 's.slug', DB::raw("'in' as direction"))
                ->orderBy('l.loan_in_date', 'desc')
                ->get();
        }

        if (Schema::hasTable('spectrum_loan_out')) {
            $loansOut = DB::table('spectrum_loan_out as l')
                ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                    $j->on('l.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                })
                ->leftJoin('slug as s', 'l.object_id', '=', 's.object_id')
                ->select('l.*', 'i18n.title as object_title', 's.slug', DB::raw("'out' as direction"))
                ->orderBy('l.loan_out_date', 'desc')
                ->get();
        }

        return ['loansIn' => $loansIn, 'loansOut' => $loansOut];
    }

    /** @inheritDoc */
    public function getMovements(int $perPage = 25): mixed
    {
        if (!Schema::hasTable('spectrum_movement')) {
            return collect();
        }

        $culture = $this->getCulture();

        return DB::table('spectrum_movement as m')
            ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                $j->on('m.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'm.object_id', '=', 's.object_id')
            ->leftJoin('spectrum_location as loc_from', 'm.location_from', '=', 'loc_from.id')
            ->leftJoin('spectrum_location as loc_to', 'm.location_to', '=', 'loc_to.id')
            ->select(
                'm.*',
                'i18n.title as object_title',
                's.slug',
                'loc_from.location_name as from_location_name',
                'loc_to.location_name as to_location_name'
            )
            ->orderBy('m.movement_date', 'desc')
            ->paginate($perPage);
    }

    /** @inheritDoc */
    public function getConditionChecks(int $perPage = 25): mixed
    {
        if (!Schema::hasTable('spectrum_condition_check')) {
            return collect();
        }

        $culture = $this->getCulture();

        return DB::table('spectrum_condition_check as c')
            ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                $j->on('c.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'c.object_id', '=', 's.object_id')
            ->select('c.*', 'i18n.title as object_title', 's.slug')
            ->orderBy('c.check_date', 'desc')
            ->paginate($perPage);
    }

    /** @inheritDoc */
    public function getConservationTreatments(int $perPage = 25): mixed
    {
        if (!Schema::hasTable('spectrum_conservation')) {
            return collect();
        }

        $culture = $this->getCulture();

        return DB::table('spectrum_conservation as c')
            ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                $j->on('c.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'c.object_id', '=', 's.object_id')
            ->select('c.*', 'i18n.title as object_title', 's.slug')
            ->orderBy('c.treatment_date', 'desc')
            ->paginate($perPage);
    }

    /** @inheritDoc */
    public function getValuations(int $perPage = 25): mixed
    {
        if (!Schema::hasTable('spectrum_valuation')) {
            return collect();
        }

        $culture = $this->getCulture();

        return DB::table('spectrum_valuation as v')
            ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                $j->on('v.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'v.object_id', '=', 's.object_id')
            ->select('v.*', 'i18n.title as object_title', 's.slug')
            ->orderBy('v.valuation_date', 'desc')
            ->paginate($perPage);
    }

    // ------------------------------------------------------------------
    // Export
    // ------------------------------------------------------------------

    /** @inheritDoc */
    public function exportData(string $format, string $type, ?int $objectId = null): mixed
    {
        $culture = $this->getCulture();
        $data = [];
        $filename = "spectrum_{$type}_" . date('Y-m-d');

        switch ($type) {
            case 'condition':
                if (Schema::hasTable('spectrum_condition_check')) {
                    $q = DB::table('spectrum_condition_check as c')
                        ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                            $j->on('c.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                        })
                        ->select('c.*', 'i18n.title as object_title')
                        ->orderBy('c.check_date', 'desc');
                    if ($objectId) {
                        $q->where('c.object_id', $objectId);
                    }
                    $data = $q->get()->toArray();
                }
                break;

            case 'valuation':
                if (Schema::hasTable('spectrum_valuation')) {
                    $q = DB::table('spectrum_valuation as v')
                        ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                            $j->on('v.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                        })
                        ->select('v.*', 'i18n.title as object_title')
                        ->orderBy('v.valuation_date', 'desc');
                    if ($objectId) {
                        $q->where('v.object_id', $objectId);
                    }
                    $data = $q->get()->toArray();
                }
                break;

            case 'movement':
                if (Schema::hasTable('spectrum_movement')) {
                    $q = DB::table('spectrum_movement as m')
                        ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                            $j->on('m.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                        })
                        ->select('m.*', 'i18n.title as object_title')
                        ->orderBy('m.movement_date', 'desc');
                    if ($objectId) {
                        $q->where('m.object_id', $objectId);
                    }
                    $data = $q->get()->toArray();
                }
                break;

            case 'loan':
                $loansIn = [];
                $loansOut = [];
                if (Schema::hasTable('spectrum_loan_in')) {
                    $qIn = DB::table('spectrum_loan_in as l')
                        ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                            $j->on('l.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                        })
                        ->select('l.*', 'i18n.title as object_title', DB::raw("'IN' as direction"));
                    if ($objectId) {
                        $qIn->where('l.object_id', $objectId);
                    }
                    $loansIn = $qIn->get()->toArray();
                }
                if (Schema::hasTable('spectrum_loan_out')) {
                    $qOut = DB::table('spectrum_loan_out as l')
                        ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                            $j->on('l.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                        })
                        ->select('l.*', 'i18n.title as object_title', DB::raw("'OUT' as direction"));
                    if ($objectId) {
                        $qOut->where('l.object_id', $objectId);
                    }
                    $loansOut = $qOut->get()->toArray();
                }
                $data = array_merge($loansIn, $loansOut);
                break;

            case 'workflow':
                if (Schema::hasTable('spectrum_workflow_history')) {
                    $q = DB::table('spectrum_workflow_history as w')
                        ->leftJoin('user as u', 'w.user_id', '=', 'u.id')
                        ->select('w.*', 'u.username as user_name')
                        ->orderBy('w.created_at', 'desc');
                    if ($objectId) {
                        $q->where('w.record_id', $objectId);
                    }
                    $data = $q->get()->toArray();
                }
                break;
        }

        if ($format === 'csv') {
            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ];

            $callback = function () use ($data) {
                $output = fopen('php://output', 'w');
                if (!empty($data)) {
                    fputcsv($output, array_keys((array) $data[0]));
                    foreach ($data as $row) {
                        fputcsv($output, (array) $row);
                    }
                }
                fclose($output);
            };

            return response()->stream($callback, 200, $headers);
        }

        // JSON
        return response()->json($data)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.json\"");
    }

    // ------------------------------------------------------------------
    // Repositories filter
    // ------------------------------------------------------------------

    /** @inheritDoc */
    public function getRepositoriesForFilter(): array
    {
        try {
            return DB::table('repository')
                ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
                ->where('actor_i18n.culture', 'en')
                ->whereNotNull('actor_i18n.authorized_form_of_name')
                ->select('repository.id', 'actor_i18n.authorized_form_of_name')
                ->orderBy('actor_i18n.authorized_form_of_name')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    // ------------------------------------------------------------------
    // Task management
    // ------------------------------------------------------------------

    /** @inheritDoc */
    public function getTasksForUser(int $userId, ?string $procedureTypeFilter = null): mixed
    {
        if (!Schema::hasTable('spectrum_workflow_state')) {
            return collect();
        }

        $culture = $this->getCulture();

        // Gather final states per procedure from configs
        $finalStatesByProcedure = [];
        if (Schema::hasTable('spectrum_workflow_config')) {
            $configs = DB::table('spectrum_workflow_config')->where('is_active', true)->get();
            foreach ($configs as $config) {
                $configData = json_decode($config->config_json, true);
                $finals = $configData['final_states'] ?? [];
                if (!empty($finals)) {
                    $finalStatesByProcedure[$config->procedure_type] = $finals;
                }
            }
        }

        $query = DB::table('spectrum_workflow_state as sws')
            ->select([
                'sws.*',
                'io.id as object_id',
                'io.identifier',
                'io.repository_id',
                'ioi18n.title as object_title',
                'slug.slug',
                'assigner.username as assigned_by_name',
            ])
            ->leftJoin('information_object as io', 'sws.record_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi18n', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi18n.id')->where('ioi18n.culture', '=', $culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('user as assigner', 'sws.assigned_by', '=', 'assigner.id')
            ->where('sws.assigned_to', $userId);

        // Exclude final states per procedure
        if (!empty($finalStatesByProcedure)) {
            $query->where(function ($q) use ($finalStatesByProcedure) {
                foreach ($finalStatesByProcedure as $proc => $finals) {
                    $q->where(function ($inner) use ($proc, $finals) {
                        $inner->where('sws.procedure_type', '!=', $proc)
                              ->orWhereNotIn('sws.current_state', $finals);
                    });
                }
            });
        }

        if ($procedureTypeFilter) {
            $query->where('sws.procedure_type', $procedureTypeFilter);
        }

        return $query->orderBy('sws.assigned_at', 'desc')->get();
    }

    /** @inheritDoc */
    public function getAssignedProcedureTypes(int $userId): array
    {
        if (!Schema::hasTable('spectrum_workflow_state')) {
            return [];
        }

        return DB::table('spectrum_workflow_state')
            ->where('assigned_to', $userId)
            ->distinct()
            ->pluck('procedure_type')
            ->toArray();
    }

    /** @inheritDoc */
    public function getUnreadNotificationCount(int $userId): int
    {
        if (!Schema::hasTable('spectrum_notification')) {
            return 0;
        }

        return DB::table('spectrum_notification')
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
