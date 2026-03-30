<?php

declare(strict_types=1);

namespace OpenRiC\UserManage\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenRiC\UserManage\Contracts\UserManageServiceInterface;

/**
 * User management service — full Heratio parity.
 *
 * Adapted from Heratio ahg-user-manage UserService (502 LOC) + UserBrowseService (166 LOC).
 * Uses the AtoM class-table-inheritance pattern: object → actor → user, with slug, contact_information,
 * acl_user_group, acl_permission, property tables. Also supports the modern OpenRiC `users` table
 * when the legacy tables are not present.
 *
 * PostgreSQL: uses ILIKE for search, STRING_AGG for aggregation.
 */
class UserManageService implements UserManageServiceInterface
{
    // ════════════════════════════════════════════════════════════════
    // Browse & Search
    // ════════════════════════════════════════════════════════════════

    public function browseUsers(array $params = []): array
    {
        $page    = max(1, (int) ($params['page'] ?? 1));
        $limit   = max(1, min(100, (int) ($params['limit'] ?? 25)));
        $offset  = ($page - 1) * $limit;
        $sort    = $params['sort'] ?? 'name';
        $sortDir = strtolower($params['sortDir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $search  = trim($params['search'] ?? '');
        $status  = $params['status'] ?? 'all';
        $roleFilter = $params['role'] ?? null;

        try {
            // Try AtoM CTI schema first (object → actor → user → slug)
            if ($this->hasLegacyTables()) {
                return $this->browseLegacyUsers($page, $limit, $offset, $sort, $sortDir, $search, $status, $roleFilter);
            }
        } catch (\Throwable $e) {
            Log::debug('UserManage: legacy browse failed, falling back to modern: ' . $e->getMessage());
        }

        return $this->browseModernUsers($page, $limit, $offset, $sort, $sortDir, $search, $status, $roleFilter);
    }

    /**
     * Browse using AtoM class-table-inheritance schema (user → actor → object → slug + acl_user_group).
     */
    private function browseLegacyUsers(
        int $page,
        int $limit,
        int $offset,
        string $sort,
        string $sortDir,
        string $search,
        string $status,
        ?string $roleFilter,
    ): array {
        $culture = app()->getLocale();

        $query = DB::table('user')
            ->join('actor_i18n', function ($j) use ($culture): void {
                $j->on('user.id', '=', 'actor_i18n.id')
                  ->where('actor_i18n.culture', '=', $culture);
            })
            ->join('object', 'user.id', '=', 'object.id')
            ->join('slug', 'user.id', '=', 'slug.object_id')
            ->leftJoin('acl_user_group', 'user.id', '=', 'acl_user_group.user_id')
            ->leftJoin('acl_group_i18n', function ($j) use ($culture): void {
                $j->on('acl_user_group.group_id', '=', 'acl_group_i18n.id')
                  ->where('acl_group_i18n.culture', '=', $culture);
            })
            ->select(
                'user.id',
                'actor_i18n.authorized_form_of_name as name',
                'user.username',
                'user.email',
                'user.active',
                'object.updated_at',
                'object.created_at',
                'slug.slug',
                DB::raw("STRING_AGG(DISTINCT acl_group_i18n.name, ', ' ORDER BY acl_group_i18n.name) as role_names"),
            )
            ->groupBy(
                'user.id',
                'actor_i18n.authorized_form_of_name',
                'user.username',
                'user.email',
                'user.active',
                'object.updated_at',
                'object.created_at',
                'slug.slug',
            );

        // Status filter
        if ($status === 'active') {
            $query->where('user.active', 1);
        } elseif ($status === 'inactive') {
            $query->where('user.active', 0);
        }

        // Role filter — filter by group_id
        if ($roleFilter !== null) {
            $query->where('acl_user_group.group_id', (int) $roleFilter);
        }

        // Search
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('actor_i18n.authorized_form_of_name', 'ILIKE', $like)
                  ->orWhere('user.username', 'ILIKE', $like)
                  ->orWhere('user.email', 'ILIKE', $like);
            });
        }

        // Count
        $countQuery = DB::table('user')
            ->join('actor_i18n', function ($j) use ($culture): void {
                $j->on('user.id', '=', 'actor_i18n.id')
                  ->where('actor_i18n.culture', '=', $culture);
            })
            ->join('object', 'user.id', '=', 'object.id')
            ->join('slug', 'user.id', '=', 'slug.object_id');

        if ($status === 'active') {
            $countQuery->where('user.active', 1);
        } elseif ($status === 'inactive') {
            $countQuery->where('user.active', 0);
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $countQuery->where(function ($q) use ($like): void {
                $q->where('actor_i18n.authorized_form_of_name', 'ILIKE', $like)
                  ->orWhere('user.username', 'ILIKE', $like)
                  ->orWhere('user.email', 'ILIKE', $like);
            });
        }
        $total = $countQuery->count();

        // Sort
        $sortColumn = match ($sort) {
            'email'       => 'user.email',
            'username'    => 'user.username',
            'lastUpdated' => 'object.updated_at',
            'created'     => 'object.created_at',
            default       => 'actor_i18n.authorized_form_of_name',
        };
        $query->orderBy($sortColumn, $sortDir);

        $rows = $query->offset($offset)->limit($limit)->get();

        $users = $rows->map(fn ($row): array => [
            'id'          => $row->id,
            'username'    => $row->username ?? '',
            'email'       => $row->email ?? '',
            'name'        => $row->name ?? '',
            'is_active'   => (bool) ($row->active ?? true),
            'roles'       => $row->role_names ?? '',
            'slug'        => $row->slug ?? '',
            'created_at'  => $row->created_at,
            'updated_at'  => $row->updated_at,
        ])->toArray();

        return ['users' => $users, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * Browse using modern OpenRiC users table.
     */
    private function browseModernUsers(
        int $page,
        int $limit,
        int $offset,
        string $sort,
        string $sortDir,
        string $search,
        string $status,
        ?string $roleFilter,
    ): array {
        $query = DB::table('users')
            ->leftJoin('role_user', 'users.id', '=', 'role_user.user_id')
            ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
            ->select(
                'users.id',
                'users.username',
                'users.email',
                'users.display_name',
                'users.active',
                'users.last_login_at',
                'users.created_at',
                'users.updated_at',
                DB::raw("STRING_AGG(DISTINCT roles.name, ', ' ORDER BY roles.name) as role_names"),
            )
            ->groupBy(
                'users.id',
                'users.username',
                'users.email',
                'users.display_name',
                'users.active',
                'users.last_login_at',
                'users.created_at',
                'users.updated_at',
            );

        if ($status === 'active') {
            $query->where('users.active', true);
        } elseif ($status === 'inactive') {
            $query->where('users.active', false);
        }

        if ($roleFilter !== null) {
            $query->where('roles.id', (int) $roleFilter);
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('users.display_name', 'ILIKE', $like)
                  ->orWhere('users.username', 'ILIKE', $like)
                  ->orWhere('users.email', 'ILIKE', $like);
            });
        }

        $countQuery = clone $query;
        $total = (int) DB::table(DB::raw("({$countQuery->toSql()}) as sub"))
            ->mergeBindings($countQuery)
            ->count();

