<?php

declare(strict_types=1);

namespace OpenRiC\Workflow\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenRiC\Workflow\Contracts\WorkflowServiceInterface;

class WorkflowService implements WorkflowServiceInterface
{
    /**
     * Get dashboard stats for a user.
     */
    public function getStats(int $userId): array
    {
        $myTasks = DB::table('workflow_tasks')
            ->where('assigned_to', $userId)
            ->whereIn('status', ['pending', 'claimed', 'in_progress'])
            ->count();

        $poolTasks = DB::table('workflow_tasks')
            ->join('workflow_steps', 'workflow_tasks.workflow_step_id', '=', 'workflow_steps.id')
            ->whereNull('workflow_tasks.assigned_to')
            ->where('workflow_steps.pool_enabled', true)
            ->whereIn('workflow_tasks.status', ['pending'])
            ->count();

        $completedToday = DB::table('workflow_tasks')
            ->where('decision_by', $userId)
            ->where('status', 'completed')
            ->whereDate('decision_at', now()->toDateString())
            ->count();

        $overdueTasks = DB::table('workflow_tasks')
            ->where('assigned_to', $userId)
            ->whereIn('status', ['pending', 'claimed', 'in_progress'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        return [
            'my_tasks' => $myTasks,
            'pool_tasks' => $poolTasks,
            'completed_today' => $completedToday,
            'overdue_tasks' => $overdueTasks,
        ];
    }

    /**
     * Get tasks assigned to a specific user, optionally filtered by status.
     */
    public function getMyTasks(int $userId, ?string $status = null): array
    {
        $query = DB::table('workflow_tasks')
            ->join('workflow_steps', 'workflow_tasks.workflow_step_id', '=', 'workflow_steps.id')
            ->join('workflows', 'workflow_tasks.workflow_id', '=', 'workflows.id')
            ->where('workflow_tasks.assigned_to', $userId)
            ->select(
                'workflow_tasks.*',
                'workflow_steps.name as step_name',
                'workflow_steps.step_type',
                'workflow_steps.action_required',
                'workflow_steps.instructions',
                'workflows.name as workflow_name'
            )
            ->orderBy('workflow_tasks.priority', 'desc')
            ->orderBy('workflow_tasks.due_date');

        if ($status) {
            $query->where('workflow_tasks.status', $status);
        } else {
            $query->whereIn('workflow_tasks.status', ['pending', 'claimed', 'in_progress']);
        }

        return $query->get()->toArray();
    }

    /**
     * Get pool tasks (unassigned, pool-enabled).
     */
    public function getPoolTasks(int $userId): array
    {
        return DB::table('workflow_tasks')
            ->join('workflow_steps', 'workflow_tasks.workflow_step_id', '=', 'workflow_steps.id')
            ->join('workflows', 'workflow_tasks.workflow_id', '=', 'workflows.id')
            ->whereNull('workflow_tasks.assigned_to')
            ->where('workflow_steps.pool_enabled', true)
            ->where('workflow_tasks.status', 'pending')
            ->select(
                'workflow_tasks.*',
                'workflow_steps.name as step_name',
                'workflow_steps.step_type',
                'workflow_steps.instructions',
                'workflows.name as workflow_name'
            )
            ->orderBy('workflow_tasks.priority', 'desc')
            ->orderBy('workflow_tasks.due_date')
            ->get()
            ->toArray();
    }

    /**
     * Get a single task with step, workflow, and history.
     */
    public function getTask(int $id): ?object
    {
        $task = DB::table('workflow_tasks')
            ->join('workflow_steps', 'workflow_tasks.workflow_step_id', '=', 'workflow_steps.id')
            ->join('workflows', 'workflow_tasks.workflow_id', '=', 'workflows.id')
            ->leftJoin('users as assigned_user', 'workflow_tasks.assigned_to', '=', 'assigned_user.id')
            ->leftJoin('users as submitted_user', 'workflow_tasks.submitted_by', '=', 'submitted_user.id')
            ->where('workflow_tasks.id', $id)
            ->select(
                'workflow_tasks.*',
                'workflow_steps.name as step_name',
                'workflow_steps.step_type',
                'workflow_steps.action_required',
                'workflow_steps.instructions',
                'workflow_steps.checklist as step_checklist',
                'workflow_steps.pool_enabled',
                'workflows.name as workflow_name',
                'workflows.description as workflow_description',
                'assigned_user.username as assigned_username',
                DB::raw('COALESCE(assigned_user.display_name, assigned_user.username) as assigned_name'),
                'submitted_user.username as submitted_username',
                DB::raw('COALESCE(submitted_user.display_name, submitted_user.username) as submitted_name')
            )
            ->first();

        if ($task) {
            $task->history = DB::table('workflow_history')
                ->leftJoin('users', 'workflow_history.performed_by', '=', 'users.id')
                ->where('workflow_history.task_id', $id)
                ->select(
                    'workflow_history.*',
                    'users.username',
                    DB::raw('COALESCE(users.display_name, users.username) as performer_name')
                )
                ->orderBy('workflow_history.performed_at', 'desc')
                ->get()
                ->toArray();
        }

        return $task;
    }

    /**
     * Claim a task for a user.
     */
    public function claimTask(int $taskId, int $userId): bool
    {
        return DB::transaction(function () use ($taskId, $userId) {
            $task = DB::table('workflow_tasks')->where('id', $taskId)->lockForUpdate()->first();
            if (!$task || $task->assigned_to !== null) {
                return false;
            }

            DB::table('workflow_tasks')->where('id', $taskId)->update([
                'assigned_to' => $userId,
                'status' => 'claimed',
                'claimed_at' => now(),
                'updated_at' => now(),
            ]);

            $this->logHistory($task, 'claimed', 'pending', 'claimed', $userId);

            return true;
        });
    }

    /**
     * Release a task back to the pool.
     */
    public function releaseTask(int $taskId, int $userId, ?string $comment = null): bool
    {
        return DB::transaction(function () use ($taskId, $userId, $comment) {
            $task = DB::table('workflow_tasks')->where('id', $taskId)->first();
            if (!$task || (int) $task->assigned_to !== $userId) {
                return false;
            }

            $fromStatus = $task->status;

            DB::table('workflow_tasks')->where('id', $taskId)->update([
                'assigned_to' => null,
                'status' => 'pending',
                'claimed_at' => null,
                'updated_at' => now(),
            ]);

            $this->logHistory($task, 'released', $fromStatus, 'pending', $userId, $comment);

            return true;
        });
    }

    /**
     * Approve a task and create the next step task if applicable.
     */
    public function approveTask(int $taskId, int $userId, ?string $comment = null): bool
    {
        return DB::transaction(function () use ($taskId, $userId, $comment) {
            $task = DB::table('workflow_tasks')->where('id', $taskId)->lockForUpdate()->first();
            if (!$task) {
                return false;
            }

            $fromStatus = $task->status;

            DB::table('workflow_tasks')->where('id', $taskId)->update([
                'decision' => 'approved',
                'status' => 'completed',
                'decision_comment' => $comment,
                'decision_at' => now(),
                'decision_by' => $userId,
                'updated_at' => now(),
            ]);

            $this->logHistory($task, 'approved', $fromStatus, 'completed', $userId, $comment);

            // Find and create next step task
            $currentStep = DB::table('workflow_steps')->where('id', $task->workflow_step_id)->first();
            if ($currentStep) {
                $nextStep = DB::table('workflow_steps')
                    ->where('workflow_id', $task->workflow_id)
                    ->where('step_order', '>', $currentStep->step_order)
                    ->where('is_active', true)
                    ->orderBy('step_order')
                    ->first();

                if ($nextStep) {
                    $newTaskId = DB::table('workflow_tasks')->insertGetId([
                        'workflow_id' => $task->workflow_id,
                        'workflow_step_id' => $nextStep->id,
                        'object_iri' => $task->object_iri,
                        'object_type' => $task->object_type,
                        'status' => $nextStep->auto_assign_user_id ? 'claimed' : 'pending',
                        'priority' => $task->priority,
                        'submitted_by' => $task->submitted_by,
                        'assigned_to' => $nextStep->auto_assign_user_id,
                        'claimed_at' => $nextStep->auto_assign_user_id ? now() : null,
                        'due_date' => $nextStep->escalation_days
                            ? now()->addDays($nextStep->escalation_days)->toDateString()
                            : $task->due_date,
                        'decision' => 'pending',
                        'previous_task_id' => $taskId,
                        'queue_id' => $task->queue_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->logHistory(
                        (object) [
                            'id' => $newTaskId,
                            'workflow_id' => $task->workflow_id,
                            'workflow_step_id' => $nextStep->id,
                            'object_iri' => $task->object_iri,
                            'object_type' => $task->object_type,
                        ],
                        'created',
                        null,
                        'pending',
                        $userId,
                        'Auto-created from approval of task #' . $taskId
                    );
                }
            }

            return true;
        });
    }

    /**
     * Complete a task with a decision (approve/reject/other).
     */
    public function completeTask(int $taskId, int $userId, string $decision, ?string $comment = null): bool
    {
        if ($decision === 'approved') {
            return $this->approveTask($taskId, $userId, $comment);
        }

        if ($decision === 'rejected') {
            return $this->rejectTask($taskId, $userId, $comment ?? 'Rejected');
        }

        return DB::transaction(function () use ($taskId, $userId, $decision, $comment) {
            $task = DB::table('workflow_tasks')->where('id', $taskId)->lockForUpdate()->first();
            if (!$task) {
                return false;
            }

            $fromStatus = $task->status;

            DB::table('workflow_tasks')->where('id', $taskId)->update([
                'decision' => $decision,
                'status' => 'completed',
                'decision_comment' => $comment,
                'decision_at' => now(),
                'decision_by' => $userId,
                'updated_at' => now(),
            ]);

            $this->logHistory($task, $decision, $fromStatus, 'completed', $userId, $comment);

            return true;
        });
    }

    /**
     * Reject a task.
     */
    public function rejectTask(int $taskId, int $userId, string $comment): bool
    {
        return DB::transaction(function () use ($taskId, $userId, $comment) {
            $task = DB::table('workflow_tasks')->where('id', $taskId)->lockForUpdate()->first();
            if (!$task) {
                return false;
            }

            $fromStatus = $task->status;

            DB::table('workflow_tasks')->where('id', $taskId)->update([
                'decision' => 'rejected',
                'status' => 'completed',
                'decision_comment' => $comment,
                'decision_at' => now(),
                'decision_by' => $userId,
                'updated_at' => now(),
            ]);

            $this->logHistory($task, 'rejected', $fromStatus, 'completed', $userId, $comment);

            return true;
        });
    }

    /**
     * Start a new workflow for an object.
     */
    public function startWorkflow(int $workflowId, string $objectIri, string $objectType, int $userId): ?int
    {
        return DB::transaction(function () use ($workflowId, $objectIri, $objectType, $userId) {
            $workflow = DB::table('workflows')->where('id', $workflowId)->where('is_active', true)->first();
            if (!$workflow) {
                return null;
            }

            $firstStep = DB::table('workflow_steps')
                ->where('workflow_id', $workflowId)
                ->where('is_active', true)
                ->orderBy('step_order')
                ->first();

            if (!$firstStep) {
                return null;
            }

            $taskId = DB::table('workflow_tasks')->insertGetId([
                'workflow_id' => $workflowId,
                'workflow_step_id' => $firstStep->id,
                'object_iri' => $objectIri,
                'object_type' => $objectType,
                'status' => $firstStep->auto_assign_user_id ? 'claimed' : 'pending',
                'priority' => 'normal',
                'submitted_by' => $userId,
                'assigned_to' => $firstStep->auto_assign_user_id,
                'claimed_at' => $firstStep->auto_assign_user_id ? now() : null,
                'due_date' => $firstStep->escalation_days
                    ? now()->addDays($firstStep->escalation_days)->toDateString()
                    : null,
                'decision' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->logHistory(
                (object) [
                    'id' => $taskId,
                    'workflow_id' => $workflowId,
                    'workflow_step_id' => $firstStep->id,
                    'object_iri' => $objectIri,
                    'object_type' => $objectType,
                ],
                'started',
                null,
                'pending',
                $userId,
                'Workflow started: ' . $workflow->name
            );

            return $taskId;
        });
    }

    /**
     * Get all workflows with step and active task counts.
     */
    public function getWorkflows(): array
    {
        return DB::table('workflows')
            ->leftJoin(
                DB::raw('(SELECT workflow_id, COUNT(*) as step_count FROM workflow_steps GROUP BY workflow_id) sc'),
                'workflows.id',
                '=',
                'sc.workflow_id'
            )
            ->leftJoin(
                DB::raw("(SELECT workflow_id, COUNT(*) as active_task_count FROM workflow_tasks WHERE status IN ('pending','claimed','in_progress') GROUP BY workflow_id) tc"),
                'workflows.id',
                '=',
                'tc.workflow_id'
            )
            ->select(
                'workflows.*',
                DB::raw('COALESCE(sc.step_count, 0) as step_count'),
                DB::raw('COALESCE(tc.active_task_count, 0) as active_task_count')
            )
            ->orderBy('workflows.name')
            ->get()
            ->toArray();
    }

    /**
     * Get a single workflow with its steps.
     */
    public function getWorkflow(int $id): ?object
    {
        $workflow = DB::table('workflows')->where('id', $id)->first();

        if ($workflow) {
            $workflow->steps = DB::table('workflow_steps')
                ->where('workflow_id', $id)
                ->orderBy('step_order')
                ->get()
                ->toArray();
        }

        return $workflow;
    }

    /**
     * Create a workflow.
     */
    public function createWorkflow(array $data): int
    {
        return DB::table('workflows')->insertGetId([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'scope_type' => $data['scope_type'] ?? 'global',
            'scope_id' => $data['scope_id'] ?? null,
            'trigger_event' => $data['trigger_event'] ?? 'submit',
            'applies_to' => $data['applies_to'] ?? 'record_resource',
            'is_active' => $data['is_active'] ?? true,
            'is_default' => $data['is_default'] ?? false,
            'require_all_steps' => $data['require_all_steps'] ?? true,
            'allow_parallel' => $data['allow_parallel'] ?? false,
            'auto_archive_days' => $data['auto_archive_days'] ?? null,
            'notification_enabled' => $data['notification_enabled'] ?? true,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update a workflow.
     */
    public function updateWorkflow(int $id, array $data): bool
    {
        return (bool) DB::table('workflows')->where('id', $id)->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'scope_type' => $data['scope_type'] ?? 'global',
            'scope_id' => $data['scope_id'] ?? null,
            'trigger_event' => $data['trigger_event'] ?? 'submit',
            'applies_to' => $data['applies_to'] ?? 'record_resource',
            'is_active' => $data['is_active'] ?? true,
            'is_default' => $data['is_default'] ?? false,
            'require_all_steps' => $data['require_all_steps'] ?? true,
            'allow_parallel' => $data['allow_parallel'] ?? false,
            'auto_archive_days' => $data['auto_archive_days'] ?? null,
            'notification_enabled' => $data['notification_enabled'] ?? true,
            'updated_at' => now(),
        ]);
    }

    /**
     * Delete a workflow and associated steps/tasks.
     */
    public function deleteWorkflow(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            // Delete history for tasks in this workflow
            DB::table('workflow_history')->where('workflow_id', $id)->delete();
            // Delete notifications for tasks in this workflow
            $taskIds = DB::table('workflow_tasks')->where('workflow_id', $id)->pluck('id');
            if ($taskIds->isNotEmpty()) {
                DB::table('workflow_notifications')->whereIn('task_id', $taskIds)->delete();
            }
            // Delete tasks
            DB::table('workflow_tasks')->where('workflow_id', $id)->delete();
            // Delete steps
            DB::table('workflow_steps')->where('workflow_id', $id)->delete();
            // Delete workflow
            return (bool) DB::table('workflows')->where('id', $id)->delete();
        });
    }

    /**
     * Add a step to a workflow.
     */
    public function addStep(int $workflowId, array $data): int
    {
        $maxOrder = DB::table('workflow_steps')
            ->where('workflow_id', $workflowId)
            ->max('step_order') ?? 0;

        return DB::table('workflow_steps')->insertGetId([
            'workflow_id' => $workflowId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'step_order' => $data['step_order'] ?? ($maxOrder + 1),
            'step_type' => $data['step_type'] ?? 'review',
            'action_required' => $data['action_required'] ?? 'approve_reject',
            'required_role_id' => $data['required_role_id'] ?? null,
            'required_clearance_level' => $data['required_clearance_level'] ?? null,
            'allowed_group_ids' => $data['allowed_group_ids'] ?? null,
            'allowed_user_ids' => $data['allowed_user_ids'] ?? null,
            'pool_enabled' => $data['pool_enabled'] ?? true,
            'auto_assign_user_id' => $data['auto_assign_user_id'] ?? null,
            'escalation_days' => $data['escalation_days'] ?? null,
            'escalation_user_id' => $data['escalation_user_id'] ?? null,
            'notification_template' => $data['notification_template'] ?? 'default',
            'instructions' => $data['instructions'] ?? null,
            'checklist' => $data['checklist'] ?? null,
            'is_optional' => $data['is_optional'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update a step.
     */
    public function updateStep(int $id, array $data): bool
    {
        $update = [
            'updated_at' => now(),
        ];

        $fields = [
            'name', 'description', 'step_order', 'step_type', 'action_required',
            'required_role_id', 'required_clearance_level', 'allowed_group_ids', 'allowed_user_ids',
            'pool_enabled', 'auto_assign_user_id', 'escalation_days', 'escalation_user_id',
            'notification_template', 'instructions', 'checklist', 'is_optional', 'is_active',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        return (bool) DB::table('workflow_steps')->where('id', $id)->update($update);
    }

    /**
     * Delete a step.
     */
    public function deleteStep(int $id): bool
    {
        return (bool) DB::table('workflow_steps')->where('id', $id)->delete();
    }

    /**
     * Get recent workflow history.
     */
    public function getHistory(int $limit = 100): array
    {
        return DB::table('workflow_history')
            ->leftJoin('users', 'workflow_history.performed_by', '=', 'users.id')
            ->leftJoin('workflows', 'workflow_history.workflow_id', '=', 'workflows.id')
            ->select(
                'workflow_history.*',
                'users.username',
                DB::raw('COALESCE(users.display_name, users.username) as performer_name'),
                'workflows.name as workflow_name'
            )
            ->orderBy('workflow_history.performed_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get all history for a specific object by IRI.
     */
    public function getObjectHistory(string $objectIri): array
    {
        return DB::table('workflow_history')
            ->leftJoin('users', 'workflow_history.performed_by', '=', 'users.id')
            ->leftJoin('workflows', 'workflow_history.workflow_id', '=', 'workflows.id')
            ->where('workflow_history.object_iri', $objectIri)
            ->select(
                'workflow_history.*',
                'users.username',
                DB::raw('COALESCE(users.display_name, users.username) as performer_name'),
                'workflows.name as workflow_name'
            )
            ->orderBy('workflow_history.performed_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get all workflow queues with task counts.
     */
    public function getQueues(): array
    {
        return DB::table('workflow_queues')
            ->leftJoin(
                DB::raw("(SELECT queue_id, COUNT(*) as task_count FROM workflow_tasks WHERE status IN ('pending','claimed','in_progress') GROUP BY queue_id) tc"),
                'workflow_queues.id',
                '=',
                'tc.queue_id'
            )
            ->leftJoin(
                DB::raw("(SELECT queue_id, COUNT(*) as overdue_count FROM workflow_tasks WHERE status IN ('pending','claimed','in_progress') AND due_date < CURRENT_DATE GROUP BY queue_id) oc"),
                'workflow_queues.id',
                '=',
                'oc.queue_id'
            )
            ->leftJoin('workflow_sla_policies', 'workflow_queues.id', '=', 'workflow_sla_policies.queue_id')
            ->select(
                'workflow_queues.*',
                DB::raw('COALESCE(tc.task_count, 0) as task_count'),
                DB::raw('COALESCE(oc.overdue_count, 0) as overdue_count'),
                'workflow_sla_policies.warning_days',
                'workflow_sla_policies.due_days',
                'workflow_sla_policies.escalation_days as sla_escalation_days'
            )
            ->orderBy('workflow_queues.sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get overdue tasks, optionally filtered by user or queue.
     */
    public function getOverdueTasks(?int $userId = null, ?int $queueId = null): array
    {
        $query = DB::table('workflow_tasks')
            ->join('workflow_steps', 'workflow_tasks.workflow_step_id', '=', 'workflow_steps.id')
            ->join('workflows', 'workflow_tasks.workflow_id', '=', 'workflows.id')
            ->leftJoin('users', 'workflow_tasks.assigned_to', '=', 'users.id')
            ->whereIn('workflow_tasks.status', ['pending', 'claimed', 'in_progress'])
            ->whereNotNull('workflow_tasks.due_date')
            ->where('workflow_tasks.due_date', '<', now()->toDateString())
            ->select(
                'workflow_tasks.*',
                'workflow_steps.name as step_name',
                'workflows.name as workflow_name',
                'users.username as assigned_username',
                DB::raw('COALESCE(users.display_name, users.username) as assigned_name')
            )
            ->orderBy('workflow_tasks.due_date');

        if ($userId) {
            $query->where('workflow_tasks.assigned_to', $userId);
        }
        if ($queueId) {
            $query->where('workflow_tasks.queue_id', $queueId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get all publish gate rules.
     */
    public function getGateRules(): array
    {
        return DB::table('publish_gate_rules')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get a single gate rule.
     */
    public function getGateRule(int $id): ?object
    {
        return DB::table('publish_gate_rules')->where('id', $id)->first();
    }

    /**
     * Create or update a gate rule.
     */
    public function saveGateRule(array $data, ?int $id = null): int
    {
        $row = [
            'name' => $data['name'],
            'rule_type' => $data['rule_type'],
            'entity_type' => $data['entity_type'] ?? 'record_resource',
            'level_of_description_id' => $data['level_of_description_id'] ?: null,
            'material_type' => $data['material_type'] ?: null,
            'repository_id' => $data['repository_id'] ?: null,
            'field_name' => $data['field_name'] ?? null,
            'rule_config' => $data['rule_config'] ?? null,
            'error_message' => $data['error_message'],
            'severity' => $data['severity'] ?? 'blocker',
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
            'updated_at' => now(),
        ];

        if ($id) {
            DB::table('publish_gate_rules')->where('id', $id)->update($row);

            return $id;
        }

        $row['created_at'] = now();

        return DB::table('publish_gate_rules')->insertGetId($row);
    }

    /**
     * Delete a gate rule.
     */
    public function deleteGateRule(int $id): bool
    {
        DB::table('publish_gate_results')->where('rule_id', $id)->delete();

        return (bool) DB::table('publish_gate_rules')->where('id', $id)->delete();
    }

    /**
     * Evaluate all applicable gate rules for an object identified by IRI.
     *
     * Gate evaluation checks each active rule against the object. For RiC-O
     * entities, field lookups use the rule_config to locate the relevant table
     * and column, keyed by object_iri.
     */
    public function evaluateGates(string $objectIri): array
    {
        $rules = DB::table('publish_gate_rules')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $results = [];
        $summary = ['pass' => 0, 'fail' => 0, 'warning' => 0];

        foreach ($rules as $rule) {
            $status = $this->evaluateSingleGate($rule, $objectIri);
            $details = null;

            if ($status === 'fail' || $status === 'warning') {
                $details = $rule->error_message;
            }

            // Store the result
            DB::table('publish_gate_results')->updateOrInsert(
                ['object_iri' => $objectIri, 'rule_id' => $rule->id],
                [
                    'status' => $status,
                    'details' => $details,
                    'evaluated_at' => now(),
                    'evaluated_by' => auth()->id(),
                ]
            );

            $results[] = (object) [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'rule_type' => $rule->rule_type,
                'severity' => $rule->severity,
                'status' => $status,
                'details' => $details,
            ];

            $summary[$status] = ($summary[$status] ?? 0) + 1;
        }

        return [
            'object_iri' => $objectIri,
            'results' => $results,
            'summary' => $summary,
        ];
    }

    /**
     * Evaluate a single gate rule against an object IRI.
     */
    private function evaluateSingleGate(object $rule, string $objectIri): string
    {
        switch ($rule->rule_type) {
            case 'required_field':
                $fieldName = $rule->field_name;
                if (!$fieldName) {
                    return 'pass';
                }
                $config = json_decode($rule->rule_config ?? '{}', true);
                $tableName = $config['table'] ?? null;
                $columnName = $config['column'] ?? $fieldName;
                if ($tableName) {
                    $value = DB::table($tableName)
                        ->where('object_iri', $objectIri)
                        ->value($columnName);

                    return (!empty($value) && trim((string) $value) !== '')
                        ? 'pass'
                        : ($rule->severity === 'warning' ? 'warning' : 'fail');
                }

                return $rule->severity === 'warning' ? 'warning' : 'fail';

            case 'workflow_completed':
                $pending = DB::table('workflow_tasks')
                    ->where('object_iri', $objectIri)
                    ->whereIn('status', ['pending', 'claimed', 'in_progress'])
                    ->count();

                return $pending === 0 ? 'pass' : ($rule->severity === 'warning' ? 'warning' : 'fail');

            case 'min_description_length':
                $config = json_decode($rule->rule_config ?? '{}', true);
                $minLength = $config['min_length'] ?? 100;
                $tableName = $config['table'] ?? null;
                $columnName = $config['column'] ?? ($rule->field_name ?? 'description');
                if ($tableName) {
                    $value = DB::table($tableName)
                        ->where('object_iri', $objectIri)
                        ->value($columnName);

                    return (mb_strlen((string) ($value ?? '')) >= $minLength)
                        ? 'pass'
                        : ($rule->severity === 'warning' ? 'warning' : 'fail');
                }

                return $rule->severity === 'warning' ? 'warning' : 'fail';

            case 'custom_sql':
                $config = json_decode($rule->rule_config ?? '{}', true);
                if (!empty($config['sql'])) {
                    try {
                        $result = DB::select($config['sql'], ['object_iri' => $objectIri]);

                        return (!empty($result) && ($result[0]->result ?? 0))
                            ? 'pass'
                            : ($rule->severity === 'warning' ? 'warning' : 'fail');
                    } catch (\Exception $e) {
                        return 'fail';
                    }
                }

                return 'pass';

            default:
                return 'pass';
        }
    }

    /**
     * Log a workflow history entry.
     */
    private function logHistory(
        object $task,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        int $userId,
        ?string $comment = null
    ): void {
        DB::table('workflow_history')->insert([
            'task_id' => $task->id,
            'workflow_id' => $task->workflow_id,
            'workflow_step_id' => $task->workflow_step_id ?? null,
            'object_iri' => $task->object_iri ?? null,
            'object_type' => $task->object_type ?? null,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'performed_by' => $userId,
            'performed_at' => now(),
            'comment' => $comment,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
            'correlation_id' => Str::uuid()->toString(),
        ]);
    }
}
