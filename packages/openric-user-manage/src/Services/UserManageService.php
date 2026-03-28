<?php

declare(strict_types=1);

namespace OpenRiC\UserManage\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenRiC\UserManage\Contracts\UserManageServiceInterface;

/**
 * User management service — adapted from Heratio ahg-user-manage UserService + UserBrowseService.
 *
 * Uses the existing users, roles, permissions, role_user tables.
 */
class UserManageService implements UserManageServiceInterface
{
    public function browseUsers(array $params = []): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;
        $sort = $params['sort'] ?? 'name';
        $sortDir = strtolower($params['sortDir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $search = trim($params['search'] ?? '');
        $status = $params['status'] ?? 'all';
        $roleFilter = $params['role'] ?? null;

        $query = DB::table('users')
            ->leftJoin('role_user', 'users.id', '=', 'role_user.user_id')
            ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
            ->select(
                'users.id',
                'users.username',
                'users.email',
                'users.name',
                'users.is_active',
                'users.last_login_at',
                'users.created_at',
                'users.updated_at',
                DB::raw("STRING_AGG(DISTINCT roles.name, ', ' ORDER BY roles.name) as role_names")
            )
            ->groupBy(
                'users.id',
                'users.username',
                'users.email',
                'users.name',
                'users.is_active',
                'users.last_login_at',
                'users.created_at',
                'users.updated_at'
            );

        // Status filter
        if ($status === 'active') {
            $query->where('users.is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('users.is_active', false);
        }

        // Role filter
        if ($roleFilter !== null) {
            $query->where('roles.id', (int) $roleFilter);
        }

        // Search filter
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('users.name', 'ILIKE', $like)
                  ->orWhere('users.username', 'ILIKE', $like)
                  ->orWhere('users.email', 'ILIKE', $like);
            });
        }

        // Count before pagination
        $countQuery = clone $query;
        $total = DB::table(DB::raw("({$countQuery->toSql()}) as sub"))
            ->mergeBindings($countQuery)
            ->count();

        // Sort
        $sortColumn = match ($sort) {
            'email' => 'users.email',
            'username' => 'users.username',
            'last_login' => 'users.last_login_at',
            'created' => 'users.created_at',
            default => 'users.name',
        };
        $query->orderBy($sortColumn, $sortDir);

        $rows = $query->offset($offset)->limit($limit)->get();

        $users = $rows->map(fn ($row): array => [
            'id' => $row->id,
            'username' => $row->username ?? '',
            'email' => $row->email ?? '',
            'name' => $row->name ?? '',
            'is_active' => (bool) $row->is_active,
            'roles' => $row->role_names ?? '',
            'last_login_at' => $row->last_login_at,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ])->toArray();

        return ['users' => $users, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function getUserDetail(int $userId): ?array
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return null;
        }

        $roles = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.user_id', $userId)
            ->select('roles.id', 'roles.name', 'roles.description')
            ->get()
            ->toArray();

        $permissions = DB::table('role_user')
            ->join('permission_role', 'role_user.role_id', '=', 'permission_role.role_id')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->where('role_user.user_id', $userId)
            ->select('permissions.name', 'permissions.description')
            ->distinct()
            ->get()
            ->toArray();

        // Clearance level: highest role's clearance_level
        $clearance = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.user_id', $userId)
            ->max('roles.clearance_level') ?? 0;

        // Recent activity from audit_log
        $activity = $this->getUserActivity($userId, 20);

