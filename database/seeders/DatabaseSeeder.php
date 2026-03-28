<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OpenRiC\Auth\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $roles = [
            ['name' => Role::ADMINISTRATOR, 'label' => 'Administrator', 'level' => Role::ADMINISTRATOR_LEVEL, 'description' => 'Full system access'],
            ['name' => Role::EDITOR, 'label' => 'Editor', 'level' => Role::EDITOR_LEVEL, 'description' => 'Can create, read, update, delete, and publish'],
            ['name' => Role::CONTRIBUTOR, 'label' => 'Contributor', 'level' => Role::CONTRIBUTOR_LEVEL, 'description' => 'Can create, read, and update'],
            ['name' => Role::TRANSLATOR, 'label' => 'Translator', 'level' => Role::TRANSLATOR_LEVEL, 'description' => 'Can read and update translations'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(['name' => $role['name']], array_merge($role, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Permissions
        $entities = ['record', 'record_set', 'record_part', 'agent', 'person', 'corporate_body', 'family', 'activity', 'place', 'date', 'mandate', 'function', 'instantiation'];
        $actions = ['create', 'read', 'update', 'delete', 'publish'];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => "{$entity}.{$action}"],
                    [
                        'label' => ucfirst($action) . ' ' . str_replace('_', ' ', ucfirst($entity)),
                        'group' => $entity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // Admin permissions
        foreach (['admin.users', 'admin.roles', 'admin.settings', 'admin.audit', 'admin.clearance', 'admin.triplestore'] as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm],
                ['label' => 'Admin: ' . str_replace('admin.', '', $perm), 'group' => 'admin', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Security classifications
        $classifications = [
            ['code' => 'UNCLASSIFIED', 'name' => 'Unclassified', 'level' => 0, 'color' => '#28a745'],
            ['code' => 'RESTRICTED', 'name' => 'Restricted', 'level' => 1, 'color' => '#ffc107'],
            ['code' => 'CONFIDENTIAL', 'name' => 'Confidential', 'level' => 2, 'color' => '#fd7e14'],
            ['code' => 'SECRET', 'name' => 'Secret', 'level' => 3, 'color' => '#dc3545', 'requires_2fa' => true],
            ['code' => 'TOP_SECRET', 'name' => 'Top Secret', 'level' => 4, 'color' => '#6f42c1', 'requires_2fa' => true, 'watermark_required' => true],
        ];

        foreach ($classifications as $class) {
            DB::table('security_classifications')->updateOrInsert(
                ['code' => $class['code']],
                array_merge([
                    'requires_2fa' => false,
                    'watermark_required' => false,
                    'download_allowed' => true,
                    'print_allowed' => true,
                    'copy_allowed' => true,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $class)
            );
        }

        // Admin user
        $adminId = DB::table('users')->updateOrInsert(
            ['email' => 'admin@openric.org'],
            [
                'uuid' => Str::uuid()->toString(),
                'username' => 'admin',
                'email' => 'admin@openric.org',
                'password' => Hash::make('admin'),
                'display_name' => 'OpenRiC Administrator',
                'active' => true,
                'locale' => 'en',
                'timezone' => 'Africa/Johannesburg',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Assign admin role
        $adminUser = DB::table('users')->where('email', 'admin@openric.org')->first();
        $adminRole = DB::table('roles')->where('name', Role::ADMINISTRATOR)->first();

        if ($adminUser && $adminRole) {
            DB::table('role_user')->updateOrInsert(
                ['user_id' => $adminUser->id, 'role_id' => $adminRole->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
