<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenRiC\Auth\Contracts\AclServiceInterface;
use OpenRiC\Auth\Models\Role;
use OpenRiC\Auth\Models\User;

/**
 * Access Control List service — adapted from Heratio AclService (498 lines).
 *
 * Provides dual-layer permissions:
 *   1. Role-based: Administrator > Editor > Contributor > Translator
 *   2. Object-level ACL: fine-grained per-object grants/denials via acl_groups
 *
 * Also handles security classification checks, access requests, and audit logging.
 *
 * Heratio differences:
 *   - Heratio uses `user` table + `actor_i18n` for display names
 *   - OpenRiC uses `users` table with `display_name` column directly
 *   - Heratio uses integer `object_id`; OpenRiC uses string `object_iri` (RDF)
 *   - Heratio has `acl_group_i18n` for multilingual; OpenRiC uses single `name` column
 */
class AclService implements AclServiceInterface
{
    /**
     * Permission grant types.
     */
    public const GRANT = true;
    public const DENY = false;

    /**
     * Available actions — matches Heratio's action set.
     */
    public const ACTIONS = [
        'create' => 'Create',
        'read' => 'Read',
        'update' => 'Update',
        'delete' => 'Delete',
        'publish' => 'Publish',
        'execute' => 'Execute',
    ];

    // ========================================================================
    // Permission checking
    // ========================================================================

    /**
     * {@inheritDoc}
     *
     * Resolution order:
     *   1. Administrator role → always true
     *   2. Role-based check (Editor, Contributor, Translator)
     *   3. Object-specific ACL permissions (object_iri match)
     *   4. Entity-type ACL permissions (entity_type match, no object_iri)
     *   5. Global ACL permissions (no entity_type, no object_iri)
     *
     * At each ACL level, deny takes precedence over grant.
     * Object-specific rules override entity-type rules which override global rules.
     */
    public function check(int $userId, string $action, ?string $entityType = null, ?string $objectIri = null): bool
    {
        $user = User::find($userId);
        if ($user === null) {
            return false;
        }

        // 1. Administrator bypasses all ACL
        $roles = $user->roles()->pluck('name')->toArray();

        if (in_array(Role::ADMINISTRATOR, $roles, true)) {
            return true;
        }

        // 2. Role-based shortcut
        if ($this->checkRolePermission($roles, $action)) {
            return true;
        }

        // 3. Named permission check (entity.action pattern)
        if ($entityType !== null) {
            $permissionName = strtolower($entityType) . '.' . $action;
            if ($user->hasPermission($permissionName)) {
                return true;
            }
        }

        // 4. ACL group-based object-level permissions
        return $this->checkAclPermission($userId, $action, $entityType, $objectIri);
    }

    /**
     * {@inheritDoc}
     */
    public function canAdmin(?int $userId = null): bool
    {
        $user = $this->resolveUser($userId);
        if ($user === null) {
            return false;
        }

        return $user->isAdmin();
    }

    /**
     * {@inheritDoc}
     */
    public function getUserPermissions(int $userId): array
    {
        $user = User::with('roles.permissions')->find($userId);
        if ($user === null) {
            return [];
        }

        $permissions = [];

        // Role-based permissions
        foreach ($user->roles as $role) {
            foreach ($role->permissions()->wherePivot('grant_type', 1)->get() as $permission) {
                $permissions[$permission->name] = $permission->label;
            }
        }

        // ACL group permissions
        $groupIds = DB::table('acl_user_group')
            ->where('user_id', $userId)
            ->pluck('acl_group_id')
            ->toArray();

        if (!empty($groupIds)) {
            $aclPerms = DB::table('acl_object_permissions')
                ->whereIn('acl_group_id', $groupIds)
                ->where('grant_deny', true)
                ->select('action', 'entity_type')
                ->distinct()
                ->get();

            foreach ($aclPerms as $perm) {
                $key = ($perm->entity_type ? $perm->entity_type . '.' : '') . $perm->action;
                $permissions[$key] = ucfirst($perm->action) . ($perm->entity_type ? ' ' . ucfirst($perm->entity_type) : '');
            }
        }

        return $permissions;
    }

