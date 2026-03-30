<?php

declare(strict_types=1);

namespace OpenRiC\UserManage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\UserManage\Services\UserManageService;

/**
 * User management controller — full Heratio parity.
 *
 * Adapted from Heratio ahg-user-manage UserController (774 LOC).
 * Covers: browse, show, create, edit, delete, password management, ACL (actor, IO,
 * repository, term, researcher), registration workflow, profile, clipboard, login.
 */
class UserManageController extends Controller
{
    public function __construct(
        private readonly UserManageService $service,
    ) {
    }

    // ════════════════════════════════════════════════════════════════
    // Browse / Index
    // ════════════════════════════════════════════════════════════════

    /**
     * Browse users with search, filter, sort, pagination.
     */
    public function browse(Request $request): View
    {
        $result = $this->service->browseUsers([
            'page'    => (int) $request->get('page', 1),
            'limit'   => (int) $request->get('limit', 25),
            'sort'    => $request->get('sort', 'name'),
            'sortDir' => $request->get('sortDir', 'asc'),
            'search'  => $request->get('subquery', $request->get('search', '')),
            'status'  => $request->get('status', $request->get('filter') === 'onlyInactive' ? 'inactive' : ($request->get('filter') === 'onlyActive' ? 'active' : 'active')),
            'role'    => $request->get('role'),
        ]);

        $stats = $this->service->getUserStats();
        $roles = $this->service->getAvailableRoles();

        return view('openric-user-manage::browse', [
            'users'       => $result['users'],
            'total'       => $result['total'],
            'page'        => $result['page'],
            'limit'       => $result['limit'],
            'pages'       => (int) ceil($result['total'] / max(1, $result['limit'])),
            'stats'       => $stats,
            'roles'       => $roles,
            'currentUserId' => auth()->id(),
            'sortOptions' => [
                'name'        => __('Name'),
                'lastUpdated' => __('Date modified'),
                'email'       => __('Email'),
                'username'    => __('Username'),
            ],
        ]);
    }

    /**
     * Alias: index → browse.
     */
    public function index(Request $request): View
    {
        return $this->browse($request);
    }

    // ════════════════════════════════════════════════════════════════
    // Show
    // ════════════════════════════════════════════════════════════════

    /**
     * Show single user detail by slug.
     */
    public function show(Request $request, string $slug): View
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $clearance = $this->service->getSecurityClearance($user['id']);
        $classifications = $this->service->getSecurityClassifications();

