<?php

declare(strict_types=1);

namespace OpenRiC\Workflow\Contracts;

interface WorkflowServiceInterface
{
    /**
     * Get dashboard stats for a user.
     */
    public function getStats(int $userId): array;

    /**
     * Get tasks assigned to a specific user, optionally filtered by status.
     */
    public function getMyTasks(int $userId, ?string $status = null): array;

    /**
     * Get pool tasks (unassigned, pool-enabled).
     */
    public function getPoolTasks(int $userId): array;

    /**
     * Get a single task with step, workflow, and history.
     */
    public function getTask(int $id): ?object;

    /**
     * Claim a task for a user.
     */
    public function claimTask(int $taskId, int $userId): bool;

    /**
     * Release a task back to the pool.
     */
    public function releaseTask(int $taskId, int $userId, ?string $comment = null): bool;

    /**
     * Approve a task and create the next step task if applicable.
     */
    public function approveTask(int $taskId, int $userId, ?string $comment = null): bool;

    /**
     * Complete a task with a decision (approve/reject/other).
     */
    public function completeTask(int $taskId, int $userId, string $decision, ?string $comment = null): bool;

    /**
     * Reject a task.
     */
    public function rejectTask(int $taskId, int $userId, string $comment): bool;

    /**
     * Start a new workflow for an object.
     */
    public function startWorkflow(int $workflowId, string $objectIri, string $objectType, int $userId): ?int;

    /**
     * Get all workflows with step and active task counts.
     */
    public function getWorkflows(): array;

    /**
     * Get a single workflow with its steps.
     */
    public function getWorkflow(int $id): ?object;

    /**
     * Create a workflow.
     */
    public function createWorkflow(array $data): int;

    /**
     * Update a workflow.
     */
    public function updateWorkflow(int $id, array $data): bool;

    /**
     * Delete a workflow and associated steps/tasks.
     */
    public function deleteWorkflow(int $id): bool;

    /**
     * Add a step to a workflow.
     */
    public function addStep(int $workflowId, array $data): int;

    /**
     * Update a step.
     */
    public function updateStep(int $id, array $data): bool;

    /**
     * Delete a step.
     */
    public function deleteStep(int $id): bool;

    /**
     * Get recent workflow history.
     */
    public function getHistory(int $limit = 100): array;

    /**
     * Get all history for a specific object by IRI.
     */
    public function getObjectHistory(string $objectIri): array;

    /**
     * Get all workflow queues with task counts.
     */
    public function getQueues(): array;

    /**
     * Get overdue tasks, optionally filtered by user or queue.
     */
    public function getOverdueTasks(?int $userId = null, ?int $queueId = null): array;

    /**
     * Get all publish gate rules.
     */
    public function getGateRules(): array;

    /**
     * Get a single gate rule.
     */
    public function getGateRule(int $id): ?object;

    /**
     * Create or update a gate rule.
     */
    public function saveGateRule(array $data, ?int $id = null): int;

    /**
     * Delete a gate rule.
     */
    public function deleteGateRule(int $id): bool;

    /**
     * Evaluate all applicable gate rules for an object identified by IRI.
     */
    public function evaluateGates(string $objectIri): array;
}
