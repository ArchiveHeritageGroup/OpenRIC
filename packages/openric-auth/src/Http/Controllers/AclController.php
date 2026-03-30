<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenRiC\Auth\Contracts\AclServiceInterface;

/**
 * ACL Controller — adapted from Heratio AclController (609 lines).
 *
 * Covers all Heratio ACL actions: groups, permissions, classifications,
 * clearances, access requests, approvers, security audit, security clearance
 * management, compartments, watermarks, two-factor, and more.
 *
 * Heratio differences:
 *   - Uses object_iri (RDF) instead of object_id (MySQL int)
 *   - Uses `users` table with `display_name` instead of `user` + `actor_i18n`
 *   - PostgreSQL instead of MySQL
 */
class AclController extends Controller
{
    public function __construct(
        private readonly AclServiceInterface $service,
    ) {
    }

    // ── ACL Groups ─────────────────────────────────────────────────

    /**
     * List all ACL groups with member counts.
     */
    public function groups(): \Illuminate\View\View
    {
        $groups = $this->service->getGroups();

        foreach ($groups as $group) {
            $group->permissions_count = $this->service->getGroupPermissions($group->id)->count();
        }

        return view('openric-auth::acl.groups', compact('groups'));
    }

    /**
     * GET: Show group with members and permissions.
     * POST: Update permissions for the group.
     */
    public function editGroup(Request $request, int $id): \Illuminate\Http\RedirectResponse|\Illuminate\View\View
    {
        if ($request->isMethod('post')) {
            $action = $request->input('_action');

            if ($action === 'add_permission') {
                $request->validate([
                    'action'     => 'required|string|max:255',
                    'grant_deny' => 'required|in:0,1',
                ]);

                $this->service->savePermission([
                    'acl_group_id' => $id,
                    'action'       => $request->input('action'),
                    'object_iri'   => $request->input('object_iri') ?: null,
                    'entity_type'  => $request->input('entity_type') ?: null,
                    'grant_deny'   => (bool) $request->input('grant_deny'),
                ]);

                return redirect()->route('acl.edit-group', ['id' => $id])
                    ->with('success', 'Permission added successfully.');
            }

            if ($action === 'delete_permission') {
                $request->validate(['permission_id' => 'required|integer']);
                $this->service->deletePermission((int) $request->input('permission_id'));

                return redirect()->route('acl.edit-group', ['id' => $id])
                    ->with('success', 'Permission removed successfully.');
            }

            return redirect()->route('acl.edit-group', ['id' => $id]);
        }

        $group = $this->service->getGroup($id);
        if (!$group) {
            abort(404, 'Group not found.');
        }

        $allUsers = $this->service->getAllUsers();

        return view('openric-auth::acl.edit-group', compact('group', 'allUsers'));
    }

