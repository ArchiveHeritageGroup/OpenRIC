<?php

declare(strict_types=1);

namespace OpenRiC\SettingsManage\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seeds OpenRiC settings from Heratio database and default values.
 *
 * Heratio uses: setting + setting_i18n tables (225 settings)
 * OpenRiC uses: settings table with group/key/value
 *
 * Run: php artisan settings:seed-from-heratio
 */
class SeedFromHeratio extends Command
{
    protected $signature = 'settings:seed-from-heratio {--dry-run : Show what would be inserted without inserting}';
    protected $description = 'Seed OpenRiC settings from Heratio database and default values';

    // Settings that are multi-value arrays to skip (they won't map cleanly)
    private const SKIP_PATTERNS = [
        'plugins',
        'element_visibility',
        'access_statement',
        'default_template',
    ];

    // Theme settings to map to 'theme' group
    private const THEME_SETTINGS = [
        'header_background_colour',
        'toggle_title',
        'toggle_logo',
        'toggle_description',
        'toggle_io_slider',
        'toggle_language_menu',
        'toggle_copyright_filter',
        'toggle_material_filter',
        'default_archival_description_browse_view',
        'default_repository_browse_view',
        'treeview_type',
        'sidebar_collapsed',
        'sort_browser_anonymous',
        'sort_browser_user',
        'sort_treeview_informationobject',
        'display_mode',
        'primary_color',
        'secondary_color',
        'header_bg',
        'footer_bg',
        'sidebar_bg',
        'body_bg',
        'body_text',
        'link_color',
        'success_color',
        'danger_color',
        'warning_color',
        'info_color',
        'custom_css',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN - No changes will be made');
        }

        // ─── First, seed all OpenRiC-specific default settings ───────────────

        $this->seedDefaultSettings($dryRun);

        // ─── Then try to import from Heratio ───────────────────────────────────

        $this->info('Connecting to Heratio database...');

        try {
            // Only get English settings (source_culture = 'en')
            $heratioSettings = DB::connection('heratio')
                ->table('setting')
                ->where('setting.source_culture', 'en')
                ->leftJoin('setting_i18n', function ($j): void {
                    $j->on('setting.id', '=', 'setting_i18n.id')
                      ->where('setting_i18n.culture', '=', 'en');
                })
                ->select('setting.id', 'setting.name', 'setting.scope', 'setting.source_culture', 'setting_i18n.value')
                ->orderBy('setting.id')
                ->get();
        } catch (\Exception $e) {
            $this->warn('Failed to connect to Heratio database: ' . $e->getMessage());
            $this->info('Skipping Heratio import. Default settings have been seeded.');
            return self::SUCCESS;
        }

        $this->info("Found {$heratioSettings->count()} settings in Heratio");

        $toInsert = [];
        $skipped = [];

