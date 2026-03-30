<?php

declare(strict_types=1);

namespace OpenRiC\JobsManage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\JobsManage\Contracts\JobsManageServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Jobs management controller -- adapted from Heratio AhgJobsManage\Controllers\JobController (238 lines).
 *
 * Provides: browse (pending/failed/completed), show, delete, clear, CSV export,
 * queue batches, queue browse, queue detail, report.
 */
class JobsManageController extends Controller
{
    public function __construct(
        private readonly JobsManageServiceInterface $service,
    ) {}

    /**
     * Browse all jobs with stats and filter pills.
     */
    public function index(Request $request): View
    {
        $page   = max(1, (int) $request->input('page', 1));
        $limit  = (int) $request->input('limit', 20);
        $filter = $request->input('filter', '');

        $stats   = $this->service->getStats();
        $pending = $this->service->getPendingJobs($page, $limit);

        return view('openric-jobs-manage::browse', array_merge($pending, [
            'stats'         => $stats,
            'currentFilter' => $filter,
        ]));
    }

    /**
     * List failed jobs.
     */
    public function failed(Request $request): View
    {
        $page  = max(1, (int) $request->input('page', 1));
        $limit = (int) $request->input('limit', 20);

        $data  = $this->service->getFailedJobs($page, $limit);
        $stats = $this->service->getStats();

        return view('openric-jobs-manage::failed', array_merge($data, [
            'stats'         => $stats,
            'currentFilter' => 'failed',
        ]));
    }

    /**
     * Retry a failed job.
     */
    public function retry(Request $request): RedirectResponse
    {
        $request->validate(['uuid' => 'required|string']);

        $retried = $this->service->retryJob($request->input('uuid'));

        if (!$retried) {
            return redirect()->route('jobs.failed')->with('error', 'Job not found.');
        }

        return redirect()->route('jobs.failed')->with('success', 'Job queued for retry.');
    }

    /**
     * Delete a single failed job.
     */
    public function delete(Request $request): RedirectResponse
    {
        $request->validate(['uuid' => 'required|string']);

        $deleted = $this->service->deleteJob($request->input('uuid'));

        if (!$deleted) {
            return redirect()->route('jobs.failed')->with('error', 'Job not found.');
        }

        return redirect()->route('jobs.index')->with('success', 'Job deleted.');
    }

    /**
     * Clear all failed jobs.
     */
    public function clearFailed(): RedirectResponse
    {
        $count = $this->service->clearFailed();

        return redirect()->route('jobs.index')->with('success', "{$count} failed job(s) cleared.");
    }

    /**
     * Export jobs as CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        $pending = $this->service->getPendingJobs(1, 10000);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="jobs-export.csv"',
        ];

        return new StreamedResponse(function () use ($pending) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Job', 'Queue', 'Attempts', 'Created']);

            foreach ($pending['results'] as $job) {
                fputcsv($handle, [
                    $job->display_name ?? 'Unknown',
                    $job->queue ?? 'default',
                    $job->attempts ?? 0,
                    $job->queued_at ?? '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Queue batches view.
     */
    public function queueBatches(): View
    {
        $completed = $this->service->getCompletedJobs(1, 50);

        return view('openric-jobs-manage::queue-batches', [
            'rows' => $completed['results'],
        ]);
    }

    /**
     * Queue browse view.
     */
    public function queueBrowse(): View
    {
        $stats = $this->service->getStats();

        return view('openric-jobs-manage::queue-browse', [
            'stats' => $stats,
        ]);
    }

    /**
     * Queue detail view.
     */
    public function queueDetail(string $id): View
    {
        return view('openric-jobs-manage::queue-detail', [
            'record' => (object) ['id' => $id, 'queue' => 'default', 'status' => 'pending'],
        ]);
    }

    /**
     * Jobs report dashboard.
     */
    public function report(): View
    {
        $stats = $this->service->getStats();

        return view('openric-jobs-manage::report', [
            'totalJobs'     => $stats['total'],
            'completedJobs' => $stats['completed'],
            'failedJobs'    => $stats['failed'],
            'avgDuration'   => '0s',
        ]);
    }
}
