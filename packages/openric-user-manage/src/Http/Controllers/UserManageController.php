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
 * User management controller — adapted from Heratio ahg-user-manage UserController.
 */
class UserManageController extends Controller
{
    public function __construct(
        private readonly UserManageService $service,
    ) {
    }

    /**
     * Browse users with search, filter, sort.
     */
    public function index(Request $request): View
    {
        $result = $this->service->browseUsers([
            'page' => (int) $request->get('page', 1),
            'limit' => (int) $request->get('limit', 25),
            'sort' => $request->get('sort', 'name'),
            'sortDir' => $request->get('sortDir', 'asc'),
            'search' => $request->get('search', ''),
            'status' => $request->get('status', 'all'),
            'role' => $request->get('role'),
        ]);

        $stats = $this->service->getUserStats();
        $roles = $this->service->getAvailableRoles();

        return view('openric-user-manage::index', [
            'users' => $result['users'],
            'total' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
            'pages' => (int) ceil($result['total'] / max(1, $result['limit'])),
            'stats' => $stats,
            'roles' => $roles,
        ]);
    }

    /**
     * Show single user detail.
     */
    public function show(int $id): View
    {
        $user = $this->service->getUserDetail($id);
        if (!$user) {
            abort(404, 'User not found.');
        }

        return view('openric-user-manage::show', ['user' => $user]);
    }

    /**
     * Create form.
     */
    public function create(): View
    {
        $roles = $this->service->getAvailableRoles();
        return view('openric-user-manage::create', ['roles' => $roles]);
    }

    /**
     * Store new user.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        $id = $this->service->createUser($request->only([
            'username', 'email', 'name', 'password', 'roles', 'is_active',
        ]));

        return redirect()->route('user-manage.show', $id)
            ->with('success', 'User created successfully.');
    }

    /**
     * Edit form.
     */
    public function edit(int $id): View
    {
        $user = $this->service->getUserDetail($id);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $roles = $this->service->getAvailableRoles();

        return view('openric-user-manage::edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update user.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = $this->service->getUserDetail($id);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $request->validate([
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        $this->service->updateUser($id, $request->only([
            'username', 'email', 'name', 'password', 'roles', 'is_active',
        ]));

        return redirect()->route('user-manage.show', $id)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Deactivate user.
     */
    public function deactivate(Request $request, int $id): RedirectResponse
    {
        $this->service->deactivateUser($id);

        return redirect()->route('user-manage.show', $id)
            ->with('success', 'User deactivated.');
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        $tempPassword = $this->service->resetPassword($id);

        return redirect()->route('user-manage.show', $id)
            ->with('success', "Password reset. Temporary password: {$tempPassword}");
    }

    /**
     * User activity log.
     */
    public function activity(int $id): View
    {
        $user = $this->service->getUserDetail($id);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $activity = $this->service->getUserActivity($id, 200);

        return view('openric-user-manage::activity', [
            'user' => $user,
            'activity' => $activity,
        ]);
    }

    /**
     * Bulk action on multiple users.
     */
    public function bulkAction(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer',
            'action' => 'required|string|in:activate,deactivate,delete,assign_role,remove_role',
            'role_id' => 'nullable|integer',
        ]);

        $result = $this->service->bulkAction(
            array_map('intval', $request->input('user_ids')),
            $request->input('action'),
            $request->only('role_id')
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->route('user-manage.index')
            ->with('success', $result['message']);
    }
}