        return [
            'id' => $user->id,
            'username' => $user->username ?? '',
            'email' => $user->email ?? '',
            'name' => $user->name ?? '',
            'is_active' => (bool) ($user->is_active ?? true),
            'last_login_at' => $user->last_login_at ?? null,
            'last_login_ip' => $user->last_login_ip ?? null,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'roles' => $roles,
            'permissions' => $permissions,
            'clearance_level' => (int) $clearance,
            'recent_activity' => $activity,
        ];
    }

    public function createUser(array $data): int
    {
        return DB::transaction(function () use ($data): int {
            $userId = (int) DB::table('users')->insertGetId([
                'username' => $data['username'],
                'email' => $data['email'],
                'name' => $data['name'] ?? $data['username'],
                'password' => Hash::make($data['password']),
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign roles
            if (!empty($data['roles'])) {
                foreach ((array) $data['roles'] as $roleId) {
                    DB::table('role_user')->insert([
                        'user_id' => $userId,
                        'role_id' => (int) $roleId,
                    ]);
                }
            }

            return $userId;
        });
    }

    public function updateUser(int $userId, array $data): void
    {
        DB::transaction(function () use ($userId, $data): void {
            $update = [];

            if (isset($data['username'])) {
                $update['username'] = $data['username'];
            }
            if (isset($data['email'])) {
                $update['email'] = $data['email'];
            }
            if (isset($data['name'])) {
                $update['name'] = $data['name'];
            }
            if (isset($data['is_active'])) {
                $update['is_active'] = (bool) $data['is_active'];
            }
            if (!empty($data['password'])) {
                $update['password'] = Hash::make($data['password']);
            }

            if (!empty($update)) {
                $update['updated_at'] = now();
                DB::table('users')->where('id', $userId)->update($update);
            }

            // Sync roles if provided
            if (isset($data['roles'])) {
                DB::table('role_user')->where('user_id', $userId)->delete();
                foreach ((array) $data['roles'] as $roleId) {
                    DB::table('role_user')->insert([
                        'user_id' => $userId,
                        'role_id' => (int) $roleId,
                    ]);
                }
            }
        });
    }

    public function deactivateUser(int $userId): void
    {
        DB::table('users')->where('id', $userId)->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);
    }

    public function resetPassword(int $userId): string
    {
        $tempPassword = Str::random(16);

        DB::table('users')->where('id', $userId)->update([
            'password' => Hash::make($tempPassword),
            'updated_at' => now(),
        ]);

        return $tempPassword;
    }

    public function getUserActivity(int $userId, int $limit = 50): array
    {
        try {
            return DB::table('audit_log')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->select('id', 'action', 'entity_type', 'entity_id', 'entity_title', 'ip_address', 'created_at')
                ->get()
                ->map(fn ($row): array => (array) $row)
                ->toArray();
        } catch (\Throwable $e) {
            Log::warning('UserManage: audit_log query failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserStats(): array
    {
        $total = DB::table('users')->count();
        $active = DB::table('users')->where('is_active', true)->count();
        $inactive = DB::table('users')->where('is_active', false)->count();

        $byRole = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('COUNT(DISTINCT role_user.user_id) as count'))
            ->groupBy('roles.name')
            ->orderByDesc('count')
            ->get()
            ->toArray();

        $recentLogins = DB::table('users')
            ->whereNotNull('last_login_at')
            ->where('last_login_at', '>=', now()->subDays(7))
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'by_role' => $byRole,
            'recent_logins_7d' => $recentLogins,
        ];
    }

    public function bulkAction(array $userIds, string $action, array $params = []): array
    {
        if (empty($userIds)) {
            return ['affected' => 0, 'message' => 'No users selected.'];
        }

        $affected = 0;

        DB::transaction(function () use ($userIds, $action, $params, &$affected): void {
            switch ($action) {
                case 'activate':
                    $affected = DB::table('users')
                        ->whereIn('id', $userIds)
                        ->update(['is_active' => true, 'updated_at' => now()]);
                    break;

                case 'deactivate':
                    $affected = DB::table('users')
                        ->whereIn('id', $userIds)
                        ->update(['is_active' => false, 'updated_at' => now()]);
                    break;

                case 'delete':
                    DB::table('role_user')->whereIn('user_id', $userIds)->delete();
                    $affected = DB::table('users')->whereIn('id', $userIds)->delete();
                    break;

                case 'assign_role':
                    $roleId = (int) ($params['role_id'] ?? 0);
                    if ($roleId > 0) {
                        foreach ($userIds as $uid) {
                            DB::table('role_user')->updateOrInsert(
                                ['user_id' => (int) $uid, 'role_id' => $roleId],
                                []
                            );
                        }
                        $affected = count($userIds);
                    }
                    break;

                case 'remove_role':
                    $roleId = (int) ($params['role_id'] ?? 0);
                    if ($roleId > 0) {
                        $affected = DB::table('role_user')
                            ->whereIn('user_id', $userIds)
                            ->where('role_id', $roleId)
                            ->delete();
                    }
                    break;
            }
        });

        return [
            'affected' => $affected,
            'message' => "Bulk action '{$action}' applied to {$affected} user(s).",
        ];
    }

    /**
     * Get all available roles for assignment.
     *
     * @return array[]
     */
    public function getAvailableRoles(): array
    {
        return DB::table('roles')
            ->select('id', 'name', 'description', 'clearance_level')
            ->orderBy('name')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->toArray();
    }
}
