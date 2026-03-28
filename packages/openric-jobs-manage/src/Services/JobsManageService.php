<?php

declare(strict_types=1);

namespace OpenRiC\JobsManage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenRiC\JobsManage\Contracts\JobsManageServiceInterface;

/**
 * Jobs management service -- adapted from Heratio AhgJobsManage\Controllers\JobController (238 lines).
 *
 * Works with Laravel's built-in jobs and failed_jobs tables; no custom migration needed.
 */
class JobsManageService implements JobsManageServiceInterface
{
    public function getPendingJobs(int $page = 1, int $limit = 20): array
    {
        $page  = max(1, $page);
        $limit = min(100, max(1, $limit));

        $q = DB::table('jobs');

        $total = $q->count();

        $results = $q->orderBy('created_at')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(function (object $job): object {
                $payload = json_decode($job->payload, true);
                $job->display_name = $payload['displayName'] ?? $payload['job'] ?? 'Unknown';
                $job->attempts     = $job->attempts ?? 0;
                $job->queued_at    = date('Y-m-d H:i:s', (int) $job->created_at);
                return $job;
            });

        return [
            'results'  => $results,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => max(1, (int) ceil($total / $limit)),
            'limit'    => $limit,
        ];
    }

    public function getFailedJobs(int $page = 1, int $limit = 20): array
    {
        $page  = max(1, $page);
        $limit = min(100, max(1, $limit));

        if (!Schema::hasTable('failed_jobs')) {
            return ['results' => collect(), 'total' => 0, 'page' => 1, 'lastPage' => 1, 'limit' => $limit];
        }

        $q = DB::table('failed_jobs');

        $total = $q->count();

        $results = $q->orderByDesc('failed_at')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(function (object $job): object {
                $payload = json_decode($job->payload, true);
                $job->display_name = $payload['displayName'] ?? $payload['job'] ?? 'Unknown';
                $job->short_exception = \Illuminate\Support\Str::limit($job->exception, 200);
                return $job;
            });

        return [
            'results'  => $results,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => max(1, (int) ceil($total / $limit)),
            'limit'    => $limit,
        ];
    }

    public function getCompletedJobs(int $page = 1, int $limit = 20): array
    {
        $page  = max(1, $page);
        $limit = min(100, max(1, $limit));

        // Laravel's job_batches table tracks batch completion
        if (!Schema::hasTable('job_batches')) {
            return ['results' => collect(), 'total' => 0, 'page' => 1, 'lastPage' => 1, 'limit' => $limit];
        }

        $q = DB::table('job_batches')->whereNotNull('finished_at');

        $total = $q->count();

        $results = $q->orderByDesc('finished_at')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        return [
            'results'  => $results,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => max(1, (int) ceil($total / $limit)),
            'limit'    => $limit,
        ];
    }

    public function retryJob(string $uuid): bool
    {
        if (!Schema::hasTable('failed_jobs')) {
            return false;
        }

        $exists = DB::table('failed_jobs')->where('uuid', $uuid)->exists();
        if (!$exists) {
            return false;
        }

        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return true;
    }

    public function deleteJob(string $uuid): bool
    {
        if (!Schema::hasTable('failed_jobs')) {
            return false;
        }

        return DB::table('failed_jobs')->where('uuid', $uuid)->delete() > 0;
    }

    public function clearFailed(): int
    {
        if (!Schema::hasTable('failed_jobs')) {
            return 0;
        }

        $count = DB::table('failed_jobs')->count();
        DB::table('failed_jobs')->truncate();

        return $count;
    }

    public function getStats(): array
    {
        $pending   = DB::table('jobs')->count();
        $failed    = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $completed = Schema::hasTable('job_batches')
            ? DB::table('job_batches')->whereNotNull('finished_at')->count()
            : 0;

        return [
            'pending'   => $pending,
            'failed'    => $failed,
            'completed' => $completed,
            'total'     => $pending + $failed + $completed,
        ];
    }
}
