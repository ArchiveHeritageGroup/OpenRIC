<?php

declare(strict_types=1);

namespace OpenRiC\Workflow\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\Workflow\Contracts\WorkflowServiceInterface;

class WorkflowService implements WorkflowServiceInterface
{
    public function getMyTasks(int $userId, ?string $status = null): array
    {
        $query = DB::table('workflow_tasks')
            ->join('workflows', 'workflow_tasks.workflow_id', '=', 'workflows.id')
            ->join('workflow_steps', 'workflow_tasks.workflow_step_id', '=', 'workflow_steps.id')
            ->select('workflow_tasks.*', 'workflows.name as workflow_name', 'workflow_steps.name as step_name')
            ->where('workflow_tasks.assigned_to', $userId)
            ->orderByDesc('workflow_tasks.priority')
            ->orderBy('workflow_tasks.due_date');

        if ($status !== null) {
            $query->where('workflow_tasks.status', $status);
        }

        return $query->limit(100)->get()->map(fn ($r) => (array) $r)->toArray();
    }

    public function getPoolTasks(int $userId): array
    {
        return DB::table('workflow_tasks')
            ->join('workflows', 'workflow_tasks.workflow_id', '=', 'workflows.id')
            ->join('workflow_steps', 'workflow_tasks.workflow_step_id', '=', 'workflow_steps.id')
            ->select('workflow_tasks.*', 'workflows.name as workflow_name', 'workflow_steps.name as step_name')
            ->whereNull('workflow_tasks.assigned_to')
            ->where('workflow_tasks.status', 'pending')
            ->where('workflow_steps.pool_enabled', true)
            ->orderByDesc('workflow_tasks.priority')
            ->limit(100)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();
    }

    public function getTask(int $taskId): ?array
    {
        $task = DB::table('workflow_tasks')
            ->join('workflows', 'workflow_tasks.workflow_id', '=', 'workflows.id')
            ->join('workflow_steps', 'workflow_tasks.workflow_step_id', '=', 'workflow_steps.id')
            ->select('workflow_tasks.*', 'workflows.name as workflow_name', 'workflow_steps.name as step_name', 'workflow_steps.instructions')
            ->where('workflow_tasks.id', $taskId)
            ->first();

        if ($task === null) {
            return null;
        }

        $history = DB::table('workflow_history')
            ->join('users', 'workflow_history.performed_by', '=', 'users.id')
            ->select('workflow_history.*', 'users.display_name as performer_name')
            ->where('workflow_history.task_id', $taskId)
            ->orderByDesc('workflow_history.performed_at')
            ->limit(50)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();

        return array_merge((array) $task, ['history' => $history]);
    }

    public function claimTask(int $taskId, int $userId): bool
    {
        return DB::transaction(function () use ($taskId, $userId) {
            $task = DB::table('workflow_tasks')
                ->where('id', $taskId)
                ->whereNull('assigned_to')
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($task === null) {
                return false;
            }

            DB::table('workflow_tasks')->where('id', $taskId)->update([
                'assigned_to' => $userId,
                'status' => 'claimed',
                'claimed_at' => now(),
                'updated_at' => now(),
            ]);

            $this->logHistory($taskId, (int) $task->workflow_id, (int) $task->workflow_step_id, 'claimed', 'pending', 'claimed', $userId);

            return true;
        });
    }

    public function releaseTask(int $taskId, int $userId): bool
    {
        return DB::transaction(function () use ($taskId, $userId) {
            $task = DB::table('workflow_tasks')
                ->where('id', $taskId)
                ->where('assigned_to', $userId)
                ->first();

            if ($task === null) {
                return false;
            }

            DB::table('workflow_tasks')->where('id', $taskId)->update([
                'assigned_to' => null,
                'status' => 'pending',
                'claimed_at' => null,
                'updated_at' => now(),
            ]);

            $this->logHistory($taskId, (int) $task->workflow_id, (int) $task->workflow_step_id, 'released', $task->status, 'pending', $userId);

            return true;
        });
    }

    public function completeTask(int $taskId, int $userId, string $decision, ?string $comment = null): bool
    {
        return DB::transaction(function () use ($taskId, $userId, $decision, $comment) {
            $task = DB::table('workflow_tasks')
                ->where('id', $taskId)
                ->where('assigned_to', $userId)
                ->first();

            if ($task === null) {
                return false;
            }

            DB::table('workflow_tasks')->where('id', $taskId)->update([
                'status' => 'completed',
                'decision' => $decision,
                'decision_by' => $userId,
                'decision_at' => now(),
                'decision_comment' => $comment,
                'updated_at' => now(),
            ]);

            $this->logHistory($taskId, (int) $task->workflow_id, (int) $task->workflow_step_id, $decision, $task->status, 'completed', $userId, $comment);

            return true;
        });
    }

    public function getStats(int $userId): array
    {
        return [
            'my_tasks' => DB::table('workflow_tasks')->where('assigned_to', $userId)->whereIn('status', ['claimed', 'in_progress'])->count(),
            'pool_tasks' => DB::table('workflow_tasks')->whereNull('assigned_to')->where('status', 'pending')->count(),
            'completed_today' => DB::table('workflow_tasks')->where('decision_by', $userId)->where('decision_at', '>=', now()->startOfDay())->count(),
            'overdue' => DB::table('workflow_tasks')->where('assigned_to', $userId)->whereNotNull('due_date')->where('due_date', '<', now())->whereIn('status', ['claimed', 'in_progress'])->count(),
        ];
    }

    private function logHistory(int $taskId, int $workflowId, int $stepId, string $action, string $fromStatus, string $toStatus, int $userId, ?string $comment = null): void
    {
        DB::table('workflow_history')->insert([
            'task_id' => $taskId,
            'workflow_id' => $workflowId,
            'workflow_step_id' => $stepId,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'performed_by' => $userId,
            'comment' => $comment,
            'performed_at' => now(),
        ]);
    }
}
