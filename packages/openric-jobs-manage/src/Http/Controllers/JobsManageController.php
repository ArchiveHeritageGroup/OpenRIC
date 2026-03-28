<?php

declare(strict_types=1);

namespace OpenRiC\JobsManage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenRiC\JobsManage\Contracts\JobsManageServiceInterface;

/**
 * Jobs management controller -- adapted from Heratio AhgJobsManage\Controllers\JobController (238 lines).
 */
class JobsManageController extends Controller
{
    public function __construct(
        private readonly JobsManageServiceInterface $service,
    ) {}

    /**
     * Dashboard: stats + pending jobs.
     */
    public function index(Request $request): JsonResponse
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        $pending = $this->service->getPendingJobs($page, $limit);
        $stats   = $this->service->getStats();

        return response()->json(array_merge($pending, ['stats' => $stats]));
    }

    /**
     * List failed jobs.
     */
    public function failed(Request $request): JsonResponse
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        $data  = $this->service->getFailedJobs($page, $limit);
        $stats = $this->service->getStats();

        return response()->json(array_merge($data, ['stats' => $stats]));
    }

    /**
     * Retry a failed job.
     */
    public function retry(Request $request): JsonResponse
    {
        $request->validate(['uuid' => 'required|string']);

        $retried = $this->service->retryJob($request->input('uuid'));

        if (!$retried) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Job queued for retry.']);
    }

    /**
     * Delete a single failed job.
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate(['uuid' => 'required|string']);

        $deleted = $this->service->deleteJob($request->input('uuid'));

        if (!$deleted) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Clear all failed jobs.
     */
    public function clearFailed(): JsonResponse
    {
        $count = $this->service->clearFailed();

        return response()->json(['cleared' => $count]);
    }
}
