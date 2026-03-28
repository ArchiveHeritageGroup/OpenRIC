<?php

declare(strict_types=1);

namespace OpenRiC\Triplestore\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Triplestore\Services\RicSyncService;

/**
 * RiC Sync Controller — adapted from Heratio RicController (2,145 lines).
 *
 * Heratio actions mapped:
 *   1. dashboard       — RiC sync dashboard with stats
 *   2. queue           — View sync queue with filters
 *   3. queueRetry      — Retry a failed queue entry (POST)
 *   4. queueRetryAll   — Retry all failed (POST)
 *   5. queuePurge      — Purge completed entries (POST)
 *   6. syncLog         — View sync operation log
 *   7. fusekiHealth    — Fuseki endpoint health check
 *   8. entityTypes     — Entity type breakdown
 *   9. propertyStats   — Property usage statistics
 *  10. syncStats       — Sync statistics/reports
 *  11. validateEntity  — Validate entity exists (JSON)
 */
class RicSyncController extends Controller
{
    public function __construct(
        private readonly RicSyncService $syncService,
    ) {}

    /**
     * #1 — RiC sync dashboard.
     */
    public function dashboard(): View
    {
        $stats = $this->syncService->getDashboardStats();

        return view('triplestore::ric-sync.dashboard', ['stats' => $stats]);
    }

    /**
     * #2 — Sync queue listing.
     */
    public function queue(Request $request): View
    {
        $filters = $request->only(['status', 'entity_type', 'action']);
        $page = max(1, (int) $request->input('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $result = $this->syncService->getQueue($filters, $limit, $offset);
        $totalPages = $limit > 0 ? (int) ceil($result['total'] / $limit) : 1;

        return view('triplestore::ric-sync.queue', [
            'items' => $result['items'], 'total' => $result['total'],
            'page' => $page, 'totalPages' => $totalPages, 'filters' => $filters,
        ]);
    }

    /**
     * #3 — Retry a failed queue entry.
     */
    public function queueRetry(int $id)
    {
        $success = $this->syncService->retryQueueEntry($id);

        return redirect()->route('admin.ric-sync.queue')
            ->with($success ? 'success' : 'error', $success ? 'Entry requeued.' : 'Entry not found or not failed.');
    }

    /**
     * #4 — Retry all failed entries.
     */
    public function queueRetryAll()
    {
        $count = $this->syncService->retryAllFailed();

        return redirect()->route('admin.ric-sync.queue')
            ->with('success', "{$count} failed entries requeued.");
    }

    /**
     * #5 — Purge completed entries.
     */
    public function queuePurge(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $count = $this->syncService->purgeCompleted($days);

        return redirect()->route('admin.ric-sync.queue')
            ->with('success', "{$count} completed entries purged.");
    }

    /**
     * #6 — Sync operation log.
     */
    public function syncLog(Request $request): View
    {
        $filters = $request->only(['entity_type', 'status', 'date_from', 'date_to']);
        $page = max(1, (int) $request->input('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $result = $this->syncService->getSyncLog($filters, $limit, $offset);
        $totalPages = $limit > 0 ? (int) ceil($result['total'] / $limit) : 1;

        return view('triplestore::ric-sync.log', [
            'items' => $result['items'], 'total' => $result['total'],
            'page' => $page, 'totalPages' => $totalPages, 'filters' => $filters,
        ]);
    }

    /**
     * #7 — Fuseki health check.
     */
    public function fusekiHealth(Request $request)
    {
        $health = $this->syncService->getFusekiHealth();

        if ($request->wantsJson()) {
            return response()->json($health);
        }

        return view('triplestore::ric-sync.fuseki-health', ['health' => $health]);
    }

    /**
     * #8 — Entity type breakdown.
     */
    public function entityTypes(): View
    {
        $types = $this->syncService->getEntityTypeBreakdown();

        return view('triplestore::ric-sync.entity-types', ['types' => $types]);
    }

    /**
     * #9 — Property usage statistics.
     */
    public function propertyStats(): View
    {
        $properties = $this->syncService->getPropertyStats();

        return view('triplestore::ric-sync.property-stats', ['properties' => $properties]);
    }

    /**
     * #10 — Sync statistics/reports.
     */
    public function syncStats(Request $request): View
    {
        $days = (int) $request->input('days', 30);
        $stats = $this->syncService->getSyncStats($days);

        return view('triplestore::ric-sync.stats', ['stats' => $stats, 'days' => $days]);
    }

    /**
     * #11 — Validate entity exists (JSON).
     */
    public function validateEntity(Request $request)
    {
        $iri = $request->input('iri', '');
        $exists = $this->syncService->validateEntity($iri);

        return response()->json(['iri' => $iri, 'exists' => $exists]);
    }
}
