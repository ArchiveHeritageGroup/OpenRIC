<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Adapted from /usr/share/nginx/heratio/packages/ahg-user-manage/src/Controllers/UserController.php (774 lines)
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('users')
            ->leftJoin('role_user', 'users.id', '=', 'role_user.user_id')
            ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
            ->select('users.*', DB::raw("STRING_AGG(roles.label, ', ' ORDER BY roles.level) as role_names"))
            ->groupBy('users.id');

        $status = $request->get('status', 'active');
        if ($status === 'active') {
            $query->where('users.active', true)->whereNull('users.deleted_at');
        } elseif ($status === 'inactive') {
            $query->where('users.active', false);
        }

        if ($request->get('q')) {
            $q = '%' . $request->get('q') . '%';
            $query->where(function ($qb) use ($q) {
                $qb->where('users.username', 'ILIKE', $q)
                    ->orWhere('users.email', 'ILIKE', $q)
                    ->orWhere('users.display_name', 'ILIKE', $q);
            });
        }

        $users = $query->orderBy('users.username')->paginate(25);

        return view('openric-auth::users.index', [
            'users' => $users,
            'status' => $status,
        ]);
    }

    public function show(int $id): View
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) abort(404);

        $roles = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.user_id', $id)
            ->select('roles.*')
            ->get();

        $clearance = DB::table('user_security_clearance as usc')
            ->join('security_classifications as sc', 'usc.classification_id', '=', 'sc.id')
            ->where('usc.user_id', $id)
            ->first();

        $recentActivity = DB::table('audit_log')
            ->where('user_id', $id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('openric-auth::users.show', compact('user', 'roles', 'clearance', 'recentActivity'));
    }

    public function create(): View
    {
        $roles = DB::table('roles')->orderBy('level')->get();
        return view('openric-auth::users.create', ['roles' => $roles]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'display_name' => 'nullable|string|max:255',
            'active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $userId = DB::table('users')->insertGetId([
            'uuid' => Str::uuid()->toString(),
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'display_name' => $data['display_name'] ?? $data['username'],
            'active' => $data['active'] ?? true,
            'locale' => 'en',
            'timezone' => 'Africa/Johannesburg',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!empty($data['roles'])) {
            foreach ($data['roles'] as $roleId) {
                DB::table('role_user')->insert([
                    'user_id' => $userId, 'role_id' => $roleId,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(int $id): View
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) abort(404);

        $roles = DB::table('roles')->orderBy('level')->get();
        $userRoleIds = DB::table('role_user')->where('user_id', $id)->pluck('role_id')->toArray();

        return view('openric-auth::users.edit', compact('user', 'roles', 'userRoleIds'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) abort(404);

        $data = $request->validate([
            'username' => "required|string|max:100|unique:users,username,{$id}",
            'email' => "required|email|max:255|unique:users,email,{$id}",
            'password' => 'nullable|string|min:8|confirmed',
            'display_name' => 'nullable|string|max:255',
            'active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $update = [
            'username' => $data['username'],
            'email' => $data['email'],
            'display_name' => $data['display_name'] ?? $data['username'],
            'active' => $data['active'] ?? true,
            'updated_at' => now(),
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        DB::table('users')->where('id', $id)->update($update);

        // Sync roles
        DB::table('role_user')->where('user_id', $id)->delete();
        foreach ($data['roles'] ?? [] as $roleId) {
            DB::table('role_user')->insert([
                'user_id' => $id, 'role_id' => $roleId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return redirect()->route('admin.users.show', $id)->with('success', 'User updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        DB::table('users')->where('id', $id)->update(['active' => false, 'deleted_at' => now()]);
        return redirect()->route('admin.users.index')->with('success', 'User deactivated.');
    }

    public function roles(): View
    {
        $roles = DB::table('roles')
            ->select('roles.*', DB::raw('(SELECT COUNT(*) FROM role_user WHERE role_user.role_id = roles.id) as user_count'))
            ->orderBy('level')
            ->get();

        return view('openric-auth::users.roles', ['roles' => $roles]);
    }
}
