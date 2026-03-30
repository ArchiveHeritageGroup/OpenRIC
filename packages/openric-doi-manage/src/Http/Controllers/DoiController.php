<?php

declare(strict_types=1);

namespace OpenRiC\DoiManage\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use OpenRiC\DoiManage\Contracts\DoiServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DOI management web controller -- adapted from Heratio AhgDoiManage\Controllers\DoiController.
 *
 * Provides dashboard, browse, view, queue, configuration, batch mint, sync,
 * deactivate/reactivate, reporting, and CSV/JSON export for the admin panel.
 */
class DoiController extends Controller
{
    public function __construct(
        private readonly DoiServiceInterface $service,
    ) {}

    // =========================================================================
    // Dashboard
    // =========================================================================

    /**
     * Dashboard -- stats, recent DOIs, quick links.
     */
    public function index(): View
    {
        if (!Schema::hasTable('dois')) {
            return view('openric-doi-manage::index', ['tablesExist' => false]);
        }

        $stats      = $this->service->getStats();
        $recentDois = $this->service->getRecentDois(10);

        return view('openric-doi-manage::index', [
            'tablesExist' => true,
            'stats'       => $stats,
            'recentDois'  => $recentDois,
        ]);
    }

    // =========================================================================
    // Browse
    // =========================================================================

    /**
     * Browse DOIs with status filter and pagination.
     */
    public function browse(Request $request): View
    {
        if (!Schema::hasTable('dois')) {
            return view('openric-doi-manage::browse', ['tablesExist' => false]);
        }

        $status = $request->get('status', '');
        $dois   = $this->service->browse([
            'status' => $status,
            'limit'  => (int) $request->get('limit', config('openric-doi.hits_per_page', 20)),
        ]);

        return view('openric-doi-manage::browse', [
            'tablesExist'   => true,
            'dois'          => $dois,
            'currentStatus' => $status,
        ]);
    }

    // =========================================================================
    // View single DOI
    // =========================================================================

    /**
     * View a single DOI with activity log and action buttons.
     */
    public function show(int $id): View
    {
        if (!Schema::hasTable('dois')) {
            return view('openric-doi-manage::view', ['tablesExist' => false]);
        }

        $doi = $this->service->find($id);
        if (!$doi) {
            abort(404, 'DOI not found.');
        }

        $logs = $this->service->getActivityLog($id);

        return view('openric-doi-manage::view', [
            'tablesExist' => true,
            'doi'         => $doi,
            'logs'        => $logs,
        ]);
    }

    // =========================================================================
    // Mint (single)
    // =========================================================================

    /**
     * Mint a DOI for a single entity (POST).
     */
    public function mint(Request $request): RedirectResponse
    {
        $request->validate([
            'entity_iri' => 'required|string|max:2048',
            'title'      => 'required|string|max:1024',
        ]);

        $result = $this->service->mintDoi(
            $request->input('entity_iri'),
            $request->input('title'),
            $request->input('metadata', []),
        );

        if ($result['success']) {
            return redirect()->route('doi.browse')
                ->with('success', 'DOI minted: ' . ($result['doi'] ?? ''));
        }

        return redirect()->back()
            ->with('error', $result['error'] ?? 'Minting failed.');
    }

    /**
     * Show the mint form (GET).
     */
    public function mintForm(): View
    {
        return view('openric-doi-manage::mint');
    }

    // =========================================================================
    // Batch Mint
    // =========================================================================