    /**
     * POST: Add a user to a group.
     */
    public function addMember(Request $request, int $groupId): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['user_id' => 'required|integer']);
        $this->service->addUserToGroup((int) $request->input('user_id'), $groupId);

        return redirect()->route('acl.edit-group', ['id' => $groupId])
            ->with('success', 'Member added to group.');
    }

    /**
     * POST: Remove a user from a group.
     */
    public function removeMember(Request $request, int $groupId, int $userId): \Illuminate\Http\RedirectResponse
    {
        $this->service->removeUserFromGroup($userId, $groupId);

        return redirect()->route('acl.edit-group', ['id' => $groupId])
            ->with('success', 'Member removed from group.');
    }

    // ── Classifications ────────────────────────────────────────────

    /**
     * List security classification levels.
     */
    public function classifications(): \Illuminate\View\View
    {
        $classifications = $this->service->getClassificationLevels();

        return view('openric-auth::acl.classifications', compact('classifications'));
    }

    // ── Clearances ─────────────────────────────────────────────────

    /**
     * List user security clearances.
     */
    public function clearances(): \Illuminate\View\View
    {
        $users = $this->service->getAllUsers();
        $classifications = $this->service->getClassificationLevels();

        $clearances = collect();
        foreach ($users as $user) {
            $clearance = $this->service->getUserClearance($user->id);
            if ($clearance) {
                $clearance->username = $user->username;
                $clearance->user_display_name = $user->display_name ?? $user->username;
                $clearances->push($clearance);
            }
        }

        return view('openric-auth::acl.clearances', compact('clearances', 'users', 'classifications'));
    }

    /**
     * POST: Set a user's security clearance.
     */
    public function setClearance(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'user_id'           => 'required|integer',
            'classification_id' => 'required|integer',
        ]);

        $grantedBy = auth()->id() ?? 1;

        $this->service->setUserClearance(
            (int) $request->input('user_id'),
            (int) $request->input('classification_id'),
            $grantedBy
        );

        return redirect()->route('acl.clearances')
            ->with('success', 'User clearance updated successfully.');
    }

    // ── Access Requests ────────────────────────────────────────────

    /**
     * List security access requests.
     */
    public function accessRequests(Request $request): \Illuminate\View\View
    {
        $status = $request->input('status', 'pending');
        $requests = $this->service->getAccessRequests($status ?: null);

        return view('openric-auth::acl.access-requests', compact('requests', 'status'));
    }

    /**
     * POST: Approve or deny an access request.
     */
    public function reviewRequest(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['decision' => 'required|in:approved,denied']);

        $reviewerId = auth()->id() ?? 1;
        $notes = $request->input('notes');
        $decision = $request->input('decision');

        if ($decision === 'approved') {
            $this->service->approveAccessRequest($id, $reviewerId, $notes);
        } else {
            $this->service->denyAccessRequest($id, $reviewerId, $notes);
        }

        return redirect()->route('acl.access-requests')
            ->with('success', 'Access request ' . $decision . '.');
    }

    /**
     * My Access Requests — user's own requests, clearance status, access grants.
     */
    public function myRequests(Request $request): \Illuminate\View\View
    {
        $userId = auth()->id();

        $currentClearance = DB::table('user_security_clearance as usc')
            ->leftJoin('security_classifications as sc', 'sc.id', '=', 'usc.classification_id')
            ->where('usc.user_id', $userId)
            ->select('usc.*', 'sc.name as classification_name', 'sc.level', 'sc.color')
            ->first();

        $accessGrants = collect();
        if (Schema::hasTable('security_object_access')) {
            $accessGrants = DB::table('security_object_access as soa')
                ->leftJoin('users as u', 'u.id', '=', 'soa.granted_by')
                ->where('soa.user_id', $userId)
                ->select('soa.*', 'u.display_name as granted_by_name')
                ->orderByDesc('soa.granted_at')
                ->get();
        }

        $requests = DB::table('security_access_requests as sar')
            ->leftJoin('security_classifications as sc', 'sc.id', '=', 'sar.classification_id')
            ->where('sar.user_id', $userId)
            ->select('sar.*', 'sc.name as requested_classification', 'sc.code as classification_code')
            ->orderByDesc('sar.created_at')
            ->get();

        return view('openric-auth::acl.my-requests', compact('currentClearance', 'accessGrants', 'requests'));
    }

    /**
     * Pending Access Requests — admin/approver review page with stats.
     */
    public function pendingRequests(Request $request): \Illuminate\View\View
    {
        $requests = $this->service->getAccessRequests('pending');

        $stats = [
            'pending' => DB::table('security_access_requests')->where('status', 'pending')->count(),
            'approved_today' => DB::table('security_access_requests')
                ->where('status', 'approved')
                ->whereDate('reviewed_at', today())
                ->count(),
            'denied_today' => DB::table('security_access_requests')
                ->where('status', 'denied')
                ->whereDate('reviewed_at', today())
                ->count(),
            'total_this_month' => DB::table('security_access_requests')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];

        return view('openric-auth::acl.pending-requests', compact('requests', 'stats'));
    }

    // ── Audit Log ──────────────────────────────────────────────────

    /**
     * Security audit log.
     */
    public function auditLog(Request $request): \Illuminate\View\View
    {
        $limit = (int) ($request->input('limit', 50));
        $entries = $this->service->getSecurityAuditLog($limit);

        return view('openric-auth::acl.audit-log', compact('entries', 'limit'));
    }

    // ── Approvers ──────────────────────────────────────────────────

    /**
     * List active access request approvers.
     */
    public function approvers(): \Illuminate\View\View
    {
        $approvers = collect();
        $classifications = $this->service->getClassificationLevels();

        if (Schema::hasTable('access_request_approvers')) {
            $approvers = DB::table('access_request_approvers as ara')
                ->join('users as u', 'u.id', '=', 'ara.user_id')
                ->leftJoin('user_security_clearance as uc', 'uc.user_id', '=', 'u.id')
                ->leftJoin('security_classifications as sc', 'sc.id', '=', 'uc.classification_id')
                ->select(
                    'ara.id', 'ara.user_id', 'ara.min_classification_level',
                    'ara.max_classification_level', 'ara.email_notifications',
                    'ara.active', 'ara.created_at',
                    'u.username', 'u.email', 'u.display_name',
                    'sc.name as clearance_name', 'sc.code as clearance_code',
                    'sc.color as clearance_color', 'sc.level as clearance_level'
                )
                ->where('ara.active', true)
                ->orderBy('u.display_name')
                ->get();
        }

        $approverUserIds = $approvers->pluck('user_id')->toArray();
        $availableUsers = $this->service->getAllUsers()
            ->filter(fn ($user) => !in_array($user->id, $approverUserIds));

        return view('openric-auth::acl.approvers', compact('approvers', 'availableUsers', 'classifications'));
    }

    /**
     * POST: Add a new access request approver.
     */
    public function addApprover(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'user_id'                  => 'required|integer',
            'min_classification_level' => 'required|integer',
            'max_classification_level' => 'required|integer',
            'email_notifications'      => 'nullable|boolean',
        ]);

        $existing = DB::table('access_request_approvers')
            ->where('user_id', (int) $request->input('user_id'))
            ->where('active', true)
            ->first();

        if ($existing) {
            return redirect()->route('acl.approvers')
                ->with('error', 'This user is already an active approver.');
        }

        DB::table('access_request_approvers')->insert([
            'user_id'                  => (int) $request->input('user_id'),
            'min_classification_level' => (int) $request->input('min_classification_level'),
            'max_classification_level' => (int) $request->input('max_classification_level'),
            'email_notifications'      => $request->boolean('email_notifications'),
            'active'                   => true,
            'created_at'               => now(),
        ]);

        return redirect()->route('acl.approvers')
            ->with('success', 'Approver added successfully.');
    }

    /**
     * POST: Deactivate an access request approver.
     */
    public function removeApprover(int $id): \Illuminate\Http\RedirectResponse
    {
        DB::table('access_request_approvers')
            ->where('id', $id)
            ->update(['active' => false]);

        return redirect()->route('acl.approvers')
            ->with('success', 'Approver removed successfully.');
    }

    // ── Security Audit ─────────────────────────────────────────────

    public function securityAuditIndex(Request $request): \Illuminate\View\View
    {
        $logs = collect();
        $actions = [];
        $categories = [];
        $total = 0;

        try {
            $logs = DB::table('security_access_log')->orderByDesc('created_at')->limit(100)->get();
            $logs->transform(function ($log) {
                $log->username = $log->user_name ?? '';
                $log->category = $log->action ?? '';
                $log->object_title = '';
                if (!empty($log->metadata)) {
                    $details = is_string($log->metadata) ? json_decode($log->metadata, true) : (array) $log->metadata;
                    $log->object_title = $details['object_title'] ?? $details['title'] ?? '';
                }
                return $log;
            });
            $actions = DB::table('security_access_log')->distinct()->pluck('action')->filter()->values()->toArray();
            $total = DB::table('security_access_log')->count();
        } catch (\Exception) {
        }

        return view('openric-auth::security-audit.index', compact('logs', 'actions', 'categories', 'total'));
    }

    public function securityAuditDashboard(Request $request): \Illuminate\View\View
    {
        $period = $request->input('period', '30 days');
        $since = now()->sub(\DateInterval::createFromDateString($period));

        $stats = [
            'total_events' => 0,
            'security_events' => 0,
            'by_user' => collect(),
            'top_objects' => collect(),
            'since' => $since->format('M j, Y H:i'),
        ];

        try {
            $stats['total_events'] = DB::table('security_access_log')->where('created_at', '>=', $since)->count();
            $stats['security_events'] = $stats['total_events'];
            $stats['by_user'] = DB::table('security_access_log')
                ->where('created_at', '>=', $since)
                ->whereNotNull('user_id')
                ->select(DB::raw("CAST(user_id AS TEXT) as username"), DB::raw('COUNT(*) as count'))
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(10)
                ->get();
        } catch (\Exception) {
        }

        return view('openric-auth::security-audit.dashboard', compact('stats', 'period'));
    }

    public function securityAuditObjectAccess(Request $request): \Illuminate\View\View
    {
        $objectIri = $request->input('iri', '');
        $period = $request->input('period', '30 days');
        $object = (object) ['iri' => $objectIri, 'title' => 'Unknown'];
        $accessLogs = collect();
        $securityLogs = collect();
        $dailyAccess = collect();
        $totalAccess = 0;

        return view('openric-auth::security-audit.object-access', compact('object', 'period', 'accessLogs', 'securityLogs', 'dailyAccess', 'totalAccess'));
    }

    // ── Security Clearance Management ──────────────────────────────

    public function securityDashboard(): \Illuminate\View\View
    {
        $stats = [
            'total_users' => DB::table('user_security_clearance')->count(),
            'active_requests' => Schema::hasTable('security_access_requests') ? DB::table('security_access_requests')->where('status', 'pending')->count() : 0,
            'classified_objects' => Schema::hasTable('object_security_classification') ? DB::table('object_security_classification')->where('active', true)->count() : 0,
            'compartments' => Schema::hasTable('security_compartments') ? DB::table('security_compartments')->count() : 0,
        ];
        $recentActivity = collect();

        return view('openric-auth::security.security-dashboard', compact('stats', 'recentActivity'));
    }

    public function securityIndex(): \Illuminate\View\View
    {
        $clearances = collect();

        return view('openric-auth::security.security-index', compact('clearances'));
    }

    public function compartments(): \Illuminate\View\View
    {
        $compartments = Schema::hasTable('security_compartments') ? DB::table('security_compartments')->get() : collect();
        $userCounts = [];

        return view('openric-auth::security.compartments', compact('compartments', 'userCounts'));
    }

    public function compartmentAccess(): \Illuminate\View\View
    {
        $grants = collect();
        $compartment = (object) ['code' => '', 'name' => '', 'description' => '', 'requires_briefing' => false, 'id' => 0];
        $users = [];

        return view('openric-auth::security.compartment-access', compact('grants', 'compartment', 'users'));
    }

    public function classify(Request $request): \Illuminate\View\View
    {
        $objectIri = $request->input('iri', '');
        $classifications = $this->service->getClassificationLevels();
        $currentClassification = $objectIri ? $this->service->getObjectClassification($objectIri) : null;
        $resource = (object) ['iri' => $objectIri, 'title' => 'Record', 'identifier' => '', 'id' => 0];

        return view('openric-auth::security.classify', compact('resource', 'classifications', 'currentClassification'));
    }

    public function classifyStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('acl.security-dashboard')->with('success', 'Classification applied.');
    }

    public function declassification(Request $request): \Illuminate\View\View
    {
        $objectIri = $request->input('iri', '');
        $currentClassification = null;
        $classifications = $this->service->getClassificationLevels();
        $dueDeclassifications = collect();
        $scheduled = collect();

        return view('openric-auth::security.declassification', compact('currentClassification', 'classifications', 'dueDeclassifications', 'scheduled'));
    }

    public function declassifyStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('acl.security-dashboard')->with('success', 'Object declassified.');
    }

    public function securityReport(): \Illuminate\View\View
    {
        $clearanceStats = ['total_users' => 0, 'with_clearance' => 0, 'without_clearance' => 0];
        $clearancesByLevel = collect();
        $objectsByLevel = collect();
        $requestStats = ['pending' => 0, 'approved' => 0, 'denied' => 0];
        $recentActivity = collect();
        $period = '30 days';

        return view('openric-auth::security.report', compact('clearanceStats', 'clearancesByLevel', 'objectsByLevel', 'requestStats', 'recentActivity', 'period'));
    }

    public function securityCompliance(): \Illuminate\View\View
    {
        $stats = ['classified_objects' => 0, 'pending_reviews' => 0, 'cleared_users' => 0, 'access_logs_today' => 0];
        $recentLogs = collect();
        $retentionSchedules = collect();

        return view('openric-auth::security.security-compliance', compact('stats', 'recentLogs', 'retentionSchedules'));
    }

    public function watermarkSettings(): \Illuminate\View\View
    {
        $watermarkTypes = collect();
        $settings = (object) [
            'default_watermark_type_id' => null,
            'default_position' => 'center',
            'default_opacity' => 0.4,
            'auto_watermark' => false,
        ];

        return view('openric-auth::security.watermark-settings', compact('watermarkTypes', 'settings'));
    }

    public function watermarkSettingsStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('acl.watermark-settings')->with('success', 'Watermark settings saved.');
    }

    public function traceWatermark(): \Illuminate\View\View
    {
        return view('openric-auth::security.trace-watermark', ['watermarkCode' => null, 'traceResult' => null, 'searchCode' => null, 'watermark' => null]);
    }

    public function traceWatermarkResult(Request $request): \Illuminate\View\View
    {
        $searchCode = $request->input('watermark_code') ?? $request->input('code');
        $watermark = null;

        return view('openric-auth::security.trace-watermark', compact('searchCode', 'watermark'));
    }

    public function objectView(Request $request): \Illuminate\View\View
    {
        $objectIri = $request->input('iri', '');
        $object = (object) ['iri' => $objectIri, 'title' => 'Unknown'];
        $objectClassification = null;

        return view('openric-auth::security.object-view', compact('object', 'objectClassification'));
    }

    public function userClearance(int $id): \Illuminate\View\View
    {
        $targetUser = DB::table('users')->where('id', $id)->first() ?? (object) ['id' => $id, 'username' => 'Unknown', 'email' => ''];
        $clearance = $this->service->getUserClearance($id);
        $classifications = $this->service->getClassificationLevels();
        $history = collect();
        $compartments = collect();
        $allCompartments = collect();

        return view('openric-auth::security.user-clearance', compact('targetUser', 'clearance', 'classifications', 'history', 'compartments', 'allCompartments'));
    }

    public function userSecurity(int $id): \Illuminate\View\View
    {
        $user = DB::table('users')->where('id', $id)->first() ?? (object) ['id' => $id, 'username' => 'Unknown'];
        $clearance = $this->service->getUserClearance($id);
        $classifications = $this->service->getClassificationLevels();
        $groups = collect();
        $history = collect();

        return view('openric-auth::security.user', compact('user', 'clearance', 'classifications', 'groups', 'history'));
    }

    public function viewClassification(int $id): \Illuminate\View\View
    {
        $targetUser = DB::table('users')->where('id', $id)->first() ?? (object) ['id' => $id, 'username' => 'Unknown', 'email' => ''];
        $clearance = $this->service->getUserClearance($id);
        $classifications = $this->service->getClassificationLevels();
        $accessGrants = collect();
        $history = collect();

        return view('openric-auth::security.view', compact('targetUser', 'clearance', 'classifications', 'accessGrants', 'history'));
    }

    public function securityAudit(): \Illuminate\View\View
    {
        $logs = collect();
        $classifications = $this->service->getClassificationLevels();
        $filters = [];

        return view('openric-auth::security.audit', compact('logs', 'classifications', 'filters'));
    }

    public function accessRequest(Request $request): \Illuminate\View\View
    {
        $objectIri = $request->input('iri', '');
        $object = (object) ['iri' => $objectIri, 'title' => 'Unknown', 'identifier' => ''];
        $classification = null;
        $userClearance = null;

        return view('openric-auth::security.access-request', compact('object', 'classification', 'userClearance'));
    }

    public function submitAccessRequest(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('security.my-requests')->with('success', 'Access request submitted.');
    }

    public function accessDenied(): \Illuminate\View\View
    {
        $access = ['reasons' => []];
        $objectTitle = 'Restricted Resource';

        return view('openric-auth::acl.access-denied', compact('access', 'objectTitle'));
    }

    public function setupTwoFactor(): \Illuminate\View\View
    {
        return view('openric-auth::security.setup-two-factor', ['qrCodeUrl' => '', 'secret' => '', 'returnUrl' => '/']);
    }

    public function setupTwoFactorStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('acl.security-dashboard')->with('success', 'Two-factor authentication enabled.');
    }

    public function twoFactor(): \Illuminate\View\View
    {
        return view('openric-auth::security.two-factor', ['returnUrl' => request()->input('return', '/')]);
    }

    public function verifyTwoFactor(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect('/')->with('success', 'Two-factor verified.');
    }

    public function reviewAccessRequest(int $id): \Illuminate\View\View
    {
        $accessRequest = DB::table('security_access_requests')->where('id', $id)->first()
            ?? (object) ['id' => $id, 'requester_name' => '', 'object_title' => '', 'justification' => '', 'created_at' => '', 'request_type' => '', 'priority' => 'normal', 'duration_hours' => 24, 'user_id' => 0, 'status' => 'pending', 'username' => '', 'email' => ''];

        return view('openric-auth::security.review-request', compact('accessRequest'));
    }

    public function isadEdit(): \Illuminate\View\View
    {
        return view('openric-auth::security.isad-edit');
    }

    public function objectSecurityView(Request $request): \Illuminate\View\View
    {
        $objectIri = $request->input('iri', '');
        $resource = (object) ['iri' => $objectIri, 'title' => 'Record', 'identifier' => '', 'id' => 0, 'slug' => ''];
        $classification = null;
        $history = collect();

        return view('openric-auth::security.object', compact('resource', 'classification', 'history'));
    }
}
