<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Services;

use Illuminate\Support\Facades\Auth;
use OpenRiC\Auth\Contracts\AclServiceInterface;
use OpenRiC\Auth\Models\Role;
use OpenRiC\Auth\Models\User;

class AclService implements AclServiceInterface
{
    public const GRANT = 1;
    public const DENY = 0;
    public const INHERIT = -1;

    public const ACTIONS = [
        'read' => 'Read',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'publish' => 'Publish',
    ];

    public function check(?string $entityType, string $action, ?int $userId = null): bool
    {
        $user = $this->resolveUser($userId);
        if ($user === null) {
            return false;
        }

        $roles = $user->roles()->pluck('name')->toArray();

        if (in_array(Role::ADMINISTRATOR, $roles, true)) {
            return true;
        }

        if (in_array(Role::EDITOR, $roles, true)) {
            return in_array($action, ['create', 'read', 'update', 'delete', 'publish'], true);
        }

        if (in_array(Role::CONTRIBUTOR, $roles, true)) {
            return in_array($action, ['create', 'read', 'update'], true);
        }

        if (in_array(Role::TRANSLATOR, $roles, true)) {
            return in_array($action, ['read', 'update'], true);
        }

        if ($entityType !== null) {
            $permissionName = strtolower($entityType) . '.' . $action;

            return $user->hasPermission($permissionName);
        }

        return false;
    }

    public function canAdmin(?int $userId = null): bool
    {
        $user = $this->resolveUser($userId);
        if ($user === null) {
            return false;
        }

        return $user->isAdmin();
    }

    public function getUserPermissions(int $userId): array
    {
        $user = User::find($userId);
        if ($user === null) {
            return [];
        }

        $permissions = [];
        foreach ($user->roles as $role) {
            foreach ($role->permissions()->wherePivot('grant_type', self::GRANT)->get() as $permission) {
                $permissions[$permission->name] = $permission->label;
            }
        }

        return $permissions;
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        $user = User::find($userId);
        if ($user === null) {
            return false;
        }

        return $user->hasPermission($permission);
    }

    private function resolveUser(?int $userId): ?User
    {
        if ($userId !== null) {
            return User::find($userId);
        }

        $authUser = Auth::user();

        return $authUser instanceof User ? $authUser : null;
    }
}
