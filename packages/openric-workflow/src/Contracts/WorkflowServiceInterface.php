<?php

declare(strict_types=1);

namespace OpenRiC\Workflow\Contracts;

interface WorkflowServiceInterface
{
    public function getMyTasks(int $userId, ?string $status = null): array;

    public function getPoolTasks(int $userId): array;

    public function getTask(int $taskId): ?array;

    public function claimTask(int $taskId, int $userId): bool;

    public function releaseTask(int $taskId, int $userId): bool;

    public function completeTask(int $taskId, int $userId, string $decision, ?string $comment = null): bool;

    public function getStats(int $userId): array;
}
