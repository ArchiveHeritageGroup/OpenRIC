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

        // ACL groups — from Heratio ahg-acl pattern
        $aclGroups = [
            ['name' => 'Administrators', 'description' => 'Full system access — all objects, all actions'],
            ['name' => 'Editors', 'description' => 'Can create, edit, delete, and publish records'],
            ['name' => 'Contributors', 'description' => 'Can create and edit records, cannot publish or delete'],
            ['name' => 'Researchers', 'description' => 'Read-only access to published records'],
            ['name' => 'Translators', 'description' => 'Can view and update translation fields only'],
        ];

        foreach ($aclGroups as $group) {
            DB::table('acl_groups')->updateOrInsert(
                ['name' => $group['name']],
                array_merge($group, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Assign admin user to Administrators ACL group
        $adminAclGroup = DB::table('acl_groups')->where('name', 'Administrators')->first();
        if ($adminUser && $adminAclGroup) {
            DB::table('acl_user_group')->updateOrInsert(
                ['user_id' => $adminUser->id, 'acl_group_id' => $adminAclGroup->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Security compartments — from Heratio ahg-security-clearance pattern
        $compartments = [
            ['code' => 'PERSONNEL', 'name' => 'Personnel Records', 'description' => 'Human resources and staff records — restricted to HR and admin'],
            ['code' => 'LEGAL', 'name' => 'Legal & Litigation', 'description' => 'Legal proceedings, litigation holds, attorney-client privilege'],
            ['code' => 'FINANCIAL', 'name' => 'Financial Records', 'description' => 'Financial statements, budgets, procurement — restricted to finance team'],
            ['code' => 'MEDICAL', 'name' => 'Medical Records', 'description' => 'Health-related records — POPIA/GDPR special category data'],
            ['code' => 'INTELLIGENCE', 'name' => 'Intelligence & Security', 'description' => 'National security and intelligence records — highest restriction'],
        ];

        foreach ($compartments as $comp) {
            DB::table('security_compartments')->updateOrInsert(
                ['code' => $comp['code']],
                array_merge($comp, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Default settings — from Heratio ahg-core AhgSettingsService pattern
        $settings = [
            // General
            ['group' => 'general', 'key' => 'site_title', 'value' => 'OpenRiC', 'type' => 'string', 'description' => 'Site title displayed in header and browser tab'],
            ['group' => 'general', 'key' => 'site_description', 'value' => 'Records in Contexts — Archival Description Platform', 'type' => 'text', 'description' => 'Site description for SEO and metadata'],
            ['group' => 'general', 'key' => 'default_language', 'value' => 'en', 'type' => 'string', 'description' => 'Default interface language'],
            ['group' => 'general', 'key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string', 'description' => 'PHP date format string'],
            ['group' => 'general', 'key' => 'results_per_page', 'value' => '25', 'type' => 'integer', 'description' => 'Default number of results per browse page'],
            ['group' => 'general', 'key' => 'repository_code', 'value' => '', 'type' => 'string', 'description' => 'ISIL or repository identifier code'],
            ['group' => 'general', 'key' => 'repository_name', 'value' => '', 'type' => 'string', 'description' => 'Full name of the archival repository'],
            ['group' => 'general', 'key' => 'repository_country', 'value' => 'ZA', 'type' => 'string', 'description' => 'ISO 3166-1 alpha-2 country code'],
            // Fuseki
            ['group' => 'fuseki', 'key' => 'endpoint', 'value' => 'http://127.0.0.1:3030/openric', 'type' => 'url', 'description' => 'Fuseki SPARQL endpoint URL'],
            ['group' => 'fuseki', 'key' => 'username', 'value' => 'admin', 'type' => 'string', 'description' => 'Fuseki authentication username', 'is_sensitive' => true],
            ['group' => 'fuseki', 'key' => 'password', 'value' => 'admin123', 'type' => 'string', 'description' => 'Fuseki authentication password', 'is_sensitive' => true],
            ['group' => 'fuseki', 'key' => 'timeout', 'value' => '15', 'type' => 'integer', 'description' => 'SPARQL query timeout in seconds'],
            ['group' => 'fuseki', 'key' => 'enabled', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable Fuseki integration'],
            ['group' => 'fuseki', 'key' => 'base_uri', 'value' => 'https://ric.theahg.co.za/ric/', 'type' => 'url', 'description' => 'Base URI for RiC-O IRIs'],
            // Elasticsearch
            ['group' => 'elasticsearch', 'key' => 'host', 'value' => 'localhost', 'type' => 'string', 'description' => 'Elasticsearch host'],
            ['group' => 'elasticsearch', 'key' => 'port', 'value' => '9200', 'type' => 'integer', 'description' => 'Elasticsearch port'],
            ['group' => 'elasticsearch', 'key' => 'enabled', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable Elasticsearch full-text search'],
            ['group' => 'elasticsearch', 'key' => 'index_prefix', 'value' => 'openric_', 'type' => 'string', 'description' => 'Index name prefix'],
            // Theme
            ['group' => 'theme', 'key' => 'primary_color', 'value' => '#1a5276', 'type' => 'string', 'description' => 'Primary brand color (hex)'],
            ['group' => 'theme', 'key' => 'sidebar_collapsed', 'value' => '0', 'type' => 'boolean', 'description' => 'Default sidebar state'],
            ['group' => 'theme', 'key' => 'display_mode', 'value' => 'list', 'type' => 'string', 'description' => 'Default browse display: list, gallery, grid, tree, timeline'],
            ['group' => 'theme', 'key' => 'logo_path', 'value' => '', 'type' => 'string', 'description' => 'Custom logo file path (relative to public/)'],
            ['group' => 'theme', 'key' => 'footer_text', 'value' => '© OpenRiC — Records in Contexts', 'type' => 'text', 'description' => 'Footer text HTML'],
            // Security
            ['group' => 'security', 'key' => 'require_2fa_level', 'value' => '3', 'type' => 'integer', 'description' => 'Minimum classification level requiring 2FA'],
            ['group' => 'security', 'key' => 'session_timeout_minutes', 'value' => '120', 'type' => 'integer', 'description' => 'Session timeout in minutes'],
            ['group' => 'security', 'key' => 'max_failed_logins', 'value' => '5', 'type' => 'integer', 'description' => 'Max failed login attempts before lockout'],
            ['group' => 'security', 'key' => 'lockout_duration_minutes', 'value' => '30', 'type' => 'integer', 'description' => 'Account lockout duration after max failed logins'],
            ['group' => 'security', 'key' => 'password_min_length', 'value' => '12', 'type' => 'integer', 'description' => 'Minimum password length'],
            ['group' => 'security', 'key' => 'ip_anonymization', 'value' => '0', 'type' => 'boolean', 'description' => 'GDPR: anonymize IP addresses in audit log'],
            // OAI-PMH
            ['group' => 'oai', 'key' => 'repository_name', 'value' => 'OpenRiC OAI-PMH Repository', 'type' => 'string', 'description' => 'OAI-PMH repository name'],
            ['group' => 'oai', 'key' => 'admin_email', 'value' => 'admin@openric.org', 'type' => 'email', 'description' => 'OAI-PMH admin email'],
            ['group' => 'oai', 'key' => 'enabled', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable OAI-PMH endpoint'],
            ['group' => 'oai', 'key' => 'results_per_page', 'value' => '100', 'type' => 'integer', 'description' => 'OAI-PMH results per ListRecords response'],
            // Qdrant / AI
            ['group' => 'ai', 'key' => 'qdrant_host', 'value' => 'localhost', 'type' => 'string', 'description' => 'Qdrant vector database host'],
            ['group' => 'ai', 'key' => 'qdrant_port', 'value' => '6333', 'type' => 'integer', 'description' => 'Qdrant port'],
            ['group' => 'ai', 'key' => 'ollama_endpoint', 'value' => 'http://localhost:11434', 'type' => 'url', 'description' => 'Ollama API endpoint for embeddings'],
            ['group' => 'ai', 'key' => 'embedding_model', 'value' => 'nomic-embed-text', 'type' => 'string', 'description' => 'Embedding model name'],
            ['group' => 'ai', 'key' => 'enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable AI/semantic search features'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['group' => $setting['group'], 'key' => $setting['key']],
                array_merge([
                    'type' => 'string',
                    'description' => null,
                    'is_sensitive' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $setting)
            );
        }

        // Default workflow — from Heratio ahg-workflow pattern
        if ($adminUser) {
            $workflowId = DB::table('workflows')->updateOrInsert(
                ['name' => 'Standard Publication Workflow'],
                [
                    'description' => 'Default editorial workflow: Draft → Review → Approve → Publish',
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $workflow = DB::table('workflows')->where('name', 'Standard Publication Workflow')->first();
            if ($workflow && DB::table('workflow_steps')->where('workflow_id', $workflow->id)->count() === 0) {
                $steps = [
                    ['name' => 'Draft', 'step_type' => 'initial', 'step_order' => 1, 'instructions' => 'Create or update the record. Ensure all required fields are complete.', 'pool_enabled' => false],
                    ['name' => 'Review', 'step_type' => 'review', 'step_order' => 2, 'instructions' => 'Review the record for accuracy, completeness, and standards compliance.', 'pool_enabled' => true],
                    ['name' => 'Approve', 'step_type' => 'approval', 'step_order' => 3, 'instructions' => 'Final approval before publication. Check classification, rights, and metadata quality.', 'pool_enabled' => false],
                    ['name' => 'Publish', 'step_type' => 'terminal', 'step_order' => 4, 'instructions' => 'Record is published and available for discovery.', 'pool_enabled' => false],
                ];

                foreach ($steps as $step) {
                    DB::table('workflow_steps')->insert(array_merge($step, [
                        'workflow_id' => $workflow->id,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
        }

        // Default SLA policy
        DB::table('workflow_sla_policies')->updateOrInsert(
            ['name' => 'Standard SLA'],
            [
                'description' => 'Default SLA: warn at 48h, breach at 72h, notify supervisor',
                'warning_hours' => 48,
                'breach_hours' => 72,
                'escalation_action' => 'notify',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Default publish gate rules
        $gateRules = [
            [
                'name' => 'Required Title',
                'description' => 'Every record must have a title before publication',
                'rule_type' => 'required_fields',
                'rule_config' => json_encode(['fields' => ['rico:title']]),
                'priority' => 100,
            ],
            [
                'name' => 'Security Classification Check',
                'description' => 'Records with Secret or higher classification cannot be published without approval',
                'rule_type' => 'classification',
                'rule_config' => json_encode(['max_publishable_level' => 2]),
                'priority' => 90,
            ],
            [
                'name' => 'Workflow Completion',
                'description' => 'Record must have completed its assigned workflow before publication',
                'rule_type' => 'workflow',
                'rule_config' => json_encode(['require_completed' => true]),
                'priority' => 80,
            ],
        ];

        foreach ($gateRules as $rule) {
            DB::table('publish_gate_rules')->updateOrInsert(
                ['name' => $rule['name']],
                array_merge($rule, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Update security classification handling instructions
        $classInstructions = [
            'UNCLASSIFIED' => ['handling_instructions' => 'No special handling required. May be shared publicly.', 'banner_text' => null, 'retention_years' => null],
            'RESTRICTED' => ['handling_instructions' => 'Handle with care. Share only with authorized personnel. Do not leave unattended.', 'banner_text' => 'RESTRICTED — Authorized Personnel Only', 'retention_years' => 10],
            'CONFIDENTIAL' => ['handling_instructions' => 'Store in locked containers when not in use. Transmit only via secure channels. Track all copies.', 'banner_text' => 'CONFIDENTIAL — Do Not Distribute', 'retention_years' => 20],
            'SECRET' => ['handling_instructions' => 'Secure storage mandatory. Two-person integrity required for access. All access logged and audited. No digital copies without approval.', 'banner_text' => 'SECRET — Unauthorized Disclosure Subject to Sanctions', 'retention_years' => 30],
            'TOP_SECRET' => ['handling_instructions' => 'Compartmentalized access only. Two-person integrity required. No electronic transmission. Physical security escort for transport. Destruction must be witnessed and documented.', 'banner_text' => 'TOP SECRET — Exceptionally Grave Damage if Disclosed', 'retention_years' => 50],
        ];

        foreach ($classInstructions as $code => $data) {
            DB::table('security_classifications')
                ->where('code', $code)
                ->update($data);
        }
    }
}
