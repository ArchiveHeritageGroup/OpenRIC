<?php

declare(strict_types=1);

namespace OpenRiC\JobsManage\Contracts;

use Illuminate\Support\Collection;

/**
 * Jobs management service interface -- adapted from Heratio AhgJobsManage\Controllers\JobController (238 lines).
 *
 * Uses Laravel's built-in jobs and failed_jobs tables.
 */
interface JobsManageServiceInterface
{
    /**
     * Get pending jobs with pagination.
     *
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function getPendingJobs(int $page = 1, int $limit = 20): array;

    /**
     * Get failed jobs with pagination.
     *
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function getFailedJobs(int $page = 1, int $limit = 20): array;

    /**
     * Get completed jobs (from job_batches if available).
     *
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function getCompletedJobs(int $page = 1, int $limit = 20): array;

    /**
     * Retry a failed job by its UUID.
     */
    public function retryJob(string $uuid): bool;

    /**
     * Delete a failed job by its UUID.
     */
    public function deleteJob(string $uuid): bool;

    /**
     * Clear all failed jobs. Returns the number deleted.
     */
    public function clearFailed(): int;

    /**
     * Get aggregate stats.
     *
     * @return array{pending: int, failed: int, completed: int, total: int}
     */
    public function getStats(): array;
}
