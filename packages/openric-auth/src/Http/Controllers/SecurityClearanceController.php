<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use OpenRiC\Auth\Contracts\SecurityClearanceServiceInterface;

/**
 * Security Clearance Controller — adapted from Heratio SecurityClearanceController (971 lines).
 *
 * Covers all Heratio security clearance actions:
 *   1. index          — List users and clearances
 *   2. view           — View single user clearance
 *   3. grant          — Grant/update clearance (POST)
 *   4. revoke         — Revoke clearance (POST)
 *   5. bulkGrant      — Bulk grant (POST)
 *   6. revokeAccess   — Revoke object access (POST)
 *   7. dashboard      — Security dashboard
 *   8. report         — Security reports
 *   9. compartments   — Compartments management
 *  10. compartmentAccess — Compartment access grants
 *  11. classify       — Classify an object
 *  12. classifyStore  — Store classification (POST)
 *  13. declassification — Declassification form
 *  14. declassifyStore — Store declassification (POST)
 *  15. accessRequests — List access requests
 *  16. approveRequest — Approve request (POST)
 *  17. denyRequest    — Deny request (POST)
 *  18. viewRequest    — View single request
 *  19. submitAccessRequest — Submit request (POST)
 *  20. myRequests     — User's own requests
 *  21. securityCompliance — Compliance dashboard
 *  22. auditDashboard — Audit dashboard
 *  23. auditIndex     — Audit log with filters
 *  24. auditExport    — Export audit log CSV
 *  25. accessDenied   — Access denied page
 *
 * Heratio differences: uses object_iri (RDF) instead of object_id (MySQL),
 * users table instead of user, no actor_i18n joins.
 */
class SecurityClearanceController extends Controller
{
    public function __construct(
        private readonly SecurityClearanceServiceInterface $service,
    ) {}

    // =========================================================================
    // Clearance Management
    // =========================================================================

    /**
     * #1 — List all users and their clearances.
     */
    public function index()
    {
        $users = $this->service->getAllUsersWithClearances();
        $classifications = $this->service->getClassificationLevels();

        $stats = [
            'total_users' => DB::table('users')->count(),
            'with_clearance' => DB::table('user_security_clearance')->count(),
            'top_secret' => DB::table('user_security_clearance as usc')
                ->join('security_classifications as sc', 'usc.classification_id', '=', 'sc.id')
                ->where('sc.level', '>=', 4)
                ->count(),
        ];

        return view('openric-auth::security.index', compact('users', 'classifications', 'stats'));
    }

    /**
     * #2 — View single user clearance details.
     */
    public function view(int $id)
    {
        $targetUser = DB::table('users')->where('id', $id)->first();
        if (!$targetUser) {
            abort(404, 'User not found');
        }

        $clearance = $this->service->getUserClearanceRecord($id);
        $classifications = $this->service->getClassificationLevels();
        $history = $this->service->getClearanceHistory($id);
        $accessGrants = $this->service->getUserAccessGrants($id);

        return view('openric-auth::security.view', compact('targetUser', 'clearance', 'classifications', 'history', 'accessGrants'));
    }