        foreach ($heratioSettings as $setting) {
            // Skip multi-value settings
            $shouldSkip = false;
            foreach (self::SKIP_PATTERNS as $pattern) {
                if (str_contains($setting->name, $pattern) || str_contains($setting->scope ?? '', $pattern)) {
                    $skipped[] = "{$setting->name} ({$setting->scope}) - {$pattern}";
                    $shouldSkip = true;
                    break;
                }
            }
            if ($shouldSkip) {
                continue;
            }

            // Skip if value is empty
            if (empty($setting->value) && $setting->name !== 'oai_repository_code' && $setting->name !== 'oai_admin_emails') {
                $skipped[] = "{$setting->name} ({$setting->scope}) - empty value";
                continue;
            }

            $group = $this->determineGroup($setting->name, $setting->scope);

            if ($group === 'skip') {
                $skipped[] = "{$setting->name} ({$setting->scope}) - no group mapping";
                continue;
            }

            $toInsert[] = [
                'group' => $group,
                'key' => $setting->name,
                'value' => $setting->value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->info('Grouped ' . count($toInsert) . ' settings for insertion');
        $this->info('Skipped ' . count($skipped) . ' settings (not applicable or empty)');

        if ($dryRun) {
            $this->table(['Group', 'Key', 'Value'], collect($toInsert)->map(fn($s) => [$s['group'], $s['key'], substr($s['value'], 0, 50) . (strlen($s['value']) > 50 ? '...' : '')])->toArray());
            return self::SUCCESS;
        }

        $inserted = 0;
        $updated = 0;
        $errors = 0;

        foreach ($toInsert as $setting) {
            try {
                $existing = DB::table('settings')
                    ->where('group', $setting['group'])
                    ->where('key', $setting['key'])
                    ->first();

                if ($existing) {
                    DB::table('settings')
                        ->where('id', $existing->id)
                        ->update([
                            'value' => $setting['value'],
                            'updated_at' => now(),
                        ]);
                    $updated++;
                } else {
                    DB::table('settings')->insert($setting);
                    $inserted++;
                }
            } catch (\Exception $e) {
                $this->warn("Failed to insert {$setting['key']}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->info("Heratio: Inserted {$inserted}, Updated {$updated}, Errors {$errors}");

        return self::SUCCESS;
    }

    /**
     * Seed all OpenRiC-specific default settings
     */
    private function seedDefaultSettings(bool $dryRun): void
    {
        $this->info('Seeding OpenRiC default settings...');

        $defaults = $this->getDefaultSettings();

        if ($dryRun) {
            $this->info('Would insert ' . count($defaults) . ' default settings');
            return;
        }

        $inserted = 0;
        foreach ($defaults as $setting) {
            try {
                DB::table('settings')->updateOrInsert(
                    ['group' => $setting['group'], 'key' => $setting['key']],
                    [
                        'value' => $setting['value'],
                        'type' => $setting['type'] ?? 'text',
                        'description' => $setting['description'] ?? '',
                        'updated_at' => now(),
                    ]
                );
                $inserted++;
            } catch (\Exception $e) {
                $this->warn("Failed: {$setting['key']}: " . $e->getMessage());
            }
        }

        $this->info("Seeded {$inserted} default settings");
    }

    /**
     * Get all default settings for OpenRiC
     */
    private function getDefaultSettings(): array
    {
        $defaults = [];

        // ─── General settings ────────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'general', 'key' => 'site_title', 'value' => 'OpenRiC', 'type' => 'text', 'description' => 'Site title'],
            ['group' => 'general', 'key' => 'site_description', 'value' => 'Records in Contexts', 'type' => 'text', 'description' => 'Site description'],
            ['group' => 'general', 'key' => 'site_base_url', 'value' => '', 'type' => 'text', 'description' => 'Base URL'],
            ['group' => 'general', 'key' => 'default_language', 'value' => 'en', 'type' => 'text', 'description' => 'Default language'],
            ['group' => 'general', 'key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'text', 'description' => 'Date format'],
            ['group' => 'general', 'key' => 'results_per_page', 'value' => '25', 'type' => 'number', 'description' => 'Results per page'],
        ]);

        // ─── Accession settings ──────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'accession', 'key' => 'accession_number_prefix', 'value' => 'ACC', 'type' => 'text', 'description' => 'Accession number prefix'],
            ['group' => 'accession', 'key' => 'accession_number_format', 'value' => '{prefix}/{year}/{sequence}', 'type' => 'text', 'description' => 'Accession number format'],
            ['group' => 'accession', 'key' => 'auto_generate_accession', 'value' => '1', 'type' => 'boolean', 'description' => 'Auto-generate accession numbers'],
            ['group' => 'accession', 'key' => 'accession_sequence_start', 'value' => '1', 'type' => 'number', 'description' => 'Starting sequence number'],
            ['group' => 'accession', 'key' => 'require_transfer_date', 'value' => '1', 'type' => 'boolean', 'description' => 'Require transfer date'],
            ['group' => 'accession', 'key' => 'require_source', 'value' => '1', 'type' => 'boolean', 'description' => 'Require source/donor information'],
            ['group' => 'accession', 'key' => 'enable_accession_agreements', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable accession agreements'],
            ['group' => 'accession', 'key' => 'notify_on_accession', 'value' => '0', 'type' => 'boolean', 'description' => 'Notify staff on new accession'],
            ['group' => 'accession', 'key' => 'accession_workflow_enabled', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable accession workflow'],
        ]);

        // ─── Ingest settings ──────────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'ingest', 'key' => 'ingest_ner', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable named entity recognition'],
            ['group' => 'ingest', 'key' => 'ingest_ocr', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable OCR on ingest'],
            ['group' => 'ingest', 'key' => 'ingest_virus_scan', 'value' => '1', 'type' => 'boolean', 'description' => 'Scan files for viruses'],
            ['group' => 'ingest', 'key' => 'ingest_summarize', 'value' => '0', 'type' => 'boolean', 'description' => 'Generate AI summaries'],
            ['group' => 'ingest', 'key' => 'ingest_spellcheck', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable spell checking'],
            ['group' => 'ingest', 'key' => 'ingest_translate', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable translation'],
            ['group' => 'ingest', 'key' => 'ingest_format_id', 'value' => '0', 'type' => 'boolean', 'description' => 'Identify file formats'],
            ['group' => 'ingest', 'key' => 'ingest_face_detect', 'value' => '0', 'type' => 'boolean', 'description' => 'Detect faces in images'],
            ['group' => 'ingest', 'key' => 'ingest_create_records', 'value' => '1', 'type' => 'boolean', 'description' => 'Create archival records'],
            ['group' => 'ingest', 'key' => 'ingest_generate_sip', 'value' => '1', 'type' => 'boolean', 'description' => 'Generate Submission Information Package'],
            ['group' => 'ingest', 'key' => 'ingest_generate_aip', 'value' => '1', 'type' => 'boolean', 'description' => 'Generate Archival Information Package'],
            ['group' => 'ingest', 'key' => 'ingest_generate_dip', 'value' => '1', 'type' => 'boolean', 'description' => 'Generate Dissemination Information Package'],
            ['group' => 'ingest', 'key' => 'ingest_thumbnails', 'value' => '1', 'type' => 'boolean', 'description' => 'Generate thumbnails'],
            ['group' => 'ingest', 'key' => 'ingest_reference', 'value' => '0', 'type' => 'boolean', 'description' => 'Generate reference copies'],
            ['group' => 'ingest', 'key' => 'ingest_default_sector', 'value' => 'archive', 'type' => 'select', 'description' => 'Default sector'],
            ['group' => 'ingest', 'key' => 'ingest_default_standard', 'value' => 'isadg', 'type' => 'select', 'description' => 'Default descriptive standard'],
        ]);

        // ─── Metadata extraction settings ────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'metadata', 'key' => 'extract_exif', 'value' => '1', 'type' => 'boolean', 'description' => 'Extract EXIF metadata'],
            ['group' => 'metadata', 'key' => 'extract_iptc', 'value' => '1', 'type' => 'boolean', 'description' => 'Extract IPTC metadata'],
            ['group' => 'metadata', 'key' => 'extract_pdf_text', 'value' => '1', 'type' => 'boolean', 'description' => 'Extract text from PDFs'],
            ['group' => 'metadata', 'key' => 'extract_docx_text', 'value' => '1', 'type' => 'boolean', 'description' => 'Extract text from Word documents'],
            ['group' => 'metadata', 'key' => 'metadata_normalize_dates', 'value' => '1', 'type' => 'boolean', 'description' => 'Normalize date formats'],
            ['group' => 'metadata', 'key' => 'metadata_map_to_fields', 'value' => '1', 'type' => 'boolean', 'description' => 'Map metadata to fields'],
        ]);

        // ─── Integrity settings ──────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'integrity', 'key' => 'integrity_enabled', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable integrity checking'],
            ['group' => 'integrity', 'key' => 'integrity_auto_baseline', 'value' => '1', 'type' => 'boolean', 'description' => 'Auto-create baselines'],
            ['group' => 'integrity', 'key' => 'integrity_notify_on_failure', 'value' => '1', 'type' => 'boolean', 'description' => 'Notify on integrity failure'],
            ['group' => 'integrity', 'key' => 'integrity_notify_on_mismatch', 'value' => '1', 'type' => 'boolean', 'description' => 'Notify on checksum mismatch'],
            ['group' => 'integrity', 'key' => 'integrity_default_algorithm', 'value' => 'sha256', 'type' => 'select', 'description' => 'Default checksum algorithm'],
        ]);

        // ─── Encryption settings ──────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'encryption', 'key' => 'encryption_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable encryption'],
            ['group' => 'encryption', 'key' => 'encryption_encrypt_derivatives', 'value' => '0', 'type' => 'boolean', 'description' => 'Encrypt derivative files'],
            ['group' => 'encryption', 'key' => 'encryption_algorithm', 'value' => 'aes-256-gcm', 'type' => 'select', 'description' => 'Encryption algorithm'],
        ]);

        // ─── IIIF settings ──────────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'iiif', 'key' => 'iiif_enabled', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable IIIF support'],
            ['group' => 'iiif', 'key' => 'iiif_show_navigator', 'value' => '1', 'type' => 'boolean', 'description' => 'Show image navigator'],
            ['group' => 'iiif', 'key' => 'iiif_show_rotation', 'value' => '1', 'type' => 'boolean', 'description' => 'Allow rotation controls'],
            ['group' => 'iiif', 'key' => 'iiif_show_fullscreen', 'value' => '1', 'type' => 'boolean', 'description' => 'Show fullscreen button'],
            ['group' => 'iiif', 'key' => 'iiif_enable_annotations', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable annotation support'],
            ['group' => 'iiif', 'key' => 'iiif_viewer', 'value' => 'openseadragon', 'type' => 'select', 'description' => 'Default IIIF viewer'],
        ]);

        // ─── Voice & AI settings ─────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'voice_ai', 'key' => 'voice_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable voice AI features'],
            ['group' => 'voice_ai', 'key' => 'voice_continuous_listening', 'value' => '0', 'type' => 'boolean', 'description' => 'Continuous listening'],
            ['group' => 'voice_ai', 'key' => 'voice_show_floating_btn', 'value' => '1', 'type' => 'boolean', 'description' => 'Show floating mic button'],
            ['group' => 'voice_ai', 'key' => 'voice_hover_read_enabled', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable hover-to-read'],
            ['group' => 'voice_ai', 'key' => 'voice_audit_ai_calls', 'value' => '0', 'type' => 'boolean', 'description' => 'Audit AI API calls'],
            ['group' => 'voice_ai', 'key' => 'voice_llm_provider', 'value' => 'local', 'type' => 'select', 'description' => 'LLM provider'],
            ['group' => 'voice_ai', 'key' => 'voice_cloud_model', 'value' => 'claude-sonnet-4-20250514', 'type' => 'select', 'description' => 'Cloud LLM model'],
            ['group' => 'voice_ai', 'key' => 'voice_ollama_url', 'value' => 'http://localhost:11434', 'type' => 'text', 'description' => 'Ollama server URL'],
            ['group' => 'voice_ai', 'key' => 'voice_ollama_model', 'value' => 'llama3', 'type' => 'text', 'description' => 'Ollama model'],
        ]);

        // ─── Email settings ─────────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'email', 'key' => 'smtp_host', 'value' => '', 'type' => 'text', 'description' => 'SMTP server host'],
            ['group' => 'email', 'key' => 'smtp_port', 'value' => '587', 'type' => 'number', 'description' => 'SMTP port'],
            ['group' => 'email', 'key' => 'smtp_username', 'value' => '', 'type' => 'text', 'description' => 'SMTP username'],
            ['group' => 'email', 'key' => 'smtp_password', 'value' => '', 'type' => 'password', 'description' => 'SMTP password'],
            ['group' => 'email', 'key' => 'smtp_encryption', 'value' => 'tls', 'type' => 'select', 'description' => 'SMTP encryption'],
            ['group' => 'email', 'key' => 'mail_from_address', 'value' => 'noreply@openric.local', 'type' => 'text', 'description' => 'From email'],
            ['group' => 'email', 'key' => 'mail_from_name', 'value' => 'OpenRiC', 'type' => 'text', 'description' => 'From name'],
            ['group' => 'email', 'key' => 'notif_new_accession', 'value' => '0', 'type' => 'boolean', 'description' => 'Notify on new accession'],
            ['group' => 'email', 'key' => 'notif_ingest_complete', 'value' => '0', 'type' => 'boolean', 'description' => 'Notify on ingest complete'],
            ['group' => 'email', 'key' => 'notif_integrity_failure', 'value' => '1', 'type' => 'boolean', 'description' => 'Notify on integrity failure'],
        ]);

        // ─── Theme settings ──────────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'theme', 'key' => 'primary_color', 'value' => '#1a5276', 'type' => 'color', 'description' => 'Primary color'],
            ['group' => 'theme', 'key' => 'secondary_color', 'value' => '#6c757d', 'type' => 'color', 'description' => 'Secondary color'],
            ['group' => 'theme', 'key' => 'header_bg', 'value' => '#212529', 'type' => 'color', 'description' => 'Header background'],
            ['group' => 'theme', 'key' => 'header_text', 'value' => '#ffffff', 'type' => 'color', 'description' => 'Header text'],
            ['group' => 'theme', 'key' => 'footer_bg', 'value' => '#212529', 'type' => 'color', 'description' => 'Footer background'],
            ['group' => 'theme', 'key' => 'footer_text_color', 'value' => '#ffffff', 'type' => 'color', 'description' => 'Footer text'],
            ['group' => 'theme', 'key' => 'sidebar_bg', 'value' => '#f8f9fa', 'type' => 'color', 'description' => 'Sidebar background'],
            ['group' => 'theme', 'key' => 'sidebar_text', 'value' => '#333333', 'type' => 'color', 'description' => 'Sidebar text'],
            ['group' => 'theme', 'key' => 'body_bg', 'value' => '#ffffff', 'type' => 'color', 'description' => 'Body background'],
            ['group' => 'theme', 'key' => 'body_text', 'value' => '#212529', 'type' => 'color', 'description' => 'Body text'],
            ['group' => 'theme', 'key' => 'link_color', 'value' => '#1a5276', 'type' => 'color', 'description' => 'Link color'],
            ['group' => 'theme', 'key' => 'success_color', 'value' => '#28a745', 'type' => 'color', 'description' => 'Success color'],
            ['group' => 'theme', 'key' => 'danger_color', 'value' => '#dc3545', 'type' => 'color', 'description' => 'Danger color'],
            ['group' => 'theme', 'key' => 'warning_color', 'value' => '#ffc107', 'type' => 'color', 'description' => 'Warning color'],
            ['group' => 'theme', 'key' => 'info_color', 'value' => '#17a2b8', 'type' => 'color', 'description' => 'Info color'],
            ['group' => 'theme', 'key' => 'custom_css', 'value' => '', 'type' => 'textarea', 'description' => 'Custom CSS'],
        ]);

        // ─── OAI settings ────────────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'oai', 'key' => 'oai_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable OAI-PMH'],
            ['group' => 'oai', 'key' => 'oai_repository_code', 'value' => 'openric', 'type' => 'text', 'description' => 'Repository code'],
            ['group' => 'oai', 'key' => 'oai_repository_name', 'value' => 'OpenRiC Repository', 'type' => 'text', 'description' => 'Repository name'],
            ['group' => 'oai', 'key' => 'oai_admin_emails', 'value' => '', 'type' => 'text', 'description' => 'Admin emails'],
            ['group' => 'oai', 'key' => 'oai_sample_identifier', 'value' => '', 'type' => 'text', 'description' => 'Sample record identifier'],
        ]);

        // ─── Security settings ──────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'security', 'key' => 'require_ssl_admin', 'value' => '0', 'type' => 'boolean', 'description' => 'Require SSL for admin'],
            ['group' => 'security', 'key' => 'require_strong_passwords', 'value' => '0', 'type' => 'boolean', 'description' => 'Require strong passwords'],
            ['group' => 'security', 'key' => 'limit_admin_ip', 'value' => '', 'type' => 'text', 'description' => 'Limit admin to IP addresses'],
        ]);

        // ─── Fuseki/RDF settings ─────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'fuseki', 'key' => 'fuseki_sync_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable Fuseki sync'],
            ['group' => 'fuseki', 'key' => 'fuseki_queue_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable Fuseki queue'],
            ['group' => 'fuseki', 'key' => 'fuseki_sync_on_save', 'value' => '1', 'type' => 'boolean', 'description' => 'Sync on save'],
            ['group' => 'fuseki', 'key' => 'fuseki_sync_on_delete', 'value' => '1', 'type' => 'boolean', 'description' => 'Sync on delete'],
            ['group' => 'fuseki', 'key' => 'fuseki_cascade_delete', 'value' => '0', 'type' => 'boolean', 'description' => 'Cascade delete'],
        ]);

        // ─── AI Condition settings ──────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'ai_condition', 'key' => 'ai_condition_auto_scan', 'value' => '0', 'type' => 'boolean', 'description' => 'Auto-scan on ingest'],
            ['group' => 'ai_condition', 'key' => 'ai_condition_overlay_enabled', 'value' => '1', 'type' => 'boolean', 'description' => 'Show overlay in viewer'],
            ['group' => 'ai_condition', 'key' => 'ai_condition_api_key', 'value' => '', 'type' => 'password', 'description' => 'API key'],
        ]);

        // ─── Spectrum/Collections settings ──────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'spectrum', 'key' => 'spectrum_default_template', 'value' => '', 'type' => 'text', 'description' => 'Default template'],
            ['group' => 'spectrum', 'key' => 'spectrum_required_fields', 'value' => '', 'type' => 'text', 'description' => 'Required fields'],
        ]);

        // ─── Media player settings ──────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'media', 'key' => 'media_player_default_type', 'value' => 'openseadragon', 'type' => 'select', 'description' => 'Default player type'],
            ['group' => 'media', 'key' => 'media_thumbnail_size', 'value' => '200', 'type' => 'number', 'description' => 'Thumbnail size'],
        ]);

        // ─── Jobs settings ───────────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'jobs', 'key' => 'jobs_queue_connection', 'value' => 'database', 'type' => 'select', 'description' => 'Queue connection'],
            ['group' => 'jobs', 'key' => 'jobs_retry_attempts', 'value' => '3', 'type' => 'number', 'description' => 'Retry attempts'],
        ]);

        // ─── Data Protection settings ───────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'data_protection', 'key' => 'gdpr_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable GDPR mode'],
            ['group' => 'data_protection', 'key' => 'anonymize_after_days', 'value' => '365', 'type' => 'number', 'description' => 'Days before anonymization'],
        ]);

        // ─── Features toggles ────────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'features', 'key' => 'feature_accessions', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable accessions'],
            ['group' => 'features', 'key' => 'feature_ingest', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable ingest'],
            ['group' => 'features', 'key' => 'feature_preservation', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable preservation'],
            ['group' => 'features', 'key' => 'feature_ai', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable AI features'],
        ]);

        // ─── Languages ──────────────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'languages', 'key' => 'en', 'value' => 'English', 'type' => 'text', 'description' => 'English'],
            ['group' => 'languages', 'key' => 'af', 'value' => 'Afrikaans', 'type' => 'text', 'description' => 'Afrikaans'],
        ]);

        // ─── UI Labels ───────────────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'ui_label', 'key' => 'search_placeholder', 'value' => 'Search records...', 'type' => 'text', 'description' => 'Search placeholder'],
            ['group' => 'ui_label', 'key' => 'browse_label', 'value' => 'Browse', 'type' => 'text', 'description' => 'Browse label'],
        ]);

        // ─── Element Visibility ─────────────────────────────────────────────
        $defaults = array_merge($defaults, [
            ['group' => 'element_visibility', 'key' => 'show_breadcrumb', 'value' => '1', 'type' => 'boolean', 'description' => 'Show breadcrumb'],
            ['group' => 'element_visibility', 'key' => 'show_treeview', 'value' => '1', 'type' => 'boolean', 'description' => 'Show treeview'],
        ]);

        return $defaults;
    }

    private function determineGroup(string $name, ?string $scope): string
    {
        // i18n languages (store under 'languages' group)
        if ($scope === 'i18n_languages') {
            return 'languages';
        }

        // Theme settings (UI toggles, display options)
        if (in_array($name, self::THEME_SETTINGS) || str_starts_with($name, 'toggle')) {
            return 'theme';
        }

        // OAI settings
        if (str_starts_with($name, 'oai_') || $scope === 'oai' || $name === 'resumption_token_limit') {
            return 'oai';
        }

        // AI settings
        if (str_contains($name, 'qdrant') || str_contains($name, 'ollama') || str_contains($name, 'embedding')) {
            return 'ai';
        }

        // Elasticsearch
        if (str_starts_with($name, 'es_') || str_starts_with($name, 'elasticsearch')) {
            return 'elasticsearch';
        }

        // Fuseki/RDF
        if (str_starts_with($name, 'fuseki')) {
            return 'fuseki';
        }

        // Security
        if ($name === 'require_ssl_admin' || $name === 'require_strong_passwords' || $name === 'limit_admin_ip') {
            return 'security';
        }

        // UI Labels - map to general for now
        if ($scope === 'ui_label') {
            return 'general';
        }

        // General settings
        $generalSettings = [
            'version', 'milestone', 'upload_dir', 'reference_image', 'hits_per_page',
            'multi_repository', 'sort_treeview', 'sort_browser', 'defaultPubStatus',
            'accession_mask', 'accession_counter', 'separator_character',
            'repository_quota', 'sword_deposit_dir', 'enable_institutional_scoping',
            'check_for_updates', 'explode_multipage_files', 'show_tooltips',
            'inherit_code_informationobject', 'privacy_notification_enabled', 'privacy_notification',
        ];

        if (in_array($name, $generalSettings)) {
            return 'general';
        }

        // Skip unknown settings
        return 'skip';
    }
}