    /**
     * {@inheritDoc}
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        $user = User::find($userId);
        if ($user === null) {
            return false;
        }

        // Check role-based permission
        if ($user->hasPermission($permission)) {
            return true;
        }

        // Check ACL group permission
        [$entityType, $action] = array_pad(explode('.', $permission, 2), 2, null);
        if ($action !== null) {
            return $this->checkAclPermission($userId, $action, $entityType);
        }

        return false;
    }

    // ========================================================================
    // ACL group management
    // ========================================================================

    /**
     * {@inheritDoc}
     */
    public function getGroups(): Collection
    {
        return DB::table('acl_groups as g')
            ->leftJoin(
                DB::raw('(SELECT acl_group_id, COUNT(*) as member_count FROM acl_user_group GROUP BY acl_group_id) as mc'),
                'mc.acl_group_id',
                '=',
                'g.id'
            )
            ->select(
                'g.id',
                'g.name',
                'g.description',
                'g.parent_id',
                'g.is_active',
                'g.created_at',
                'g.updated_at',
                DB::raw('COALESCE(mc.member_count, 0) as member_count')
            )
            ->orderBy('g.name')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getGroup(int $id): ?object
    {
        $group = DB::table('acl_groups')
            ->select('id', 'name', 'description', 'parent_id', 'is_active', 'created_at', 'updated_at')
            ->where('id', $id)
            ->first();

        if (!$group) {
            return null;
        }

        // Get members with display names
        $group->members = DB::table('acl_user_group as ug')
            ->join('users as u', 'u.id', '=', 'ug.user_id')
            ->select(
                'ug.id as membership_id',
                'ug.user_id',
                'u.username',
                'u.email',
                'u.display_name',
                'ug.created_at as joined_at'
            )
            ->where('ug.acl_group_id', $id)
            ->orderBy('u.display_name')
            ->orderBy('u.username')
            ->get();

        // Get ACL permissions
        $group->permissions = $this->getGroupPermissions($id);

        return $group;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupPermissions(int $groupId): Collection
    {
        return DB::table('acl_object_permissions')
            ->select('id', 'user_id', 'acl_group_id', 'object_iri', 'entity_type', 'action', 'grant_deny', 'conditional', 'conditions', 'priority', 'created_at', 'updated_at')
            ->where('acl_group_id', $groupId)
            ->orderBy('entity_type')
            ->orderBy('action')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function savePermission(array $data): int
    {
        $now = now()->toDateTimeString();

        // Check if permission already exists for this group+action+entity_type+object combo
        $query = DB::table('acl_object_permissions')
            ->where('action', $data['action'] ?? null);

        if (isset($data['acl_group_id'])) {
            $query->where('acl_group_id', $data['acl_group_id']);
        } elseif (isset($data['user_id'])) {
            $query->where('user_id', $data['user_id']);
        }

        if (isset($data['object_iri'])) {
            $query->where('object_iri', $data['object_iri']);
        } else {
            $query->whereNull('object_iri');
        }

        if (isset($data['entity_type'])) {
            $query->where('entity_type', $data['entity_type']);
        } else {
            $query->whereNull('entity_type');
        }

        $existing = $query->first();

        if ($existing) {
            DB::table('acl_object_permissions')
                ->where('id', $existing->id)
                ->update([
                    'grant_deny' => $data['grant_deny'] ?? true,
                    'conditional' => $data['conditional'] ?? false,
                    'conditions' => isset($data['conditions']) ? json_encode($data['conditions']) : null,
                    'priority' => $data['priority'] ?? 0,
                    'updated_at' => $now,
                ]);

            return $existing->id;
        }

        return DB::table('acl_object_permissions')->insertGetId([
            'user_id' => $data['user_id'] ?? null,
            'acl_group_id' => $data['acl_group_id'] ?? null,
            'object_iri' => $data['object_iri'] ?? null,
            'entity_type' => $data['entity_type'] ?? null,
            'action' => $data['action'] ?? null,
            'grant_deny' => $data['grant_deny'] ?? true,
            'conditional' => $data['conditional'] ?? false,
            'conditions' => isset($data['conditions']) ? json_encode($data['conditions']) : null,
            'priority' => $data['priority'] ?? 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function deletePermission(int $id): bool
    {
        return DB::table('acl_object_permissions')->where('id', $id)->delete() > 0;
    }

    // ========================================================================
    // User-group membership
    // ========================================================================

    /**
     * {@inheritDoc}
     */
    public function getUserGroups(int $userId): Collection
    {
        return DB::table('acl_user_group as ug')
            ->join('acl_groups as g', 'g.id', '=', 'ug.acl_group_id')
            ->select(
                'ug.id as membership_id',
                'g.id as group_id',
                'g.name',
                'g.description',
                'ug.created_at as joined_at'
            )
            ->where('ug.user_id', $userId)
            ->orderBy('g.name')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function addUserToGroup(int $userId, int $groupId): int
    {
        // Prevent duplicate entries
        $existing = DB::table('acl_user_group')
            ->where('user_id', $userId)
            ->where('acl_group_id', $groupId)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('acl_user_group')->insertGetId([
            'user_id' => $userId,
            'acl_group_id' => $groupId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function removeUserFromGroup(int $userId, int $groupId): bool
    {
        return DB::table('acl_user_group')
            ->where('user_id', $userId)
            ->where('acl_group_id', $groupId)
            ->delete() > 0;
    }

    // ========================================================================
    // Security classification
    // ========================================================================

    /**
     * {@inheritDoc}
     */
    public function getClassificationLevels(): Collection
    {
        return DB::table('security_classifications')
            ->select(
                'id', 'code', 'level', 'name', 'color',
                'requires_2fa', 'watermark_required',
                'download_allowed', 'print_allowed', 'copy_allowed',
                'handling_instructions', 'banner_text', 'retention_years',
                'active', 'created_at', 'updated_at'
            )
            ->where('active', true)
            ->orderBy('level')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getObjectClassification(string $objectIri): ?object
    {
        return DB::table('object_security_classification as osc')
            ->join('security_classifications as sc', 'sc.id', '=', 'osc.classification_id')
            ->select(
                'osc.*',
                'sc.code as classification_code',
                'sc.name as classification_name',
                'sc.level as classification_level',
                'sc.color as classification_color',
                'sc.banner_text',
                'sc.handling_instructions'
            )
            ->where('osc.object_iri', $objectIri)
            ->where('osc.active', true)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function setObjectClassification(string $objectIri, int $classificationId, int $userId): int
    {
        $now = now()->toDateTimeString();

        // Deactivate any existing classification
        DB::table('object_security_classification')
            ->where('object_iri', $objectIri)
            ->where('active', true)
            ->update(['active' => false, 'updated_at' => $now]);

        $id = DB::table('object_security_classification')->insertGetId([
            'object_iri' => $objectIri,
            'classification_id' => $classificationId,
            'classified_by' => $userId,
            'classified_at' => $now,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Log the classification change
        $this->logSecurityAction($userId, 'classify', 'object', $objectIri, $classificationId);

        return $id;
    }

    /**
     * {@inheritDoc}
     */
    public function getUserClearance(int $userId): ?object
    {
        return DB::table('user_security_clearance as uc')
            ->join('security_classifications as sc', 'sc.id', '=', 'uc.classification_id')
            ->leftJoin('users as u', 'u.id', '=', 'uc.granted_by')
            ->select(
                'uc.*',
                'sc.code as classification_code',
                'sc.name as classification_name',
                'sc.level as classification_level',
                'sc.color as classification_color',
                'u.display_name as granted_by_name'
            )
            ->where('uc.user_id', $userId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function setUserClearance(int $userId, int $classificationId, int $grantedBy): void
    {
        $now = now()->toDateTimeString();

        $existing = DB::table('user_security_clearance')
            ->where('user_id', $userId)
            ->first();

        $previousClassificationId = $existing->classification_id ?? null;

        if ($existing) {
            DB::table('user_security_clearance')
                ->where('user_id', $userId)
                ->update([
                    'classification_id' => $classificationId,
                    'granted_by' => $grantedBy,
                    'granted_at' => $now,
                    'updated_at' => $now,
                ]);
            $action = 'clearance_updated';
        } else {
            DB::table('user_security_clearance')->insert([
                'user_id' => $userId,
                'classification_id' => $classificationId,
                'granted_by' => $grantedBy,
                'granted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $action = 'clearance_granted';
        }

        // Log the change
        DB::table('user_security_clearance_log')->insert([
            'user_id' => $userId,
            'action' => $action,
            'previous_classification_id' => $previousClassificationId,
            'classification_id' => $classificationId,
            'changed_by' => $grantedBy,
            'notes' => null,
            'created_at' => $now,
        ]);

        $this->logSecurityAction($grantedBy, $action, 'user', (string) $userId, $classificationId);
    }

    // ========================================================================
    // Access requests
    // ========================================================================

    /**
     * {@inheritDoc}
     */
    public function getAccessRequests(?string $status = 'pending'): Collection
    {
        $query = DB::table('security_access_requests as sar')
            ->leftJoin('users as u', 'u.id', '=', 'sar.user_id')
            ->leftJoin('security_classifications as sc', 'sc.id', '=', 'sar.classification_id')
            ->leftJoin('security_compartments as comp', 'comp.id', '=', 'sar.compartment_id')
            ->select(
                'sar.*',
                'u.display_name as user_name',
                'u.username',
                'u.email as user_email',
                'sc.name as classification_name',
                'sc.code as classification_code',
                'sc.color as classification_color',
                'comp.name as compartment_name',
                'comp.code as compartment_code'
            );

        if ($status !== null) {
            $query->where('sar.status', $status);
        }

        return $query->orderByDesc('sar.created_at')->get();
    }

    /**
     * {@inheritDoc}
     */
    public function approveAccessRequest(int $id, int $reviewerId, ?string $notes = null): bool
    {
        $now = now()->toDateTimeString();

        $result = DB::table('security_access_requests')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => $now,
                'review_notes' => $notes,
                'updated_at' => $now,
            ]) > 0;

        if ($result) {
            $request = DB::table('security_access_requests')->find($id);
            if ($request) {
                $this->logSecurityAction($reviewerId, 'approve', 'access_request', (string) $id);
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function denyAccessRequest(int $id, int $reviewerId, ?string $notes = null): bool
    {
        $now = now()->toDateTimeString();

        $result = DB::table('security_access_requests')
            ->where('id', $id)
            ->update([
                'status' => 'denied',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => $now,
                'review_notes' => $notes,
                'updated_at' => $now,
            ]) > 0;

        if ($result) {
            $this->logSecurityAction($reviewerId, 'deny', 'access_request', (string) $id);
        }

        return $result;
    }

    // ========================================================================
    // Audit
    // ========================================================================

    /**
     * {@inheritDoc}
     */
    public function getSecurityAuditLog(int $limit = 50): Collection
    {
        return DB::table('security_access_log as sal')
            ->leftJoin('users as u', 'u.id', '=', 'sal.user_id')
            ->leftJoin('security_classifications as sc', 'sc.id', '=', 'sal.classification_id')
            ->leftJoin('security_compartments as comp', 'comp.id', '=', 'sal.compartment_id')
            ->select(
                'sal.id',
                'sal.user_id',
                'sal.action',
                'sal.target_type',
                'sal.target_id',
                'sal.classification_id',
                'sal.compartment_id',
                'sal.ip_address',
                'sal.metadata',
                'sal.created_at',
                'u.display_name',
                'u.username',
                'sc.name as classification_name',
                'sc.code as classification_code',
                'comp.name as compartment_name'
            )
            ->orderByDesc('sal.created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllUsers(): Collection
    {
        return DB::table('users')
            ->select('id', 'username', 'email', 'display_name', 'active')
            ->where('active', true)
            ->orderBy('display_name')
            ->orderBy('username')
            ->get();
    }

    // ========================================================================
    // Private helpers
    // ========================================================================

    /**
     * Check if a set of roles grants an action.
     */
    private function checkRolePermission(array $roles, string $action): bool
    {
        if (in_array(Role::EDITOR, $roles, true)) {
            return in_array($action, ['create', 'read', 'update', 'delete', 'publish'], true);
        }

        if (in_array(Role::CONTRIBUTOR, $roles, true)) {
            return in_array($action, ['create', 'read', 'update'], true);
        }

        if (in_array(Role::TRANSLATOR, $roles, true)) {
            return in_array($action, ['read', 'update'], true);
        }

        return false;
    }

    /**
     * Check ACL group-based permissions for a user.
     *
     * Resolution: object-specific > entity-type > global.
     * At each level, deny (grant_deny=false) takes precedence over grant (grant_deny=true).
     */
    private function checkAclPermission(int $userId, string $action, ?string $entityType = null, ?string $objectIri = null): bool
    {
        $groupIds = DB::table('acl_user_group')
            ->where('user_id', $userId)
            ->pluck('acl_group_id')
            ->toArray();

        if (empty($groupIds)) {
            return false;
        }

        $query = DB::table('acl_object_permissions')
            ->where('action', $action)
            ->where(function ($q) use ($userId, $groupIds) {
                $q->whereIn('acl_group_id', $groupIds)
                    ->orWhere('user_id', $userId);
            });

        $permissions = $query->get();

        if ($permissions->isEmpty()) {
            return false;
        }

        // Level 1: Object-specific permissions (highest priority)
        if ($objectIri !== null) {
            $objectSpecific = $permissions->where('object_iri', $objectIri);
            if ($objectSpecific->isNotEmpty()) {
                // Deny takes precedence
                if ($objectSpecific->contains('grant_deny', false)) {
                    return false;
                }
                return $objectSpecific->contains('grant_deny', true);
            }
        }

        // Level 2: Entity-type permissions
        if ($entityType !== null) {
            $entitySpecific = $permissions->where('entity_type', $entityType)->whereNull('object_iri');
            if ($entitySpecific->isNotEmpty()) {
                if ($entitySpecific->contains('grant_deny', false)) {
                    return false;
                }
                return $entitySpecific->contains('grant_deny', true);
            }
        }

        // Level 3: Global permissions (no entity_type, no object_iri)
        $global = $permissions->whereNull('entity_type')->whereNull('object_iri');
        if ($global->isNotEmpty()) {
            if ($global->contains('grant_deny', false)) {
                return false;
            }
            return $global->contains('grant_deny', true);
        }

        return false;
    }

    /**
     * Log a security action to the security_access_log table.
     */
    private function logSecurityAction(
        int $userId,
        string $action,
        string $targetType,
        ?string $targetId = null,
        ?int $classificationId = null,
        ?int $compartmentId = null,
    ): void {
        DB::table('security_access_log')->insert([
            'user_id' => $userId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'classification_id' => $classificationId,
            'compartment_id' => $compartmentId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Resolve a user from ID or current auth.
     */
    private function resolveUser(?int $userId): ?User
    {
        if ($userId !== null) {
            return User::find($userId);
        }

        $authUser = Auth::user();

        return $authUser instanceof User ? $authUser : null;
    }
}