        return view('openric-user-manage::show', [
            'user'            => $user,
            'groups'          => collect($user['groups'] ?? []),
            'clearance'       => $clearance,
            'classifications' => $classifications,
        ]);
    }

    /**
     * Public user view (read-only).
     */
    public function userView(string $slug): View
    {
        return $this->show(request(), $slug);
    }

    // ════════════════════════════════════════════════════════════════
    // Create / Store
    // ════════════════════════════════════════════════════════════════

    /**
     * Create form.
     */
    public function create(): View
    {
        $roles = $this->service->getAvailableRoles();
        $languages = $this->service->getAvailableLanguages();

        return view('openric-user-manage::edit', [
            'user'               => null,
            'assignableGroups'   => $roles,
            'availableLanguages' => $languages,
        ]);
    }

    /**
     * Store new user.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username'                 => 'required|string|max:255',
            'email'                    => 'required|email|max:255',
            'password'                 => 'required|string|min:6',
            'confirm_password'         => 'nullable|same:password',
            'authorized_form_of_name'  => 'nullable|string|max:1024',
            'contact_telephone'        => 'nullable|string|max:255',
            'contact_fax'              => 'nullable|string|max:255',
            'contact_street_address'   => 'nullable|string|max:1024',
            'contact_city'             => 'nullable|string|max:1024',
            'contact_region'           => 'nullable|string|max:1024',
            'contact_postal_code'      => 'nullable|string|max:255',
            'contact_country_code'     => 'nullable|string|max:255',
            'contact_website'          => 'nullable|url|max:1024',
            'contact_note'             => 'nullable|string',
            'translate'                => 'nullable|array',
        ]);

        $data = $request->only([
            'username', 'email', 'password', 'authorized_form_of_name',
            'contact_telephone', 'contact_fax', 'contact_street_address',
            'contact_city', 'contact_region', 'contact_postal_code',
            'contact_country_code', 'contact_website', 'contact_note',
        ]);
        $data['active'] = $request->has('active') ? (int) $request->input('active') : 1;
        $data['is_active'] = $data['active'];
        $data['groups'] = $request->input('groups', []);
        $data['translate'] = $request->input('translate', []);
        $data['name'] = $data['authorized_form_of_name'] ?? $data['username'];
        $data['restApiKey'] = $request->input('restApiKey', '');
        $data['oaiApiKey'] = $request->input('oaiApiKey', '');

        $id = $this->service->createUser($data);
        $slug = $this->service->getSlug($id) ?? (string) $id;

        // Handle API keys
        if (!empty($data['restApiKey'])) {
            $this->service->manageApiKey($id, 'rest', $data['restApiKey']);
        }
        if (!empty($data['oaiApiKey'])) {
            $this->service->manageApiKey($id, 'oai', $data['oaiApiKey']);
        }

        return redirect()
            ->route('user.show', $slug)
            ->with('success', __('User created successfully.'));
    }

    // ════════════════════════════════════════════════════════════════
    // Edit / Update
    // ════════════════════════════════════════════════════════════════

    /**
     * Edit form.
     */
    public function edit(string $slug): View
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $roles = $this->service->getAvailableRoles();
        $languages = $this->service->getAvailableLanguages();

        return view('openric-user-manage::edit', [
            'user'               => $user,
            'assignableGroups'   => $roles,
            'availableLanguages' => $languages,
        ]);
    }

    /**
     * Update user.
     */
    public function update(Request $request, string $slug): RedirectResponse
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $request->validate([
            'username'                 => 'required|string|max:255',
            'email'                    => 'required|email|max:255',
            'password'                 => 'nullable|string|min:6',
            'confirm_password'         => 'nullable|same:password',
            'authorized_form_of_name'  => 'nullable|string|max:1024',
            'contact_telephone'        => 'nullable|string|max:255',
            'contact_fax'              => 'nullable|string|max:255',
            'contact_street_address'   => 'nullable|string|max:1024',
            'contact_city'             => 'nullable|string|max:1024',
            'contact_region'           => 'nullable|string|max:1024',
            'contact_postal_code'      => 'nullable|string|max:255',
            'contact_country_code'     => 'nullable|string|max:255',
            'contact_website'          => 'nullable|url|max:1024',
            'contact_note'             => 'nullable|string',
            'translate'                => 'nullable|array',
        ]);

        $data = $request->only([
            'username', 'email', 'password', 'authorized_form_of_name',
            'contact_telephone', 'contact_fax', 'contact_street_address',
            'contact_city', 'contact_region', 'contact_postal_code',
            'contact_country_code', 'contact_website', 'contact_note',
        ]);
        $data['active'] = $request->has('active') ? (int) $request->input('active') : 1;
        $data['is_active'] = $data['active'];
        $data['groups'] = $request->input('groups', []);
        $data['translate'] = $request->input('translate', []);
        $data['name'] = $data['authorized_form_of_name'] ?? $data['username'];
        $data['restApiKey'] = $request->input('restApiKey', '');
        $data['oaiApiKey'] = $request->input('oaiApiKey', '');

        $this->service->updateUser($user['id'], $data);

        return redirect()
            ->route('user.show', $slug)
            ->with('success', __('User updated successfully.'));
    }

    // ════════════════════════════════════════════════════════════════
    // Delete
    // ════════════════════════════════════════════════════════════════

    /**
     * Confirm delete page.
     */
    public function confirmDelete(string $slug): View
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404, 'User not found.');
        }

        return view('openric-user-manage::delete', ['user' => $user]);
    }

    /**
     * Destroy user.
     */
    public function destroy(string $slug): RedirectResponse
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $this->service->deleteUser($user['id']);

        return redirect()
            ->route('user.browse')
            ->with('success', __('User deleted successfully.'));
    }

    // ════════════════════════════════════════════════════════════════
    // Password
    // ════════════════════════════════════════════════════════════════

    /**
     * Password edit form (self-service).
     */
    public function passwordEdit(): View
    {
        $userId = auth()->id();
        $user = $this->service->getUserDetail((int) $userId);
        if (!$user) {
            abort(404, 'User not found.');
        }

        return view('openric-user-manage::password-edit', ['user' => $user]);
    }

    /**
     * Password reset (self-service POST).
     */
    public function passwordReset(Request $request): RedirectResponse|View
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'current_password'      => 'required|string',
                'password'              => 'required|string|min:6',
                'password_confirmation' => 'required|same:password',
            ]);

            $userId = (int) auth()->id();
            $success = $this->service->changePassword(
                $userId,
                $request->input('current_password'),
                $request->input('password'),
            );

            if (!$success) {
                return redirect()->back()->with('error', __('Current password is incorrect.'));
            }

            $slug = $this->service->getSlug($userId) ?? (string) $userId;

            return redirect()->route('user.show', $slug)
                ->with('success', __('Password updated successfully.'));
        }

        return $this->passwordEdit();
    }

    /**
     * Password reset confirm (token-based).
     */
    public function passwordResetConfirm(Request $request, string $token): View
    {
        return view('openric-user-manage::password-reset-confirm', ['token' => $token]);
    }

    /**
     * Admin: reset password for another user.
     */
    public function adminResetPassword(Request $request, string $slug): RedirectResponse
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $tempPassword = $this->service->resetPassword($user['id']);

        return redirect()->route('user.show', $slug)
            ->with('success', __('Password reset. Temporary password:') . " {$tempPassword}");
    }

    // ════════════════════════════════════════════════════════════════
    // Profile (self-service)
    // ════════════════════════════════════════════════════════════════

    /**
     * User profile page (self-service).
     */
    public function profile(): View
    {
        $userId = (int) auth()->id();
        $user = $this->service->getUserDetail($userId);
        if (!$user) {
            abort(404, 'User not found.');
        }

        return view('openric-user-manage::show', [
            'user'   => $user,
            'groups' => collect($user['groups'] ?? []),
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    // Clipboard
    // ════════════════════════════════════════════════════════════════

    /**
     * User clipboard page.
     */
    public function clipboard(): View
    {
        $userId = (int) auth()->id();
        $items = $this->service->getClipboardItems($userId);

        return view('openric-user-manage::clipboard', ['items' => collect($items)]);
    }

    /**
     * Remove item from clipboard.
     */
    public function removeClipboardItem(string $itemId): RedirectResponse
    {
        $userId = (int) auth()->id();
        $this->service->removeClipboardItem($userId, (int) $itemId);

        return redirect()->route('user.clipboard')->with('success', 'Item removed from clipboard.');
    }

    /**
     * Clear all clipboard items.
     */
    public function clearClipboard(): RedirectResponse
    {
        $userId = (int) auth()->id();
        $this->service->clearClipboard($userId);

        return redirect()->route('user.clipboard')->with('success', 'Clipboard cleared.');
    }

    // ════════════════════════════════════════════════════════════════
    // Login
    // ════════════════════════════════════════════════════════════════

    /**
     * Login page dispatcher (delegates to standard, CAS, or ext-auth variant).
     */
    public function login(Request $request): View
    {
        return view('openric-user-manage::login');
    }

    // ════════════════════════════════════════════════════════════════
    // Registration
    // ════════════════════════════════════════════════════════════════

    /**
     * Self-registration form.
     */
    public function register(Request $request): View
    {
        return view('openric-user-manage::registration-register');
    }

    /**
     * Email verification.
     */
    public function verify(string $token): View
    {
        return view('openric-user-manage::registration-verify', ['token' => $token]);
    }

    /**
     * Admin: pending registrations list.
     */
    public function registrationPending(Request $request): View
    {
        $statusFilter = $request->get('status');
        $result = $this->service->getRegistrationRequests($statusFilter);

        return view('openric-user-manage::registration-pending', $result);
    }

    /**
     * Admin: approve registration.
     */
    public function registrationApprove(Request $request): JsonResponse
    {
        $requestId = (int) $request->input('request_id');
        $notes = $request->input('admin_notes', '');
        $groupId = $request->input('group_id') ? (int) $request->input('group_id') : null;
        $adminId = (int) auth()->id();

        $result = $this->service->approveRegistration($requestId, $groupId, $notes, $adminId);

        return response()->json($result);
    }

    /**
     * Admin: reject registration.
     */
    public function registrationReject(Request $request): JsonResponse
    {
        $requestId = (int) $request->input('request_id');
        $notes = $request->input('admin_notes', '');
        $adminId = (int) auth()->id();

        $result = $this->service->rejectRegistration($requestId, $notes, $adminId);

        return response()->json($result);
    }

    // ════════════════════════════════════════════════════════════════
    // ACL — Actor Permissions
    // ════════════════════════════════════════════════════════════════

    /**
     * View actor ACL matrix.
     */
    public function indexActorAcl(string $slug): View
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404);
        }

        $data = $this->service->buildAclData($user['id'], 'QubitActor');
        $data['actorNames'] = $data['objectNames'] ?? [];

        return view('openric-user-manage::index-actor-acl', $data);
    }

    /**
     * Edit actor ACL permissions.
     */
    public function editActorAcl(Request $request, string $slug): View|RedirectResponse
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404);
        }

        if ($request->isMethod('post')) {
            $this->service->saveAclPermissions(
                $user['id'],
                $request->input('permissions', []),
                [
                    'actor_id'   => $request->input('new_actor_id'),
                    'action'     => $request->input('new_action', ''),
                    'grant_deny' => $request->input('new_grant_deny', 'grant'),
                ],
            );
            return redirect()->route('user.indexActorAcl', ['slug' => $slug])
                ->with('success', __('Actor permissions saved.'));
        }

        $data = $this->service->buildEditAclData($user['id'], 'QubitActor');
        $data['actors'] = $this->service->getActorsForAcl();

        return view('openric-user-manage::edit-actor-acl', $data);
    }

    // ════════════════════════════════════════════════════════════════
    // ACL — Information Object Permissions
    // ════════════════════════════════════════════════════════════════

    public function indexInformationObjectAcl(string $slug): View
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404);
        }

        $data = $this->service->buildAclData($user['id'], 'QubitInformationObject');
        $data['ioNames'] = $data['objectNames'] ?? [];

        return view('openric-user-manage::index-information-object-acl', $data);
    }

    public function editInformationObjectAcl(Request $request, string $slug): View|RedirectResponse
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404);
        }

        if ($request->isMethod('post')) {
            $this->service->saveAclPermissions(
                $user['id'],
                $request->input('permissions', []),
                [
                    'object_id'  => $request->input('new_object_id'),
                    'action'     => $request->input('new_action', ''),
                    'grant_deny' => $request->input('new_grant_deny', 'grant'),
                ],
            );
            return redirect()->route('user.indexInformationObjectAcl', ['slug' => $slug])
                ->with('success', __('Information object permissions saved.'));
        }

        $data = $this->service->buildEditAclData($user['id'], 'QubitInformationObject');
        $data['repositories'] = $this->service->getRepositoriesForAcl();

        return view('openric-user-manage::edit-information-object-acl', $data);
    }

    // ════════════════════════════════════════════════════════════════
    // ACL — Repository Permissions
    // ════════════════════════════════════════════════════════════════

    public function indexRepositoryAcl(string $slug): View
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404);
        }

        $data = $this->service->buildAclData($user['id'], 'QubitRepository');
        $data['repoNames'] = $data['objectNames'] ?? [];

        return view('openric-user-manage::index-repository-acl', $data);
    }

    public function editRepositoryAcl(Request $request, string $slug): View|RedirectResponse
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404);
        }

        if ($request->isMethod('post')) {
            $this->service->saveAclPermissions(
                $user['id'],
                $request->input('permissions', []),
                [
                    'repository_id' => $request->input('new_repository_id'),
                    'action'        => $request->input('new_action', ''),
                    'grant_deny'    => $request->input('new_grant_deny', 'grant'),
                ],
            );
            return redirect()->route('user.indexRepositoryAcl', ['slug' => $slug])
                ->with('success', __('Repository permissions saved.'));
        }

        $data = $this->service->buildEditAclData($user['id'], 'QubitRepository');
        $data['repositories'] = $this->service->getRepositoriesForAcl();

        return view('openric-user-manage::edit-repository-acl', $data);
    }

    // ════════════════════════════════════════════════════════════════
    // ACL — Term / Taxonomy Permissions
    // ════════════════════════════════════════════════════════════════

    public function indexTermAcl(string $slug): View
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404);
        }

        $data = $this->service->buildAclData($user['id'], 'QubitTerm');
        $data['termNames'] = $data['objectNames'] ?? [];

        return view('openric-user-manage::index-term-acl', $data);
    }

    public function editTermAcl(Request $request, string $slug): View|RedirectResponse
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404);
        }

        if ($request->isMethod('post')) {
            $this->service->saveAclPermissions(
                $user['id'],
                $request->input('permissions', []),
                [
                    'taxonomy_id' => $request->input('new_taxonomy_id'),
                    'action'      => $request->input('new_action', ''),
                    'grant_deny'  => $request->input('new_grant_deny', 'grant'),
                ],
            );
            return redirect()->route('user.indexTermAcl', ['slug' => $slug])
                ->with('success', __('Taxonomy permissions saved.'));
        }

        $data = $this->service->buildEditAclData($user['id'], 'QubitTerm');
        $data['taxonomies'] = $this->service->getTaxonomiesForAcl();

        return view('openric-user-manage::edit-term-acl', $data);
    }

    // ════════════════════════════════════════════════════════════════
    // ACL — Researcher Permissions
    // ════════════════════════════════════════════════════════════════

    public function editResearcherAcl(Request $request, string $slug): View|RedirectResponse
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404);
        }

        if ($request->isMethod('post')) {
            $this->service->saveAclPermissions(
                $user['id'],
                $request->input('permissions', []),
                [],
            );
            return redirect()->route('user.show', ['slug' => $slug])
                ->with('success', __('Researcher permissions saved.'));
        }

        // Get researcher-specific permissions
        $permissions = collect();
        try {
            $permissions = \Illuminate\Support\Facades\DB::table('acl_permission')
                ->where('user_id', $user['id'])
                ->where(function ($q): void {
                    $q->where('action', 'ILIKE', 'research%')
                      ->orWhere('action', 'ILIKE', 'researcher%');
                })
                ->get();

            foreach ($permissions as $perm) {
                $perm->object_name = null;
            }
        } catch (\Throwable) {
        }

        return view('openric-user-manage::edit-researcher-acl', [
            'user'        => $user,
            'permissions' => $permissions,
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    // Security Clearance
    // ════════════════════════════════════════════════════════════════

    /**
     * Grant or update security clearance.
     */
    public function grantClearance(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id'           => 'required|integer',
            'classification_id' => 'required|integer',
            'expires_at'        => 'nullable|date',
            'notes'             => 'nullable|string',
        ]);

        $userId = (int) $request->input('user_id');
        $this->service->grantSecurityClearance(
            $userId,
            (int) $request->input('classification_id'),
            $request->input('expires_at'),
            $request->input('notes', ''),
        );

        $slug = $this->service->getSlug($userId) ?? (string) $userId;

        return redirect()->route('user.show', $slug)
            ->with('success', __('Security clearance updated.'));
    }

    /**
     * Revoke security clearance.
     */
    public function revokeClearance(int $id): RedirectResponse
    {
        $this->service->revokeSecurityClearance($id);
        $slug = $this->service->getSlug($id) ?? (string) $id;

        return redirect()->route('user.show', $slug)
            ->with('success', __('Security clearance revoked.'));
    }

    // ════════════════════════════════════════════════════════════════
    // Activity
    // ════════════════════════════════════════════════════════════════

    /**
     * User activity log.
     */
    public function activity(string $slug): View
    {
        $user = $this->service->getUserBySlug($slug);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $activity = $this->service->getUserActivity($user['id'], 200);

        return view('openric-user-manage::activity', [
            'user'     => $user,
            'activity' => $activity,
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    // Bulk Actions
    // ════════════════════════════════════════════════════════════════

    /**
     * Bulk action on multiple users.
     */
    public function bulkAction(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer',
            'action'     => 'required|string|in:activate,deactivate,delete,assign_role,remove_role',
            'role_id'    => 'nullable|integer',
        ]);

        $result = $this->service->bulkAction(
            array_map('intval', $request->input('user_ids')),
            $request->input('action'),
            $request->only('role_id'),
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->route('user.browse')
            ->with('success', $result['message']);
    }

    // ════════════════════════════════════════════════════════════════
    // Read-only mode page
    // ════════════════════════════════════════════════════════════════

    /**
     * Read-only mode notice page.
     */
    public function readOnly(): View
    {
        return view('openric-user-manage::read-only');
    }
}