        $sortColumn = match ($sort) {
            'email'       => 'users.email',
            'username'    => 'users.username',
            'last_login'  => 'users.last_login_at',
            'created'     => 'users.created_at',
            'lastUpdated' => 'users.updated_at',
            default       => 'users.display_name',
        };
        $query->orderBy($sortColumn, $sortDir);

        $rows = $query->offset($offset)->limit($limit)->get();

        $users = $rows->map(fn ($row): array => [
            'id'            => $row->id,
            'username'      => $row->username ?? '',
            'email'         => $row->email ?? '',
            'name'          => $row->display_name ?? '',
            'is_active'     => (bool) $row->active,
            'roles'         => $row->role_names ?? '',
            'slug'          => (string) $row->id,
            'last_login_at' => $row->last_login_at,
            'created_at'    => $row->created_at,
            'updated_at'    => $row->updated_at,
        ])->toArray();

        return ['users' => $users, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    // ════════════════════════════════════════════════════════════════
    // Read
    // ════════════════════════════════════════════════════════════════

    public function getUserDetail(int $userId): ?array
    {
        if ($this->hasLegacyTables()) {
            return $this->getLegacyUserDetail($userId);
        }

        return $this->getModernUserDetail($userId);
    }

    public function getUserBySlug(string $slug): ?array
    {
        $id = $this->resolveSlug($slug);
        if ($id === null) {
            return null;
        }

        return $this->getUserDetail($id);
    }

    /**
     * Legacy CTI user detail (user → actor → object → slug + contact_information + acl_user_group).
     */
    private function getLegacyUserDetail(int $userId): ?array
    {
        $culture = app()->getLocale();

        $user = DB::table('user')
            ->join('actor', 'user.id', '=', 'actor.id')
            ->join('object', 'user.id', '=', 'object.id')
            ->join('slug', 'user.id', '=', 'slug.object_id')
            ->leftJoin('actor_i18n', function ($j) use ($culture): void {
                $j->on('user.id', '=', 'actor_i18n.id')
                  ->where('actor_i18n.culture', '=', $culture);
            })
            ->where('user.id', $userId)
            ->select(
                'user.id',
                'user.username',
                'user.email',
                'user.active',
                'user.password_hash',
                'user.salt',
                'actor.entity_type_id',
                'actor.parent_id',
                'actor.source_culture',
                'actor_i18n.authorized_form_of_name',
                'object.created_at',
                'object.updated_at',
                'object.serial_number',
                'slug.slug',
            )
            ->first();

        if (!$user) {
            return null;
        }

        // Groups
        $groups = DB::table('acl_user_group')
            ->join('acl_group', 'acl_user_group.group_id', '=', 'acl_group.id')
            ->leftJoin('acl_group_i18n', function ($j) use ($culture): void {
                $j->on('acl_group.id', '=', 'acl_group_i18n.id')
                  ->where('acl_group_i18n.culture', '=', $culture);
            })
            ->where('acl_user_group.user_id', $userId)
            ->select('acl_group.id', 'acl_group_i18n.name')
            ->get()
            ->map(fn ($g): array => ['id' => $g->id, 'name' => $g->name ?? ''])
            ->toArray();

        // Contact information
        $contact = DB::table('contact_information')
            ->leftJoin('contact_information_i18n', function ($j) use ($culture): void {
                $j->on('contact_information.id', '=', 'contact_information_i18n.id')
                  ->where('contact_information_i18n.culture', '=', $culture);
            })
            ->where('contact_information.actor_id', $userId)
            ->select(
                'contact_information.id',
                'contact_information.telephone',
                'contact_information.fax',
                'contact_information.street_address',
                'contact_information.postal_code',
                'contact_information.country_code',
                'contact_information.website',
                'contact_information.contact_note',
                'contact_information_i18n.city',
                'contact_information_i18n.region',
                'contact_information_i18n.note',
            )
            ->first();

        // Translate languages
        $translateLanguages = $this->getTranslateLanguages($userId);

        // API keys
        $apiKeys = $this->getApiKeys($userId);

        // Note count
        $noteCount = 0;
        try {
            $noteCount = DB::table('note')->where('user_id', $userId)->count();
        } catch (\Throwable) {
            // note table may not exist
        }

        // Clearance
        $clearance = $this->getSecurityClearance($userId);

        // Recent activity
        $activity = $this->getUserActivity($userId, 20);

        return [
            'id'                       => $user->id,
            'username'                 => $user->username ?? '',
            'email'                    => $user->email ?? '',
            'name'                     => $user->authorized_form_of_name ?? $user->username ?? '',
            'authorized_form_of_name'  => $user->authorized_form_of_name ?? '',
            'is_active'                => (bool) ($user->active ?? true),
            'active'                   => (int) ($user->active ?? 1),
            'password_hash'            => $user->password_hash ?? '',
            'salt'                     => $user->salt ?? '',
            'slug'                     => $user->slug ?? '',
            'source_culture'           => $user->source_culture ?? 'en',
            'serial_number'            => $user->serial_number ?? 0,
            'created_at'               => $user->created_at,
            'updated_at'               => $user->updated_at,
            'roles'                    => $groups,
            'groups'                   => $groups,
            'contact'                  => $contact ? (array) $contact : null,
            'translate_languages'      => $translateLanguages,
            'api_keys'                 => $apiKeys,
            'note_count'               => $noteCount,
            'clearance'                => $clearance,
            'recent_activity'          => $activity,
            'permissions'              => [],
            'clearance_level'          => $clearance['level'] ?? 0,
        ];
    }

    /**
     * Modern users table detail.
     */
    private function getModernUserDetail(int $userId): ?array
    {
        $user = DB::table('users')
            ->where('id', $userId)
            ->select('id', 'username', 'email', 'display_name', 'active', 'last_login_at', 'last_login_ip', 'created_at', 'updated_at')
            ->first();
        if (!$user) {
            return null;
        }

        $roles = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.user_id', $userId)
            ->select('roles.id', 'roles.name', 'roles.description')
            ->get()
            ->map(fn ($r): array => (array) $r)
            ->toArray();

        $permissions = DB::table('role_user')
            ->join('permission_role', 'role_user.role_id', '=', 'permission_role.role_id')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->where('role_user.user_id', $userId)
            ->select('permissions.name', 'permissions.label')
            ->distinct()
            ->get()
            ->map(fn ($r): array => ['name' => $r->name, 'description' => $r->label])
            ->toArray();

        $clearanceLevel = 0;

        $activity = $this->getUserActivity($userId, 20);
        $clearance = $this->getSecurityClearance($userId);

        return [
            'id'                       => $user->id,
            'username'                 => $user->username ?? '',
            'email'                    => $user->email ?? '',
            'name'                     => $user->display_name ?? '',
            'authorized_form_of_name'  => $user->display_name ?? '',
            'is_active'                => (bool) ($user->active ?? true),
            'active'                   => ($user->active ?? true) ? 1 : 0,
            'slug'                     => (string) $user->id,
            'last_login_at'            => $user->last_login_at ?? null,
            'last_login_ip'            => $user->last_login_ip ?? null,
            'created_at'               => $user->created_at,
            'updated_at'               => $user->updated_at,
            'roles'                    => $roles,
            'groups'                   => $roles,
            'contact'                  => null,
            'translate_languages'      => [],
            'api_keys'                 => ['rest' => null, 'oai' => null],
            'note_count'               => 0,
            'clearance'                => $clearance,
            'clearance_level'          => $clearanceLevel,
            'recent_activity'          => $activity,
            'permissions'              => $permissions,
        ];
    }

    // ════════════════════════════════════════════════════════════════
    // Create / Update / Delete
    // ════════════════════════════════════════════════════════════════

    public function createUser(array $data): int
    {
        if ($this->hasLegacyTables()) {
            return $this->createLegacyUser($data);
        }

        return $this->createModernUser($data);
    }

    /**
     * Create user in AtoM CTI schema (object → actor → user + slug + contact + acl_user_group).
     */
    private function createLegacyUser(array $data): int
    {
        $culture = app()->getLocale();

        return DB::transaction(function () use ($data, $culture): int {
            // Object row
            $id = (int) DB::table('object')->insertGetId([
                'class_name' => 'QubitUser',
                'created_at' => now(),
                'updated_at' => now(),
                'serial_number' => 0,
            ]);

            // Slug
            $baseSlug = Str::slug($data['username'] ?? 'user');
            $slug = $baseSlug;
            $counter = 1;
            while (DB::table('slug')->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }
            DB::table('slug')->insert(['object_id' => $id, 'slug' => $slug]);

            // Actor row
            DB::table('actor')->insert([
                'id' => $id,
                'parent_id' => 3, // QubitActor::ROOT_ID
                'source_culture' => $culture,
            ]);

            // Actor i18n
            $displayName = $data['authorized_form_of_name'] ?? $data['name'] ?? '';
            if ($displayName !== '') {
                DB::table('actor_i18n')->insert([
                    'id' => $id,
                    'culture' => $culture,
                    'authorized_form_of_name' => $displayName,
                ]);
            }

            // Password hash (dual-layer: SHA-1 + Argon2/bcrypt)
            $salt = md5((string) rand(100000, 999999) . ($data['email'] ?? ''));
            $sha1Hash = sha1($salt . ($data['password'] ?? ''));
            $hashAlgo = defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : PASSWORD_DEFAULT;
            $passwordHash = password_hash($sha1Hash, $hashAlgo);

            // User row
            DB::table('user')->insert([
                'id' => $id,
                'username' => $data['username'] ?? '',
                'email' => $data['email'] ?? '',
                'password_hash' => $passwordHash,
                'salt' => $salt,
                'active' => isset($data['is_active']) ? (int) $data['is_active'] : (isset($data['active']) ? (int) $data['active'] : 1),
            ]);

            // Always assign Authenticated group (99)
            DB::table('acl_user_group')->insert([
                'user_id' => $id,
                'group_id' => 99,
            ]);

            // Assign selected groups
            $groups = $data['groups'] ?? $data['roles'] ?? [];
            foreach ((array) $groups as $groupId) {
                $groupId = (int) $groupId;
                if ($groupId > 99) {
                    DB::table('acl_user_group')->insert([
                        'user_id' => $id,
                        'group_id' => $groupId,
                    ]);
                }
            }

            // Contact information
            $this->saveLegacyContact($id, $data, $culture);

            // Translate languages
            $this->saveTranslateLanguages($id, $data['translate'] ?? []);

            return $id;
        });
    }

    /**
     * Create user in modern OpenRiC users table.
     */
    private function createModernUser(array $data): int
    {
        return DB::transaction(function () use ($data): int {
            $userId = (int) DB::table('users')->insertGetId([
                'username' => $data['username'],
                'email' => $data['email'],
                'display_name' => $data['name'] ?? $data['authorized_form_of_name'] ?? $data['username'],
                'password' => Hash::make($data['password']),
                'active' => isset($data['is_active']) ? (bool) $data['is_active'] : (isset($data['active']) ? (bool) $data['active'] : true),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $roles = $data['roles'] ?? $data['groups'] ?? [];
            foreach ((array) $roles as $roleId) {
                DB::table('role_user')->insert([
                    'user_id' => $userId,
                    'role_id' => (int) $roleId,
                ]);
            }

            return $userId;
        });
    }

    public function updateUser(int $userId, array $data): void
    {
        if ($this->hasLegacyTables()) {
            $this->updateLegacyUser($userId, $data);
            return;
        }

        $this->updateModernUser($userId, $data);
    }

    /**
     * Update user in AtoM CTI schema.
     */
    private function updateLegacyUser(int $userId, array $data): void
    {
        $culture = app()->getLocale();

        DB::transaction(function () use ($userId, $data, $culture): void {
            $updateFields = [];
            if (isset($data['username'])) {
                $updateFields['username'] = $data['username'];
            }
            if (isset($data['email'])) {
                $updateFields['email'] = $data['email'];
            }
            if (isset($data['active'])) {
                $updateFields['active'] = (int) $data['active'];
            }
            if (isset($data['is_active'])) {
                $updateFields['active'] = (int) $data['is_active'];
            }

            // Password
            if (!empty($data['password'])) {
                $salt = md5((string) rand(100000, 999999) . ($data['email'] ?? ''));
                $sha1Hash = sha1($salt . $data['password']);
                $hashAlgo = defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : PASSWORD_DEFAULT;
                $updateFields['password_hash'] = password_hash($sha1Hash, $hashAlgo);
                $updateFields['salt'] = $salt;
            }

            if (!empty($updateFields)) {
                DB::table('user')->where('id', $userId)->update($updateFields);
            }

            // Actor i18n (display name)
            $displayName = $data['authorized_form_of_name'] ?? $data['name'] ?? null;
            if ($displayName !== null) {
                $exists = DB::table('actor_i18n')
                    ->where('id', $userId)
                    ->where('culture', $culture)
                    ->exists();

                if ($exists) {
                    DB::table('actor_i18n')
                        ->where('id', $userId)
                        ->where('culture', $culture)
                        ->update(['authorized_form_of_name' => $displayName]);
                } else {
                    DB::table('actor_i18n')->insert([
                        'id' => $userId,
                        'culture' => $culture,
                        'authorized_form_of_name' => $displayName,
                    ]);
                }
            }

            // Sync groups
            $groups = $data['groups'] ?? $data['roles'] ?? null;
            if ($groups !== null) {
                DB::table('acl_user_group')
                    ->where('user_id', $userId)
                    ->where('group_id', '>', 99)
                    ->delete();

                foreach ((array) $groups as $groupId) {
                    $groupId = (int) $groupId;
                    if ($groupId > 99) {
                        DB::table('acl_user_group')->insert([
                            'user_id' => $userId,
                            'group_id' => $groupId,
                        ]);
                    }
                }
            }

            // Contact information
            $this->saveLegacyContact($userId, $data, $culture);

            // Translate languages
            if (isset($data['translate'])) {
                $this->saveTranslateLanguages($userId, $data['translate']);
            }

            // API keys
            if (isset($data['restApiKey'])) {
                $this->manageApiKey($userId, 'rest', $data['restApiKey']);
            }
            if (isset($data['oaiApiKey'])) {
                $this->manageApiKey($userId, 'oai', $data['oaiApiKey']);
            }

            // Touch object timestamp
            DB::table('object')->where('id', $userId)->update([
                'updated_at' => now(),
                'serial_number' => DB::raw('serial_number + 1'),
            ]);
        });
    }

    /**
     * Update user in modern OpenRiC users table.
     */
    private function updateModernUser(int $userId, array $data): void
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
                $update['display_name'] = $data['name'];
            }
            if (isset($data['authorized_form_of_name'])) {
                $update['display_name'] = $data['authorized_form_of_name'];
            }
            if (isset($data['is_active'])) {
                $update['active'] = (bool) $data['is_active'];
            }
            if (isset($data['active'])) {
                $update['active'] = (bool) $data['active'];
            }
            if (!empty($data['password'])) {
                $update['password'] = Hash::make($data['password']);
            }

            if (!empty($update)) {
                $update['updated_at'] = now();
                DB::table('users')->where('id', $userId)->update($update);
            }

            $roles = $data['roles'] ?? $data['groups'] ?? null;
            if ($roles !== null) {
                DB::table('role_user')->where('user_id', $userId)->delete();
                foreach ((array) $roles as $roleId) {
                    DB::table('role_user')->insert([
                        'user_id' => $userId,
                        'role_id' => (int) $roleId,
                    ]);
                }
            }
        });
    }

    public function deleteUser(int $userId): void
    {
        if ($this->hasLegacyTables()) {
            $this->deleteLegacyUser($userId);
            return;
        }

        DB::transaction(function () use ($userId): void {
            DB::table('role_user')->where('user_id', $userId)->delete();
            DB::table('users')->where('id', $userId)->delete();
        });
    }

    /**
     * Delete user in AtoM CTI schema — all related records.
     */
    private function deleteLegacyUser(int $userId): void
    {
        DB::transaction(function () use ($userId): void {
            DB::table('acl_user_group')->where('user_id', $userId)->delete();
            DB::table('acl_permission')->where('user_id', $userId)->delete();

            // Delete clipboard items
            try {
                DB::table('clipboard')->where('user_id', $userId)->delete();
            } catch (\Throwable) {
            }

            // Delete properties and i18n (API keys, etc.)
            $propertyIds = DB::table('property')->where('object_id', $userId)->pluck('id')->toArray();
            if (!empty($propertyIds)) {
                DB::table('property_i18n')->whereIn('id', $propertyIds)->delete();
                DB::table('property')->whereIn('id', $propertyIds)->delete();
            }

            // Delete contact information
            $contactIds = DB::table('contact_information')->where('actor_id', $userId)->pluck('id')->toArray();
            if (!empty($contactIds)) {
                DB::table('contact_information_i18n')->whereIn('id', $contactIds)->delete();
                DB::table('contact_information')->whereIn('id', $contactIds)->delete();
            }

            DB::table('user')->where('id', $userId)->delete();
            DB::table('actor_i18n')->where('id', $userId)->delete();
            DB::table('actor')->where('id', $userId)->delete();
            DB::table('slug')->where('object_id', $userId)->delete();
            DB::table('object')->where('id', $userId)->delete();
        });
    }

    public function deactivateUser(int $userId): void
    {
        if ($this->hasLegacyTables()) {
            DB::table('user')->where('id', $userId)->update(['active' => 0]);
            DB::table('object')->where('id', $userId)->update(['updated_at' => now()]);
        } else {
            DB::table('users')->where('id', $userId)->update([
                'active' => false,
                'updated_at' => now(),
            ]);
        }
    }

    public function activateUser(int $userId): void
    {
        if ($this->hasLegacyTables()) {
            DB::table('user')->where('id', $userId)->update(['active' => 1]);
            DB::table('object')->where('id', $userId)->update(['updated_at' => now()]);
        } else {
            DB::table('users')->where('id', $userId)->update([
                'active' => true,
                'updated_at' => now(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════
    // Password
    // ════════════════════════════════════════════════════════════════

    public function resetPassword(int $userId): string
    {
        $tempPassword = Str::random(16);

        if ($this->hasLegacyTables()) {
            $user = DB::table('user')->where('id', $userId)->first();
            $salt = md5((string) rand(100000, 999999) . ($user->email ?? ''));
            $sha1Hash = sha1($salt . $tempPassword);
            $hashAlgo = defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : PASSWORD_DEFAULT;
            DB::table('user')->where('id', $userId)->update([
                'password_hash' => password_hash($sha1Hash, $hashAlgo),
                'salt' => $salt,
            ]);
        } else {
            DB::table('users')->where('id', $userId)->update([
                'password' => Hash::make($tempPassword),
                'updated_at' => now(),
            ]);
        }

        return $tempPassword;
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        if ($this->hasLegacyTables()) {
            $user = DB::table('user')->where('id', $userId)->first();
            if (!$user) {
                return false;
            }

            // Verify current password using dual-layer hash
            $sha1Current = sha1(($user->salt ?? '') . $currentPassword);
            if (!password_verify($sha1Current, $user->password_hash ?? '')) {
                return false;
            }

            $salt = md5((string) rand(100000, 999999) . ($user->email ?? ''));
            $sha1New = sha1($salt . $newPassword);
            $hashAlgo = defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : PASSWORD_DEFAULT;
            DB::table('user')->where('id', $userId)->update([
                'password_hash' => password_hash($sha1New, $hashAlgo),
                'salt' => $salt,
            ]);

            return true;
        }

        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user || !Hash::check($currentPassword, $user->password ?? '')) {
            return false;
        }

        DB::table('users')->where('id', $userId)->update([
            'password' => Hash::make($newPassword),
            'updated_at' => now(),
        ]);

        return true;
    }

    // ════════════════════════════════════════════════════════════════
    // Roles & Groups & Languages
    // ════════════════════════════════════════════════════════════════

    public function getAvailableRoles(): array
    {
        if ($this->hasLegacyTables()) {
            $culture = app()->getLocale();

            return DB::table('acl_group')
                ->leftJoin('acl_group_i18n', function ($j) use ($culture): void {
                    $j->on('acl_group.id', '=', 'acl_group_i18n.id')
                      ->where('acl_group_i18n.culture', '=', $culture);
                })
                ->where('acl_group.id', '>', 99)
                ->select('acl_group.id', 'acl_group_i18n.name', DB::raw("'' as description"), DB::raw('0 as clearance_level'))
                ->orderBy('acl_group.id')
                ->get()
                ->map(fn ($r): array => (array) $r)
                ->toArray();
        }

        return DB::table('roles')
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get()
            ->map(fn ($r): array => (array) $r)
            ->toArray();
    }

    public function getAvailableLanguages(): array
    {
        try {
            $row = DB::table('setting')
                ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')
                ->where('setting.name', 'i18n_languages')
                ->first();

            if ($row && !empty($row->value)) {
                $data = @unserialize($row->value);
                if (is_array($data)) {
                    return $data;
                }
                $data = json_decode($row->value, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        } catch (\Throwable) {
            // setting table may not exist
        }

        return [app()->getLocale()];
    }

    public function getTranslateLanguages(int $userId): array
    {
        try {
            $row = DB::table('acl_permission')
                ->where('user_id', $userId)
                ->where('action', 'translate')
                ->first();

            if (!$row) {
                return [];
            }

            $source = $row->constants ?? $row->conditional ?? '';
            if (empty($source)) {
                return [];
            }

            $data = @unserialize($source);
            if (is_array($data) && isset($data['languages'])) {
                return $data['languages'];
            }
            if (is_array($data)) {
                return $data;
            }

            $data = json_decode($source, true);
            return is_array($data) ? ($data['languages'] ?? $data) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    // ════════════════════════════════════════════════════════════════
    // ACL
    // ════════════════════════════════════════════════════════════════

    public function buildAclData(int $userId, string $className): array
    {
        $culture = app()->getLocale();
        $user = $this->getUserDetail($userId);
        if (!$user) {
            return [];
        }

        // Get user's group IDs
        $userGroupIds = array_map(fn ($g) => (int) $g['id'], $user['groups'] ?? []);
        if (empty($userGroupIds)) {
            $userGroupIds = [99]; // Authenticated group
        }

        $tableCols = count($userGroupIds) + 3;

        // Group names
        $groupNames = [];
        try {
            $groups = DB::table('acl_group_i18n')
                ->whereIn('id', $userGroupIds)
                ->where('culture', $culture)
                ->get();
            foreach ($groups as $g) {
                $groupNames[$g->id] = $g->name;
            }
        } catch (\Throwable) {
        }

        // All user groups includes group IDs plus the username
        $allUserGroups = array_merge($userGroupIds, [$user['username']]);

        // Get permissions - try modern acl_object_permissions table first
        try {
            $permissions = DB::table('acl_object_permissions')
                ->where(function ($q) use ($userId, $userGroupIds): void {
                    $q->where('acl_object_permissions.user_id', $userId)
                      ->orWhereIn('acl_object_permissions.acl_group_id', $userGroupIds);
                })
                ->where(function ($q) use ($className): void {
                    $q->where('acl_object_permissions.entity_type', $className)
                      ->orWhereNull('acl_object_permissions.object_iri');
                })
                ->orderBy('acl_object_permissions.object_iri')
                ->orderBy('acl_object_permissions.user_id')
                ->orderBy('acl_object_permissions.acl_group_id')
                ->select('acl_object_permissions.*')
                ->get();

            // Build ACL matrix
            $acl = [];
            $objectIris = [];
            foreach ($permissions as $perm) {
                $objectIri = $perm->object_iri ?? '';
                $groupKey = $perm->acl_group_id ?? $user['username'];
                $acl[$objectIri][$perm->action ?? ''][$groupKey] = $perm;
                if ($objectIri) {
                    $objectIris[] = $objectIri;
                }
            }

            // Get object names from IRIs
            $objectNames = $this->resolveObjectNamesFromIris(array_unique($objectIris), $culture);

            $aclActions = [
                'create'    => __('Create'),
                'read'      => __('Read'),
                'update'    => __('Update'),
                'delete'    => __('Delete'),
                'publish'   => __('Publish'),
                'translate' => __('Translate'),
            ];

            return [
                'user'        => $user,
                'acl'         => $acl,
                'userGroups'  => $allUserGroups,
                'groupNames'  => $groupNames,
                'objectNames' => $objectNames,
                'tableCols'   => $tableCols,
                'aclActions'  => $aclActions,
            ];
        } catch (\Throwable $e) {
            Log::debug('UserManage: acl_object_permissions query failed: ' . $e->getMessage());
        }

        // Fallback to legacy acl_permission table if it exists
        try {
            if (!\Schema::hasTable('acl_permission')) {
                return [
                    'user'        => $user,
                    'acl'         => [],
                    'userGroups'  => $allUserGroups,
                    'groupNames'  => $groupNames,
                    'objectNames' => [],
                    'tableCols'   => $tableCols,
                    'aclActions'  => [],
                ];
            }
        } catch (\Throwable) {
        }

        $permissions = DB::table('acl_permission')
            ->leftJoin('object', 'acl_permission.object_id', '=', 'object.id')
            ->where(function ($q) use ($userId, $userGroupIds): void {
                $q->where('acl_permission.user_id', $userId)
                  ->orWhereIn('acl_permission.group_id', $userGroupIds);
            })
            ->where(function ($q) use ($className): void {
                $q->where('object.class_name', $className)
                  ->orWhereNull('acl_permission.object_id');
            })
            ->orderBy('acl_permission.object_id')
            ->orderBy('acl_permission.user_id')
            ->orderBy('acl_permission.group_id')
            ->select('acl_permission.*', 'object.class_name')
            ->get();

        // Build ACL matrix
        $acl = [];
        $objectIds = [];
        foreach ($permissions as $perm) {
            $objectId = $perm->object_id ?? '';
            $groupKey = $perm->group_id ?? $user['username'];
            $acl[$objectId][$perm->action ?? ''][$groupKey] = $perm;
            if ($objectId) {
                $objectIds[] = $objectId;
            }
        }

        // Get object names
        $objectNames = $this->resolveObjectNames(array_unique($objectIds), $culture);

        $aclActions = [
            'create'    => __('Create'),
            'read'      => __('Read'),
            'update'    => __('Update'),
            'delete'    => __('Delete'),
            'publish'   => __('Publish'),
            'translate' => __('Translate'),
        ];

        return [
            'user'        => $user,
            'acl'         => $acl,
            'userGroups'  => $allUserGroups,
            'groupNames'  => $groupNames,
            'objectNames' => $objectNames,
            'tableCols'   => $tableCols,
            'aclActions'  => $aclActions,
        ];
    }

    public function buildEditAclData(int $userId, string $className): array
    {
        $culture = app()->getLocale();
        $user = $this->getUserDetail($userId);
        if (!$user) {
            return [];
        }

        $permissions = DB::table('acl_permission')
            ->leftJoin('object', 'acl_permission.object_id', '=', 'object.id')
            ->where('acl_permission.user_id', $userId)
            ->where(function ($q) use ($className): void {
                $q->where('object.class_name', $className)
                  ->orWhereNull('acl_permission.object_id');
            })
            ->select('acl_permission.*')
            ->get();

        // Attach object names
        foreach ($permissions as $perm) {
            $perm->object_name = null;
            if ($perm->object_id) {
                $names = $this->resolveObjectNames([$perm->object_id], $culture);
                $perm->object_name = $names[$perm->object_id] ?? null;
            }
        }

        return ['user' => $user, 'permissions' => $permissions];
    }

    public function saveAclPermissions(int $userId, array $permissionUpdates, array $newPermission = []): void
    {
        // Update existing permissions
        foreach ($permissionUpdates as $id => $value) {
            if ($value === 'inherit') {
                DB::table('acl_permission')->where('id', (int) $id)->delete();
            } else {
                DB::table('acl_permission')
                    ->where('id', (int) $id)
                    ->update(['grant_deny' => ($value === 'grant') ? 1 : 0]);
            }
        }

        // Add new permission
        $objectId = $newPermission['actor_id'] ?? $newPermission['object_id'] ?? $newPermission['repository_id'] ?? $newPermission['taxonomy_id'] ?? null;
        $action = $newPermission['action'] ?? '';
        $grantDeny = ($newPermission['grant_deny'] ?? 'grant') === 'grant' ? 1 : 0;

        if ($objectId || $action) {
            DB::table('acl_permission')->insert([
                'user_id'    => $userId,
                'object_id'  => $objectId ?: null,
                'action'     => $action ?: null,
                'grant_deny' => $grantDeny,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════
    // Registration
    // ════════════════════════════════════════════════════════════════

    public function getRegistrationRequests(?string $statusFilter = null): array
    {
        $rows = collect();
        $groups = collect();

        $validStatuses = ['pending', 'verified', 'approved', 'rejected', 'expired'];
        if ($statusFilter !== null && !in_array($statusFilter, $validStatuses, true)) {
            $statusFilter = null;
        }

        try {
            $query = DB::table('user_registration_request');
            if ($statusFilter !== null) {
                $query->where('status', $statusFilter);
            }
            $rows = $query->orderByDesc('created_at')->get();
        } catch (\Throwable) {
            // Table may not exist
        }

        try {
            $culture = app()->getLocale();
            $groups = DB::table('acl_group')
                ->leftJoin('acl_group_i18n', function ($j) use ($culture): void {
                    $j->on('acl_group.id', '=', 'acl_group_i18n.id')
                      ->where('acl_group_i18n.culture', '=', $culture);
                })
                ->where('acl_group.id', '>', 99)
                ->select('acl_group.id', 'acl_group_i18n.name')
                ->orderBy('acl_group_i18n.name')
                ->get();
        } catch (\Throwable) {
        }

        return ['rows' => $rows, 'groups' => $groups, 'statusFilter' => $statusFilter];
    }

    public function approveRegistration(int $requestId, ?int $groupId, string $notes, int $adminId): array
    {
        try {
            $regRequest = DB::table('user_registration_request')->where('id', $requestId)->first();
            if (!$regRequest) {
                return ['success' => false, 'error' => 'Registration request not found.'];
            }
            if (!in_array($regRequest->status, ['pending', 'verified'], true)) {
                return ['success' => false, 'error' => 'Request is not in a valid state for approval.'];
            }

            DB::beginTransaction();

            // Create user via legacy path
            $userId = $this->createUser([
                'username' => $regRequest->username,
                'email' => $regRequest->email,
                'password' => 'changeme', // Will be overridden if password_hash available
                'name' => $regRequest->full_name ?? $regRequest->username,
                'authorized_form_of_name' => $regRequest->full_name ?? $regRequest->username,
                'is_active' => 1,
                'groups' => $groupId ? [$groupId] : [],
            ]);

            // If the request has a password_hash, use it directly
            if (!empty($regRequest->password_hash)) {
                DB::table('user')->where('id', $userId)->update([
                    'password_hash' => $regRequest->password_hash,
                ]);
            }

            DB::table('user_registration_request')
                ->where('id', $requestId)
                ->update([
                    'status' => 'approved',
                    'admin_notes' => $notes,
                    'reviewed_by' => $adminId,
                    'reviewed_at' => now(),
                    'user_id' => $userId,
                ]);

            DB::commit();

            return ['success' => true, 'message' => 'Registration approved. User account created.'];
        } catch (\Throwable $e) {
            DB::rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function rejectRegistration(int $requestId, string $notes, int $adminId): array
    {
        try {
            $regRequest = DB::table('user_registration_request')->where('id', $requestId)->first();
            if (!$regRequest) {
                return ['success' => false, 'error' => 'Registration request not found.'];
            }

            DB::table('user_registration_request')
                ->where('id', $requestId)
                ->update([
                    'status' => 'rejected',
                    'admin_notes' => $notes,
                    'reviewed_by' => $adminId,
                    'reviewed_at' => now(),
                ]);

            return ['success' => true, 'message' => 'Registration rejected.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ════════════════════════════════════════════════════════════════
    // API Keys
    // ════════════════════════════════════════════════════════════════

    public function getApiKeys(int $userId): array
    {
        $rest = null;
        $oai = null;

        try {
            $rest = DB::table('property')
                ->join('property_i18n', 'property.id', '=', 'property_i18n.id')
                ->where('property.object_id', $userId)
                ->where('property.name', 'restApiKey')
                ->value('property_i18n.value');

            $oai = DB::table('property')
                ->join('property_i18n', 'property.id', '=', 'property_i18n.id')
                ->where('property.object_id', $userId)
                ->where('property.name', 'oaiApiKey')
                ->value('property_i18n.value');
        } catch (\Throwable) {
            // property table may not exist
        }

        return ['rest' => $rest, 'oai' => $oai];
    }

    public function manageApiKey(int $userId, string $keyType, string $action): ?string
    {
        $propName = $keyType === 'oai' ? 'oaiApiKey' : 'restApiKey';

        if ($action === '' || $action === null) {
            return null;
        }

        try {
            // Find existing property
            $existing = DB::table('property')
                ->where('object_id', $userId)
                ->where('name', $propName)
                ->first();

            if ($action === 'delete') {
                if ($existing) {
                    DB::table('property_i18n')->where('id', $existing->id)->delete();
                    DB::table('property')->where('id', $existing->id)->delete();
                }
                return null;
            }

            if ($action === 'generate') {
                $newKey = Str::random(64);

                if ($existing) {
                    DB::table('property_i18n')
                        ->where('id', $existing->id)
                        ->update(['value' => $newKey]);
                } else {
                    $propId = (int) DB::table('property')->insertGetId([
                        'object_id' => $userId,
                        'name' => $propName,
                        'source_culture' => app()->getLocale(),
                    ]);
                    DB::table('property_i18n')->insert([
                        'id' => $propId,
                        'culture' => app()->getLocale(),
                        'value' => $newKey,
                    ]);
                }

                return $newKey;
            }
        } catch (\Throwable $e) {
            Log::warning('UserManage: API key management failed: ' . $e->getMessage());
        }

        return null;
    }

    // ════════════════════════════════════════════════════════════════
    // Clipboard
    // ════════════════════════════════════════════════════════════════

    public function getClipboardItems(int $userId): array
    {
        try {
            return DB::table('clipboard')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($r): array => (array) $r)
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    public function removeClipboardItem(int $userId, int $itemId): bool
    {
        try {
            return DB::table('clipboard')
                ->where('user_id', $userId)
                ->where('id', $itemId)
                ->delete() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public function clearClipboard(int $userId): int
    {
        try {
            return DB::table('clipboard')
                ->where('user_id', $userId)
                ->delete();
        } catch (\Throwable) {
            return 0;
        }
    }

    // ════════════════════════════════════════════════════════════════
    // Activity & Stats
    // ════════════════════════════════════════════════════════════════

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
            Log::debug('UserManage: audit_log query failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserStats(): array
    {
        if ($this->hasLegacyTables()) {
            $total = DB::table('user')->count();
            $active = DB::table('user')->where('active', 1)->count();
            $inactive = DB::table('user')->where('active', 0)->count();

            $culture = app()->getLocale();
            $byRole = DB::table('acl_user_group')
                ->join('acl_group_i18n', function ($j) use ($culture): void {
                    $j->on('acl_user_group.group_id', '=', 'acl_group_i18n.id')
                      ->where('acl_group_i18n.culture', '=', $culture);
                })
                ->select('acl_group_i18n.name', DB::raw('COUNT(DISTINCT acl_user_group.user_id) as count'))
                ->groupBy('acl_group_i18n.name')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($r): array => (array) $r)
                ->toArray();

            return [
                'total'             => $total,
                'active'            => $active,
                'inactive'          => $inactive,
                'by_role'           => $byRole,
                'recent_logins_7d'  => 0,
            ];
        }

        $total = DB::table('users')->count();
        $active = DB::table('users')->where('active', true)->count();
        $inactive = DB::table('users')->where('active', false)->count();

        $byRole = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('COUNT(DISTINCT role_user.user_id) as count'))
            ->groupBy('roles.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r): array => (array) $r)
            ->toArray();

        $recentLogins = DB::table('users')
            ->whereNotNull('last_login_at')
            ->where('last_login_at', '>=', now()->subDays(7))
            ->count();

        return [
            'total'             => $total,
            'active'            => $active,
            'inactive'          => $inactive,
            'by_role'           => $byRole,
            'recent_logins_7d'  => $recentLogins,
        ];
    }

    // ════════════════════════════════════════════════════════════════
    // Bulk Actions
    // ════════════════════════════════════════════════════════════════

    public function bulkAction(array $userIds, string $action, array $params = []): array
    {
        if (empty($userIds)) {
            return ['affected' => 0, 'message' => 'No users selected.'];
        }

        $affected = 0;
        $isLegacy = $this->hasLegacyTables();

        DB::transaction(function () use ($userIds, $action, $params, &$affected, $isLegacy): void {
            $table = $isLegacy ? 'user' : 'users';
            $activeCol = $isLegacy ? 'active' : 'active';
            $trueVal = $isLegacy ? 1 : true;
            $falseVal = $isLegacy ? 0 : false;

            switch ($action) {
                case 'activate':
                    $update = [$activeCol => $trueVal];
                    if (!$isLegacy) {
                        $update['updated_at'] = now();
                    }
                    $affected = DB::table($table)->whereIn('id', $userIds)->update($update);
                    break;

                case 'deactivate':
                    $update = [$activeCol => $falseVal];
                    if (!$isLegacy) {
                        $update['updated_at'] = now();
                    }
                    $affected = DB::table($table)->whereIn('id', $userIds)->update($update);
                    break;

                case 'delete':
                    foreach ($userIds as $uid) {
                        $this->deleteUser((int) $uid);
                        $affected++;
                    }
                    break;

                case 'assign_role':
                    $roleId = (int) ($params['role_id'] ?? 0);
                    if ($roleId > 0) {
                        $joinTable = $isLegacy ? 'acl_user_group' : 'role_user';
                        $roleCol = $isLegacy ? 'group_id' : 'role_id';
                        foreach ($userIds as $uid) {
                            $exists = DB::table($joinTable)
                                ->where('user_id', (int) $uid)
                                ->where($roleCol, $roleId)
                                ->exists();
                            if (!$exists) {
                                DB::table($joinTable)->insert([
                                    'user_id' => (int) $uid,
                                    $roleCol => $roleId,
                                ]);
                            }
                        }
                        $affected = count($userIds);
                    }
                    break;

                case 'remove_role':
                    $roleId = (int) ($params['role_id'] ?? 0);
                    if ($roleId > 0) {
                        $joinTable = $isLegacy ? 'acl_user_group' : 'role_user';
                        $roleCol = $isLegacy ? 'group_id' : 'role_id';
                        $affected = DB::table($joinTable)
                            ->whereIn('user_id', $userIds)
                            ->where($roleCol, $roleId)
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

    // ════════════════════════════════════════════════════════════════
    // Security Clearance
    // ════════════════════════════════════════════════════════════════

    public function getSecurityClearance(int $userId): ?array
    {
        try {
            $row = DB::table('security_clearance')
                ->leftJoin('security_classification', 'security_clearance.classification_id', '=', 'security_classification.id')
                ->where('security_clearance.user_id', $userId)
                ->select(
                    'security_clearance.*',
                    'security_classification.name as classification_name',
                    'security_classification.level',
                    'security_classification.color as classification_color',
                )
                ->first();

            if (!$row) {
                return null;
            }

            return (array) $row;
        } catch (\Throwable) {
            return null;
        }
    }

    public function grantSecurityClearance(int $userId, int $classificationId, ?string $expiresAt, string $notes): void
    {
        try {
            $existing = DB::table('security_clearance')->where('user_id', $userId)->first();

            $data = [
                'user_id'           => $userId,
                'classification_id' => $classificationId,
                'granted_at'        => now(),
                'expires_at'        => $expiresAt,
                'notes'             => $notes,
                'granted_by'        => auth()->id(),
            ];

            if ($existing) {
                DB::table('security_clearance')->where('user_id', $userId)->update($data);
            } else {
                DB::table('security_clearance')->insert($data);
            }
        } catch (\Throwable $e) {
            Log::warning('UserManage: security clearance grant failed: ' . $e->getMessage());
        }
    }

    public function revokeSecurityClearance(int $userId): void
    {
        try {
            DB::table('security_clearance')->where('user_id', $userId)->delete();
        } catch (\Throwable $e) {
            Log::warning('UserManage: security clearance revoke failed: ' . $e->getMessage());
        }
    }

    public function getSecurityClassifications(): array
    {
        try {
            return DB::table('security_classification')
                ->where('active', 1)
                ->orderBy('level')
                ->get()
                ->map(fn ($r): array => (array) $r)
                ->toArray();
        } catch (\Throwable) {
            return [
                ['id' => 1, 'name' => 'Public', 'level' => 0, 'color' => 'success'],
                ['id' => 2, 'name' => 'Restricted', 'level' => 1, 'color' => 'info'],
                ['id' => 3, 'name' => 'Confidential', 'level' => 2, 'color' => 'warning'],
                ['id' => 4, 'name' => 'Secret', 'level' => 3, 'color' => 'danger'],
                ['id' => 5, 'name' => 'Top Secret', 'level' => 4, 'color' => 'dark'],
            ];
        }
    }

    // ════════════════════════════════════════════════════════════════
    // Slug Helpers
    // ════════════════════════════════════════════════════════════════

    public function getSlug(int $userId): ?string
    {
        if ($this->hasLegacyTables()) {
            return DB::table('slug')->where('object_id', $userId)->value('slug');
        }
        return (string) $userId;
    }

    public function resolveSlug(string $slug): ?int
    {
        if ($this->hasLegacyTables()) {
            $row = DB::table('slug')
                ->join('object', 'slug.object_id', '=', 'object.id')
                ->where('slug.slug', $slug)
                ->where('object.class_name', 'QubitUser')
                ->select('slug.object_id')
                ->first();

            return $row ? (int) $row->object_id : null;
        }

        // Modern — slug is the user ID
        if (is_numeric($slug)) {
            $exists = DB::table('users')->where('id', (int) $slug)->exists();
            return $exists ? (int) $slug : null;
        }

        return null;
    }

    // ════════════════════════════════════════════════════════════════
    // Private Helpers
    // ════════════════════════════════════════════════════════════════

    /**
     * Check if the legacy AtoM CTI tables exist.
     */
    private function hasLegacyTables(): bool
    {
        static $checked = null;
        if ($checked !== null) {
            return $checked;
        }

        try {
            $checked = \Schema::hasTable('user') && \Schema::hasTable('actor') && \Schema::hasTable('object') && \Schema::hasTable('slug');
        } catch (\Throwable) {
            $checked = false;
        }

        return $checked;
    }

    /**
     * Save contact information for a user (legacy CTI schema).
     */
    private function saveLegacyContact(int $userId, array $data, string $culture): void
    {
        $contactFields = [
            'contact_telephone'      => 'telephone',
            'contact_fax'            => 'fax',
            'contact_street_address' => 'street_address',
            'contact_postal_code'    => 'postal_code',
            'contact_country_code'   => 'country_code',
            'contact_website'        => 'website',
            'contact_note'           => 'contact_note',
        ];
        $i18nFields = [
            'contact_city'   => 'city',
            'contact_region' => 'region',
        ];

        $hasContact = false;
        foreach (array_keys(array_merge($contactFields, $i18nFields)) as $formField) {
            if (!empty($data[$formField])) {
                $hasContact = true;
                break;
            }
        }
        if (!$hasContact) {
            return;
        }

        $existing = DB::table('contact_information')->where('actor_id', $userId)->first();

        $baseData = [];
        foreach ($contactFields as $formField => $dbField) {
            $baseData[$dbField] = $data[$formField] ?? null;
        }

        $i18nData = [];
        foreach ($i18nFields as $formField => $dbField) {
            $i18nData[$dbField] = $data[$formField] ?? null;
        }

        if ($existing) {
            $baseData['updated_at'] = now();
            DB::table('contact_information')->where('id', $existing->id)->update($baseData);
            DB::table('contact_information_i18n')->updateOrInsert(
                ['id' => $existing->id, 'culture' => $culture],
                $i18nData,
            );
        } else {
            $baseData['actor_id'] = $userId;
            $baseData['source_culture'] = $culture;
            $baseData['created_at'] = now();
            $baseData['updated_at'] = now();
            $contactId = (int) DB::table('contact_information')->insertGetId($baseData);
            DB::table('contact_information_i18n')->insert(array_merge(
                ['id' => $contactId, 'culture' => $culture],
                $i18nData,
            ));
        }
    }

    /**
     * Save translate language permissions for a user.
     */
    private function saveTranslateLanguages(int $userId, array $languages): void
    {
        try {
            DB::table('acl_permission')
                ->where('user_id', $userId)
                ->where('action', 'translate')
                ->delete();

            if (!empty($languages)) {
                DB::table('acl_permission')->insert([
                    'user_id'       => $userId,
                    'group_id'      => null,
                    'object_id'     => null,
                    'action'        => 'translate',
                    'grant_deny'    => 1,
                    'conditional'   => 'in_array(%p[language], %k[languages])',
                    'constants'     => serialize(['languages' => $languages]),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                    'serial_number' => 0,
                ]);
            }
        } catch (\Throwable) {
            // acl_permission table may not exist
        }
    }

    /**
     * Resolve object IDs to display names across actor, information_object, repository, term i18n tables.
     */
    private function resolveObjectNames(array $objectIds, string $culture): array
    {
        if (empty($objectIds)) {
            return [];
        }

        $names = [];

        try {
            $rows = DB::table('actor_i18n')
                ->whereIn('id', $objectIds)
                ->where('culture', $culture)
                ->pluck('authorized_form_of_name', 'id')
                ->toArray();
            $names = array_merge($names, $rows);
        } catch (\Throwable) {
        }

        try {
            $rows = DB::table('information_object_i18n')
                ->whereIn('id', $objectIds)
                ->where('culture', $culture)
                ->pluck('title', 'id')
                ->toArray();
            $names = array_merge($names, $rows);
        } catch (\Throwable) {
        }

        try {
            $rows = DB::table('repository_i18n')
                ->whereIn('id', $objectIds)
                ->where('culture', $culture)
                ->pluck('authorized_form_of_name', 'id')
                ->toArray();
            $names = array_merge($names, $rows);
        } catch (\Throwable) {
        }

        try {
            $rows = DB::table('term_i18n')
                ->whereIn('id', $objectIds)
                ->where('culture', $culture)
                ->pluck('name', 'id')
                ->toArray();
            $names = array_merge($names, $rows);
        } catch (\Throwable) {
        }

        return $names;
    }

    /**
     * Get actors for ACL dropdown (limited to 500).
     */
    public function getActorsForAcl(): array
    {
        $culture = app()->getLocale();

        try {
            return DB::table('actor')
                ->join('actor_i18n', function ($j) use ($culture): void {
                    $j->on('actor.id', '=', 'actor_i18n.id')
                      ->where('actor_i18n.culture', '=', $culture);
                })
                ->whereNotNull('actor_i18n.authorized_form_of_name')
                ->orderBy('actor_i18n.authorized_form_of_name')
                ->select('actor.id', 'actor_i18n.authorized_form_of_name')
                ->limit(500)
                ->get()
                ->map(fn ($r): array => (array) $r)
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get repositories for ACL dropdown.
     */
    public function getRepositoriesForAcl(): array
    {
        $culture = app()->getLocale();

        try {
            return DB::table('repository')
                ->join('actor_i18n', function ($j) use ($culture): void {
                    $j->on('repository.id', '=', 'actor_i18n.id')
                      ->where('actor_i18n.culture', '=', $culture);
                })
                ->whereNotNull('actor_i18n.authorized_form_of_name')
                ->orderBy('actor_i18n.authorized_form_of_name')
                ->select('repository.id', 'actor_i18n.authorized_form_of_name')
                ->limit(500)
                ->get()
                ->map(fn ($r): array => (array) $r)
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Resolve object IRIs to display names for modern acl_object_permissions schema.
     */
    private function resolveObjectNamesFromIris(array $iris, string $culture): array
    {
        if (empty($iris)) {
            return [];
        }

        $names = [];

        // Parse IRIs to extract IDs - format could be 'actor:123' or '/actors/123'
        foreach ($iris as $iri) {
            if (preg_match('/actor[s]?[:\/](\d+)/i', $iri, $matches)) {
                $actorId = (int) $matches[1];
                try {
                    $name = DB::table('actor_i18n')
                        ->where('id', $actorId)
                        ->where('culture', $culture)
                        ->value('authorized_form_of_name');
                    if ($name) {
                        $names[$iri] = $name;
                    }
                } catch (\Throwable) {
                }
            } elseif (preg_match('/information_object[s]?[:\/](\d+)/i', $iri, $matches)) {
                $ioId = (int) $matches[1];
                try {
                    $title = DB::table('information_object_i18n')
                        ->where('id', $ioId)
                        ->where('culture', $culture)
                        ->value('title');
                    if ($title) {
                        $names[$iri] = $title;
                    }
                } catch (\Throwable) {
                }
            } elseif (preg_match('/repository[:\/](\d+)/i', $iri, $matches)) {
                $repoId = (int) $matches[1];
                try {
                    $name = DB::table('repository_i18n')
                        ->where('id', $repoId)
                        ->where('culture', $culture)
                        ->value('authorized_form_of_name');
                    if ($name) {
                        $names[$iri] = $name;
                    }
                } catch (\Throwable) {
                }
            } elseif (preg_match('/taxonomy[:\/](\d+)/i', $iri, $matches)) {
                $taxId = (int) $matches[1];
                try {
                    $name = DB::table('term_i18n')
                        ->where('id', $taxId)
                        ->where('culture', $culture)
                        ->value('name');
                    if ($name) {
                        $names[$iri] = $name;
                    }
                } catch (\Throwable) {
                }
            }
        }

        return $names;
    }

    /**
     * Get taxonomies for ACL dropdown.
     */
    public function getTaxonomiesForAcl(): array
    {
        $culture = app()->getLocale();

        try {
            return DB::table('taxonomy')
                ->join('taxonomy_i18n', function ($j) use ($culture): void {
                    $j->on('taxonomy.id', '=', 'taxonomy_i18n.id')
                      ->where('taxonomy_i18n.culture', '=', $culture);
                })
                ->whereNotNull('taxonomy_i18n.name')
                ->orderBy('taxonomy_i18n.name')
                ->select('taxonomy.id', 'taxonomy_i18n.name')
                ->limit(500)
                ->get()
                ->map(fn ($r): array => (array) $r)
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }
}