    /**
     * #3 — Grant or update clearance (POST).
     */
    public function grant(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'classification_id' => 'required|integer',
        ]);

        $userId = (int) $request->input('user_id');
        $classificationId = (int) $request->input('classification_id');
        $expiresAt = $request->input('expires_at');
        $notes = trim($request->input('notes', ''));
        $grantedBy = auth()->id();

        if ($classificationId === 0) {
            $success = $this->service->revokeClearance($userId, $grantedBy, $notes ?: 'Clearance revoked by administrator');
            $message = $success ? 'Clearance revoked successfully.' : 'Failed to revoke clearance.';
        } else {
            $success = $this->service->grantClearance($userId, $classificationId, $grantedBy, $expiresAt ?: null, $notes);
            $message = $success ? 'Clearance granted successfully.' : 'Failed to grant clearance.';
        }

        return redirect()->route('admin.security-clearance.index')
            ->with($success ? 'success' : 'error', $message);
    }

    /**
     * #4 — Revoke clearance (POST).
     */
    public function revoke(Request $request, int $id)
    {
        $notes = $request->input('notes', 'Clearance revoked by administrator');
        $success = $this->service->revokeClearance($id, auth()->id(), $notes);

        return redirect()->route('admin.security-clearance.index')
            ->with($success ? 'success' : 'error', $success ? 'Clearance revoked.' : 'Failed to revoke clearance.');
    }

    /**
     * #5 — Bulk grant clearances (POST).
     */
    public function bulkGrant(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'classification_id' => 'required|integer|exists:security_classifications,id',
        ]);

        $count = $this->service->bulkGrant(
            $request->input('user_ids'),
            (int) $request->input('classification_id'),
            auth()->id(),
            trim($request->input('notes', 'Bulk grant by administrator'))
        );

        return redirect()->route('admin.security-clearance.index')
            ->with('success', "Clearance granted to {$count} users.");
    }

    /**
     * #6 — Revoke object access grant (POST).
     */
    public function revokeAccess(Request $request, int $id)
    {
        $userId = (int) $request->input('user_id');
        $success = $this->service->revokeObjectAccess($id, auth()->id());

        return redirect()->route('admin.security-clearance.view', ['id' => $userId])
            ->with($success ? 'success' : 'error', $success ? 'Access revoked.' : 'Failed to revoke access.');
    }

    /**
     * #7 — Security Dashboard.
     */
    public function dashboard()
    {
        $statistics = $this->service->getDashboardStatistics();
        $pendingRequests = $this->service->getPendingRequests();
        $expiringClearances = $this->service->getExpiringClearances();
        $dueDeclassifications = $this->service->getDueDeclassifications();

        return view('openric-auth::security.dashboard', compact('statistics', 'pendingRequests', 'expiringClearances', 'dueDeclassifications'));
    }

    /**
     * #8 — Security Reports.
     */
    public function report(Request $request)
    {
        $period = $request->input('period', '30 days');
        $reportData = $this->service->getReportStats($period);

        return view('openric-auth::security.report', array_merge($reportData, ['period' => $period]));
    }

    /**
     * #9 — Compartments management.
     */
    public function compartments()
    {
        $compartments = $this->service->getCompartments();
        $userCounts = $this->service->getCompartmentUserCounts();

        return view('openric-auth::security.compartments', compact('compartments', 'userCounts'));
    }

    /**
     * #10 — Compartment access grants.
     */
    public function compartmentAccess()
    {
        $grants = $this->service->getCompartmentAccessGrants();

        return view('openric-auth::security.compartment-access', compact('grants'));
    }

    // =========================================================================
    // Object Classification
    // =========================================================================

    /**
     * #11 — Classify an object form.
     */
    public function classify(Request $request)
    {
        $objectIri = $request->input('iri', '');
        $classifications = $this->service->getClassificationLevels();
        $currentClassification = $objectIri ? $this->service->getObjectClassification($objectIri) : null;
        $compartments = $this->service->getCompartments();

        return view('openric-auth::security.classify', compact('objectIri', 'classifications', 'currentClassification', 'compartments'));
    }

    /**
     * #12 — Store classification (POST).
     */
    public function classifyStore(Request $request)
    {
        $request->validate([
            'object_iri' => 'required|string|max:2048',
            'classification_id' => 'required|integer|exists:security_classifications,id',
        ]);

        $success = $this->service->classifyObject(
            $request->input('object_iri'),
            (int) $request->input('classification_id'),
            auth()->id(),
            $request->input('reason'),
            $request->input('compartment_ids')
        );

        return redirect()->route('admin.security-clearance.dashboard')
            ->with($success ? 'success' : 'error', $success ? 'Classification applied.' : 'Failed to apply classification.');
    }

    /**
     * #13 — Declassification form.
     */
    public function declassification(Request $request)
    {
        $objectIri = $request->input('iri', '');
        $currentClassification = $objectIri ? $this->service->getObjectClassification($objectIri) : null;
        $classifications = $this->service->getClassificationLevels();

        return view('openric-auth::security.declassification', compact('objectIri', 'currentClassification', 'classifications'));
    }

    /**
     * #14 — Store declassification (POST).
     */
    public function declassifyStore(Request $request)
    {
        $request->validate(['object_iri' => 'required|string|max:2048']);

        $success = $this->service->declassifyObject(
            $request->input('object_iri'),
            auth()->id(),
            $request->input('new_classification_id') ? (int) $request->input('new_classification_id') : null,
            $request->input('reason')
        );

        return redirect()->route('admin.security-clearance.dashboard')
            ->with($success ? 'success' : 'error', $success ? 'Object declassified.' : 'Failed to declassify.');
    }

    /**
     * Schedule declassification (POST).
     */
    public function scheduleDeclassification(Request $request)
    {
        $request->validate([
            'object_iri' => 'required|string|max:2048',
            'target_classification_id' => 'required|integer|exists:security_classifications,id',
            'scheduled_date' => 'required|date|after:today',
        ]);

        $id = $this->service->scheduleDeclassification(
            $request->input('object_iri'),
            (int) $request->input('target_classification_id'),
            $request->input('scheduled_date'),
            auth()->id(),
            $request->input('reason')
        );

        return redirect()->route('admin.security-clearance.dashboard')
            ->with('success', "Declassification scheduled (ID: {$id}).");
    }

    // =========================================================================
    // Access Requests
    // =========================================================================

    /**
     * #15 — List access requests.
     */
    public function accessRequests(Request $request)
    {
        $status = $request->input('status', 'pending');
        $requests = $this->service->getAccessRequests($status ?: null);

        $stats = [
            'pending' => DB::table('security_access_requests')->where('status', 'pending')->count(),
            'approved_today' => DB::table('security_access_requests')->where('status', 'approved')->whereDate('reviewed_at', today())->count(),
            'denied_today' => DB::table('security_access_requests')->where('status', 'denied')->whereDate('reviewed_at', today())->count(),
            'total_this_month' => DB::table('security_access_requests')
                ->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
        ];

        return view('openric-auth::security.access-requests', compact('requests', 'stats', 'status'));
    }

    /**
     * #16 — Approve request (POST).
     */
    public function approveRequest(Request $request, int $id)
    {
        $notes = $request->input('notes', '');
        $this->service->reviewAccessRequest($id, 'approved', auth()->id(), $notes);

        // If approved, create an object access grant
        $accessRequest = DB::table('security_access_requests')->find($id);
        if ($accessRequest && $accessRequest->object_iri) {
            $expiresAt = $request->input('duration_hours')
                ? now()->addHours((int) $request->input('duration_hours'))->toDateTimeString()
                : null;

            $this->service->grantObjectAccess(
                $accessRequest->user_id,
                $accessRequest->object_iri,
                $accessRequest->request_type,
                auth()->id(),
                $id,
                $expiresAt
            );
        }

        return redirect()->route('admin.security.access-requests')
            ->with('success', 'Access request approved.');
    }

    /**
     * #17 — Deny request (POST).
     */
    public function denyRequest(Request $request, int $id)
    {
        $notes = $request->input('notes', '');
        $this->service->reviewAccessRequest($id, 'denied', auth()->id(), $notes);

        return redirect()->route('admin.security.access-requests')
            ->with('success', 'Access request denied.');
    }

    /**
     * #18 — View single request.
     */
    public function viewRequest(int $id)
    {
        $accessRequest = DB::table('security_access_requests as sar')
            ->leftJoin('users as u', 'sar.user_id', '=', 'u.id')
            ->leftJoin('security_classifications as sc', 'sc.id', '=', 'sar.classification_id')
            ->leftJoin('security_compartments as comp', 'comp.id', '=', 'sar.compartment_id')
            ->where('sar.id', $id)
            ->select(
                'sar.*',
                'u.username',
                'u.display_name as user_name',
                'u.email as user_email',
                'sc.name as classification_name',
                'sc.code as classification_code',
                'sc.color as classification_color',
                'comp.name as compartment_name'
            )
            ->first();

        if (!$accessRequest) {
            abort(404, 'Access request not found');
        }

        return view('openric-auth::security.view-request', compact('accessRequest'));
    }

    /**
     * #19 — Submit access request (POST) — for authenticated users.
     */
    public function submitAccessRequest(Request $request)
    {
        $request->validate([
            'object_iri' => 'required|string|max:2048',
            'request_type' => 'required|string|in:view,download,print,declassify,elevate',
            'justification' => 'required|string|max:1000',
        ]);

        $success = $this->service->submitAccessRequest(
            auth()->id(),
            $request->input('object_iri'),
            $request->input('request_type'),
            $request->input('justification'),
            $request->input('priority', 'normal'),
            (int) $request->input('duration_hours', 24)
        );

        return redirect()->route('security.my-requests')
            ->with($success ? 'success' : 'error', $success ? 'Access request submitted.' : 'Failed to submit request.');
    }

    /**
     * #20 — User's own access requests.
     */
    public function myRequests()
    {
        $userId = auth()->id();
        $currentClearance = $this->service->getUserClearance($userId);

        $requests = DB::table('security_access_requests as sar')
            ->leftJoin('security_classifications as sc', 'sc.id', '=', 'sar.classification_id')
            ->where('sar.user_id', $userId)
            ->select('sar.*', 'sc.name as requested_classification', 'sc.code as classification_code')
            ->orderByDesc('sar.created_at')
            ->get();

        $accessGrants = $this->service->getUserAccessGrants($userId);

        return view('openric-auth::security.my-requests', compact('currentClearance', 'requests', 'accessGrants'));
    }

    /**
     * #21 — Security Compliance Dashboard.
     */
    public function securityCompliance()
    {
        $stats = $this->service->getComplianceStats();
        $recentLogs = $this->service->getRecentComplianceLogs();

        return view('openric-auth::security.compliance', compact('stats', 'recentLogs'));
    }

    // =========================================================================
    // Security Audit
    // =========================================================================

    /**
     * #22 — Audit Dashboard.
     */
    public function auditDashboard(Request $request)
    {
        $period = $request->input('period', '30 days');
        $since = now()->sub(\DateInterval::createFromDateString($period));

        $stats = [
            'total_events' => 0,
            'security_events' => 0,
            'by_user' => collect(),
            'by_action' => collect(),
            'by_day' => collect(),
            'since' => $since->format('M j, Y H:i'),
        ];

        try {
            $baseQuery = DB::table('audit_log')->where('module', 'security')->where('created_at', '>=', $since);

            $stats['total_events'] = (clone $baseQuery)->count();
            $stats['security_events'] = $stats['total_events'];

            $stats['by_user'] = (clone $baseQuery)
                ->whereNotNull('username')
                ->select('username', DB::raw('COUNT(*) as count'))
                ->groupBy('username')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            $stats['by_action'] = (clone $baseQuery)
                ->select('action', DB::raw('COUNT(*) as count'))
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            $stats['by_day'] = (clone $baseQuery)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();
        } catch (\Exception) {
        }

        return view('openric-auth::security.audit-dashboard', compact('stats', 'period'));
    }

    /**
     * #23 — Audit Log Index with filters.
     */
    public function auditIndex(Request $request)
    {
        $filters = array_filter([
            'username' => $request->input('user'),
            'action' => $request->input('log_action'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ]);

        $page = max(1, (int) $request->input('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $result = $this->service->getAuditLog($filters, $limit, $offset);
        $logs = $result['logs'];
        $total = $result['total'];
        $totalPages = $total > 0 ? (int) ceil($total / $limit) : 1;

        $actions = DB::table('audit_log')
            ->where('module', 'security')
            ->distinct()
            ->pluck('action')
            ->filter()
            ->values()
            ->toArray();

        return view('openric-auth::security.audit-index', compact('logs', 'total', 'page', 'totalPages', 'filters', 'actions'));
    }

    /**
     * #24 — Export audit log as CSV.
     */
    public function auditExport()
    {
        $logs = $this->service->exportAuditLog();
        $filename = 'security_audit_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date/Time', 'User', 'Action', 'Object IRI', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->created_at,
                    $log->username,
                    $log->action,
                    $log->object_iri ?? 'N/A',
                    $log->ip_address ?? 'N/A',
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * #25 — Access denied page.
     */
    public function accessDenied(Request $request)
    {
        $objectIri = $request->input('iri', '');

        return view('openric-auth::security.denied', compact('objectIri'));
    }
}
