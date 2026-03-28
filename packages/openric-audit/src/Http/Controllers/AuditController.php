<?php

declare(strict_types=1);

namespace OpenRiC\Audit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\Audit\Contracts\AuditServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Audit trail controller — adapted from Heratio AuditTrailController (489 lines).
 *
 * All actions from Heratio mapped:
 *   1. browse           — List audit entries with filters + pagination
 *   2. show             — View single audit entry detail
 *   3. statistics       — Statistics dashboard (actions by type, top users, failures)
 *   4. settings         — Audit configuration (GET + POST)
 *   5. authentication   — Authentication log (logins, failures, lockouts)
 *   6. entityHistory    — Audit trail for a specific entity (by IRI)
 *   7. userActivity     — All activity for a specific user
 *   8. compareData      — Side-by-side old/new value diff
 *   9. export           — CSV export of audit log
 *  10. entityHistoryByType — Entity history filtered by type + IRI
 */
class AuditController extends Controller
{
    public function __construct(
        private readonly AuditServiceInterface $auditService,
    ) {}

    /**
     * #1 — Browse audit log with filters and pagination.
     */
    public function browse(Request $request): View
    {
        $filters = [
            'action' => $request->input('action', ''),
            'entity_type' => $request->input('type', ''),
            'user' => $request->input('user', ''),
            'date_from' => $request->input('from', ''),
            'date_to' => $request->input('to', ''),
        ];

        $page = max(1, (int) $request->input('page', 1));
        $limit = max(1, (int) $request->input('limit', 25));
        $offset = ($page - 1) * $limit;

        $result = $this->auditService->browse(array_filter($filters), $limit, $offset);
        $totalPages = $limit > 0 ? (int) ceil($result['total'] / $limit) : 1;

        return view('openric-audit::browse', [
            'entries' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'entityTypes' => $result['entityTypes'],
            'actions' => $result['actions'],
            'filters' => $filters,
        ]);
    }

    /**
     * #2 — View single audit entry detail.
     */
    public function show(int $id): View
    {
        $entry = $this->auditService->find($id);

        if ($entry === null) {
            abort(404, 'Audit entry not found');
        }

        return view('openric-audit::show', ['entry' => $entry]);
    }

    /**
     * #3 — Statistics dashboard.
     */
    public function statistics(Request $request): View
    {
        $days = (int) $request->input('days', 30);
        if (!in_array($days, [7, 30, 90, 365])) {
            $days = 30;
        }

        $stats = $this->auditService->getStatistics($days);

        return view('openric-audit::statistics', array_merge($stats, ['days' => $days]));
    }

    /**
     * #4 — Audit settings (GET).
     */
    public function settings(): View
    {
        $settings = $this->auditService->getSettings();

        return view('openric-audit::settings', ['settings' => $settings]);
    }

    /**
     * #4b — Audit settings (POST).
     */
    public function settingsStore(Request $request)
    {
        $this->auditService->saveSettings($request->input('settings', []));

        return redirect()->route('audit.settings')
            ->with('success', 'Audit settings saved.');
    }

    /**
     * #5 — Authentication log (logins, failures, lockouts).
     */
    public function authentication(): View
    {
        $recentLogins = $this->auditService->getAuthenticationLogs('login', 50);
        $suspiciousActivity = $this->auditService->getAuthenticationLogs('suspicious', 50);

        return view('openric-audit::authentication', compact('recentLogins', 'suspiciousActivity'));
    }

    /**
     * #6 — Entity history by IRI.
     */
    public function entityHistory(Request $request): View
    {
        $entityId = $request->input('iri', '');
        $entityType = $request->input('type');

        $rows = $entityId
            ? $this->auditService->getEntityHistory($entityId, $entityType)
            : collect();

        return view('openric-audit::entity-history', ['rows' => $rows, 'entityId' => $entityId, 'entityType' => $entityType]);
    }

    /**
     * #7 — User activity by user ID.
     */
    public function userActivity(int $userId): View
    {
        $result = $this->auditService->getUserActivity($userId);

        return view('openric-audit::user-activity', [
            'rows' => $result['rows'],
            'username' => $result['username'],
        ]);
    }

    /**
     * #8 — Compare old/new values side by side.
     */
    public function compareData(int $id): View
    {
        $result = $this->auditService->compareData($id);

        if ($result === null) {
            abort(404, 'Audit entry not found');
        }

        return view('openric-audit::compare', ['entry' => $result]);
    }

    /**
     * #9 — Export audit log as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = array_filter([
            'date_from' => $request->input('from'),
            'date_to' => $request->input('to'),
        ]);

        $logs = $this->auditService->export($filters);
        $filename = 'audit_log_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date/Time', 'User', 'Action', 'Entity Type', 'Entity ID', 'Entity Title', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->created_at,
                    $log->username ?? 'N/A',
                    $log->action,
                    $log->entity_type ?? 'N/A',
                    $log->entity_id ?? 'N/A',
                    $log->entity_title ?? 'N/A',
                    $log->ip_address ?? 'N/A',
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * #10 — Entity history by type + IRI (direct URL).
     */
    public function entityHistoryByType(string $entityType, string $entityId): View
    {
        $rows = $this->auditService->getEntityHistory($entityId, $entityType);

        return view('openric-audit::entity-history', [
            'rows' => $rows,
            'entityId' => $entityId,
            'entityType' => $entityType,
        ]);
    }
}