    /**
     * Show batch mint form or process batch (GET/POST).
     */
    public function batchMint(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'entity_iris' => 'required|string',
            ]);

            $iris = array_filter(
                array_map('trim', explode("\n", $request->input('entity_iris', ''))),
                fn ($v) => $v !== '',
            );

            $result = $this->service->batchMint($iris);

            return redirect()->route('doi.queue')
                ->with('success', sprintf(
                    'Batch mint: %d queued, %d skipped.%s',
                    $result['queued'],
                    $result['skipped'],
                    count($result['errors']) > 0 ? ' Errors: ' . count($result['errors']) : ''
                ));
        }

        return view('openric-doi-manage::batch-mint');
    }

    // =========================================================================
    // Sync
    // =========================================================================

    /**
     * Sync a DOI's metadata with DataCite (POST).
     */
    public function sync(int $id): RedirectResponse
    {
        $result = $this->service->syncMetadata($id);

        if ($result['success']) {
            return redirect()->route('doi.view', $id)
                ->with('success', 'Metadata synced to DataCite.');
        }

        return redirect()->route('doi.view', $id)
            ->with('error', $result['error'] ?? 'Sync failed.');
    }

    // =========================================================================
    // Deactivate / Reactivate
    // =========================================================================

    /**
     * Deactivate a DOI (POST).
     */
    public function deactivate(Request $request, int $id): RedirectResponse
    {
        $reason = $request->input('reason', '');

        $result = $this->service->deactivate($id, $reason);

        if ($result['success']) {
            return redirect()->route('doi.view', $id)
                ->with('success', 'DOI deactivated.');
        }

        return redirect()->route('doi.view', $id)
            ->with('error', $result['error'] ?? 'Deactivation failed.');
    }

    /**
     * Reactivate a DOI (POST).
     */
    public function reactivate(int $id): RedirectResponse
    {
        $result = $this->service->reactivate($id);

        if ($result['success']) {
            return redirect()->route('doi.view', $id)
                ->with('success', 'DOI reactivated.');
        }

        return redirect()->route('doi.view', $id)
            ->with('error', $result['error'] ?? 'Reactivation failed.');
    }

    // =========================================================================
    // Queue
    // =========================================================================

    /**
     * Browse the DOI processing queue.
     */
    public function queue(Request $request): View|RedirectResponse
    {
        if (!Schema::hasTable('doi_queue')) {
            return view('openric-doi-manage::queue', ['tablesExist' => false]);
        }

        // Handle retry
        $retryId = (int) $request->get('retry', 0);
        if ($retryId > 0) {
            $retried = $this->service->retryQueueItem($retryId);
            return redirect()->route('doi.queue', $request->except('retry'))
                ->with($retried ? 'success' : 'error', $retried ? 'Queue item requeued.' : 'Could not retry this item.');
        }

        $status      = $request->get('status', '');
        $queueItems  = $this->service->browseQueue([
            'status' => $status,
            'limit'  => (int) $request->get('limit', config('openric-doi.hits_per_page', 20)),
        ]);
        $statusCounts = $this->service->getQueueCounts();

        return view('openric-doi-manage::queue', [
            'tablesExist'   => true,
            'queueItems'    => $queueItems,
            'statusCounts'  => $statusCounts,
            'currentStatus' => $status,
        ]);
    }

    // =========================================================================
    // Configuration
    // =========================================================================

    /**
     * Show configuration form (GET).
     */
    public function config(): View
    {
        $settings = $this->service->getConfig();

        return view('openric-doi-manage::config', [
            'settings' => $settings,
        ]);
    }

    /**
     * Save configuration (POST).
     */
    public function configSave(Request $request): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        // Handle AJAX test connection
        if ($request->has('test')) {
            $result = $this->service->testConnection();
            return response()->json($result);
        }

        $request->validate([
            'datacite_prefix'        => 'nullable|string|max:255',
            'datacite_repository_id' => 'nullable|string|max:255',
            'datacite_password'      => 'nullable|string|max:255',
            'datacite_url'           => 'nullable|url|max:500',
            'datacite_environment'   => 'nullable|in:test,production',
            'auto_mint'              => 'nullable|in:0,1',
            'auto_mint_levels'       => 'nullable|array',
            'require_digital_object' => 'nullable|in:0,1',
            'default_publisher'      => 'nullable|string|max:500',
            'default_resource_type'  => 'nullable|string|max:255',
            'suffix_pattern'         => 'nullable|string|max:500',
        ]);

        $this->service->saveConfig([
            'datacite_prefix'        => $request->input('datacite_prefix', ''),
            'datacite_repository_id' => $request->input('datacite_repository_id', ''),
            'datacite_password'      => $request->input('datacite_password', ''),
            'datacite_url'           => $request->input('datacite_url', 'https://api.test.datacite.org'),
            'datacite_environment'   => $request->input('datacite_environment', 'test'),
            'auto_mint'              => $request->input('auto_mint', '0'),
            'auto_mint_levels'       => $request->input('auto_mint_levels', []),
            'require_digital_object' => $request->input('require_digital_object', '0'),
            'default_publisher'      => $request->input('default_publisher', ''),
            'default_resource_type'  => $request->input('default_resource_type', 'Dataset'),
            'suffix_pattern'         => $request->input('suffix_pattern', '{year}/{entity_id}'),
        ]);

        return redirect()->route('doi.config')
            ->with('success', 'DOI configuration saved.');
    }

    // =========================================================================
    // Reports
    // =========================================================================

    /**
     * Report page or CSV/JSON export.
     */
    public function report(Request $request): View|StreamedResponse|\Illuminate\Http\JsonResponse
    {
        if (!Schema::hasTable('dois')) {
            return view('openric-doi-manage::report', ['tablesExist' => false]);
        }

        $format = $request->get('format');

        // Handle CSV export
        if ($format === 'csv') {
            return $this->exportCsv($request);
        }

        // Handle JSON export
        if ($format === 'json') {
            $data = $this->service->export($request->only(['status', 'from_date', 'to_date']));
            return response()->json(['dois' => $data, 'exported_at' => now()->toIso8601String()]);
        }

        $stats        = $this->service->getStats();
        $monthlyStats = $this->service->getMonthlyStats(24);
        $byRepository = $this->service->getByRepository();

        return view('openric-doi-manage::report', [
            'tablesExist'  => true,
            'stats'        => $stats,
            'monthlyStats' => $monthlyStats,
            'byRepository' => $byRepository,
        ]);
    }

    /**
     * Stream CSV export.
     */
    private function exportCsv(Request $request): StreamedResponse
    {
        $filters = $request->only(['status', 'from_date', 'to_date']);

        return response()->streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['DOI', 'Entity IRI', 'Title', 'Status', 'Minted At', 'Last Sync', 'Created', 'Updated']);

            $data = $this->service->export($filters);
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['doi'] ?? '',
                    $row['entity_iri'] ?? '',
                    $row['title'] ?? '',
                    $row['status'] ?? '',
                    $row['minted_at'] ?? '',
                    $row['last_sync_at'] ?? '',
                    $row['created_at'] ?? '',
                    $row['updated_at'] ?? '',
                ]);
            }

            fclose($handle);
        }, 'dois-export-' . date('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
