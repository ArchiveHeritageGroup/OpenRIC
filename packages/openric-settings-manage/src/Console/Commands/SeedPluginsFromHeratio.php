<?php

declare(strict_types=1);

namespace OpenRiC\SettingsManage\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seeds OpenRiC plugins from Heratio database.
 *
 * Heratio stores plugin settings in ahg_settings dropdowns.
 * Run: php artisan settings:seed-plugins-from-heratio
 */
class SeedPluginsFromHeratio extends Command
{
    protected $signature = 'settings:seed-plugins {--dry-run : Show what would be inserted without inserting}';
    protected $description = 'Seed OpenRiC plugins from Heratio database';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN - No changes will be made');
        }

        $this->info('Connecting to Heratio database...');

        try {
            // Get plugins from Heratio ahg_settings dropdowns
            $heratioPlugins = DB::connection('heratio')
                ->table('ahg_settings')
                ->where('type', 'dropdown')
                ->where('code', 'plugins')
                ->first();
        } catch (\Exception $e) {
            $this->warn('Failed to connect to Heratio database: ' . $e->getMessage());
            return self::SUCCESS;
        }

        if (!$heratioPlugins) {
            $this->info('No plugins configuration found in Heratio.');
            return self::SUCCESS;
        }

        // Get dropdown options
        $dropdownOptions = [];
        try {
            $dropdownOptions = DB::connection('heratio')
                ->table('ahg_settings_dropdown_options')
                ->where('setting_id', $heratioPlugins->id)
                ->orderBy('sort_order')
                ->get();
        } catch (\Exception $e) {
            $this->warn('Could not fetch dropdown options: ' . $e->getMessage());
        }

        // Default plugins based on Heratio structure
        $plugins = [
            // Core plugins
            ['name' => 'OpenRiC Core', 'version' => '1.0.0', 'description' => 'Core RiC-O functionality', 'enabled' => true, 'category' => 'Core', 'code' => 'openric_core'],
            ['name' => 'Qdrant AI Embeddings', 'version' => '1.0.0', 'description' => 'Vector database for AI embeddings and semantic search', 'enabled' => true, 'category' => 'AI', 'code' => 'qdrant_embeddings'],
            ['name' => 'Ollama AI Integration', 'version' => '1.0.0', 'description' => 'Local LLM integration for AI features', 'enabled' => true, 'category' => 'AI', 'code' => 'ollama_integration'],
            ['name' => 'Anthropic Claude Integration', 'version' => '1.0.0', 'description' => 'Cloud LLM integration via Anthropic API', 'enabled' => false, 'category' => 'AI', 'code' => 'anthropic_claude'],
            
            // Management plugins
            ['name' => 'Record Management', 'version' => '1.0.0', 'description' => 'Record and record set management', 'enabled' => true, 'category' => 'Management', 'code' => 'record_management'],
            ['name' => 'Agent Management', 'version' => '1.0.0', 'description' => 'Agent management (persons, families, corporate bodies)', 'enabled' => true, 'category' => 'Management', 'code' => 'agent_management'],
            ['name' => 'Place Management', 'version' => '1.0.0', 'description' => 'Place and location management', 'enabled' => true, 'category' => 'Management', 'code' => 'place_management'],
            ['name' => 'Accession Management', 'version' => '1.0.0', 'description' => 'Accession and intake workflow', 'enabled' => true, 'category' => 'Management', 'code' => 'accession_management'],
            ['name' => '3D Model Viewer', 'version' => '1.0.0', 'description' => '3D model viewing and management', 'enabled' => false, 'category' => 'Management', 'code' => '3d_model_viewer'],
            
            // Media plugins
            ['name' => 'Digital Object Management', 'version' => '1.0.0', 'description' => 'Digital objects, derivatives, and media management', 'enabled' => true, 'category' => 'Media', 'code' => 'digital_objects'],
            ['name' => 'IIIF Image Viewer', 'version' => '1.0.0', 'description' => 'IIIF-compatible image viewer (OpenSeadragon, Mirador, Leaflet)', 'enabled' => true, 'category' => 'Media', 'code' => 'iiif_viewer'],
            ['name' => 'Media Player', 'version' => '1.0.0', 'description' => 'Audio and video playback support', 'enabled' => true, 'category' => 'Media', 'code' => 'media_player'],
            ['name' => 'ImageMagick Processing', 'version' => '1.0.0', 'description' => 'Image processing and format conversion', 'enabled' => true, 'category' => 'Media', 'code' => 'imagemagick'],
            ['name' => 'FFmpeg Processing', 'version' => '1.0.0', 'description' => 'Video and audio transcoding', 'enabled' => true, 'category' => 'Media', 'code' => 'ffmpeg'],
            ['name' => 'Tesseract OCR', 'version' => '1.0.0', 'description' => 'Optical character recognition', 'enabled' => false, 'category' => 'Media', 'code' => 'tesseract_ocr'],
            
            // Integration plugins
            ['name' => 'OAI-PMH Repository', 'version' => '1.0.0', 'description' => 'OAI-PMH protocol for metadata harvesting', 'enabled' => false, 'category' => 'Integration', 'code' => 'oai_pmh'],
            ['name' => 'Fedora Commons Integration', 'version' => '1.0.0', 'description' => 'Fedora repository integration', 'enabled' => false, 'category' => 'Integration', 'code' => 'fedora_commons'],
            ['name' => 'AtoM/EAD Import', 'version' => '1.0.0', 'description' => 'Import from AtoM and EAD files', 'enabled' => true, 'category' => 'Integration', 'code' => 'atom_ead_import'],
            ['name' => 'ISAD(G) Export', 'version' => '1.0.0', 'description' => 'Export to ISAD(G) finding aids', 'enabled' => true, 'category' => 'Integration', 'code' => 'isadg_export'],
            ['name' => 'Dublin Core Export', 'version' => '1.0.0', 'description' => 'Dublin Core metadata export', 'enabled' => true, 'category' => 'Integration', 'code' => 'dublin_core_export'],
            
            // Preservation plugins
            ['name' => 'Preservation Planning', 'version' => '1.0.0', 'description' => 'Preservation workflow and planning', 'enabled' => true, 'category' => 'Preservation', 'code' => 'preservation_planning'],
            ['name' => 'Checksum Verification', 'version' => '1.0.0', 'description' => 'File integrity checking (SHA-256, MD5)', 'enabled' => true, 'category' => 'Preservation', 'code' => 'checksum_verification'],
            ['name' => 'Virus Scanning', 'version' => '1.0.0', 'description' => 'ClamAV virus scanning', 'enabled' => true, 'category' => 'Preservation', 'code' => 'virus_scanning'],
            ['name' => 'Format Identification', 'version' => '1.0.0', 'description' => 'PRONOM-based format identification', 'enabled' => true, 'category' => 'Preservation', 'code' => 'format_identification'],
            ['name' => 'SIP/AIP/DIP Generation', 'version' => '1.0.0', 'description' => 'Submission, Archival, and DIP generation', 'enabled' => true, 'category' => 'Preservation', 'code' => 'sip_aip_dip'],
            
            // System plugins
            ['name' => 'Settings Management', 'version' => '1.0.0', 'description' => 'System configuration and settings', 'enabled' => true, 'category' => 'System', 'code' => 'settings_management'],
            ['name' => 'User Management', 'version' => '1.0.0', 'description' => 'User accounts and permissions', 'enabled' => true, 'category' => 'System', 'code' => 'user_management'],
            ['name' => 'Role-Based Access Control', 'version' => '1.0.0', 'description' => 'RBAC and permissions management', 'enabled' => true, 'category' => 'System', 'code' => 'rbac'],
            ['name' => 'LDAP Authentication', 'version' => '1.0.0', 'description' => 'LDAP/Active Directory integration', 'enabled' => false, 'category' => 'System', 'code' => 'ldap_auth'],
            ['name' => 'Theme Configuration', 'version' => '1.0.0', 'description' => 'Custom themes and branding', 'enabled' => true, 'category' => 'System', 'code' => 'theme_config'],
            
            // Voice & Accessibility
            ['name' => 'Text-to-Speech', 'version' => '1.0.0', 'description' => 'Voice reading and audio output', 'enabled' => true, 'category' => 'Accessibility', 'code' => 'text_to_speech'],
            ['name' => 'Speech-to-Text', 'version' => '1.0.0', 'description' => 'Voice commands and dictation', 'enabled' => false, 'category' => 'Accessibility', 'code' => 'speech_to_text'],
            ['name' => 'Screen Reader Support', 'version' => '1.0.0', 'description' => 'ARIA labels and accessibility features', 'enabled' => true, 'category' => 'Accessibility', 'code' => 'screen_reader'],
            ['name' => 'Multi-Language Support', 'version' => '1.0.0', 'description' => 'i18n and l10n with language files', 'enabled' => true, 'category' => 'Accessibility', 'code' => 'multilanguage'],
            
            // Analysis plugins
            ['name' => 'Named Entity Recognition', 'version' => '1.0.0', 'description' => 'AI-powered NER for archival entities', 'enabled' => false, 'category' => 'Analysis', 'code' => 'ner'],
            ['name' => 'Condition Assessment AI', 'version' => '1.0.0', 'description' => 'AI-based material condition analysis', 'enabled' => false, 'category' => 'Analysis', 'code' => 'condition_assessment'],
            ['name' => 'Face Detection', 'version' => '1.0.0', 'description' => 'Detect faces in photographs', 'enabled' => false, 'category' => 'Analysis', 'code' => 'face_detection'],
            ['name' => 'Semantic Search', 'version' => '1.0.0', 'description' => 'Vector-based semantic search', 'enabled' => true, 'category' => 'Analysis', 'code' => 'semantic_search'],
            
            // Reporting
            ['name' => 'Usage Statistics', 'version' => '1.0.0', 'description' => 'Repository activity and usage reports', 'enabled' => true, 'category' => 'Reporting', 'code' => 'usage_stats'],
            ['name' => 'Accession Reports', 'version' => '1.0.0', 'description' => 'Accession statistics and reports', 'enabled' => true, 'category' => 'Reporting', 'code' => 'accession_reports'],
            ['name' => 'Collection Analysis', 'version' => '1.0.0', 'description' => 'Collection depth and coverage analysis', 'enabled' => true, 'category' => 'Reporting', 'code' => 'collection_analysis'],
        ];

        // Process Heratio dropdown options if available
        if ($dropdownOptions->isNotEmpty()) {
            foreach ($dropdownOptions as $option) {
                // Update plugin status based on Heratio settings
                foreach ($plugins as &$plugin) {
                    $optionCode = strtolower(str_replace(' ', '_', $option->value ?? ''));
                    if (str_contains($plugin['code'], $optionCode) || str_contains($optionCode, $plugin['code'])) {
                        // Check if enabled/disabled in Heratio
                        if (isset($option->is_active)) {
                            $plugin['enabled'] = (bool) $option->is_active;
                        }
                        // Update version if available
                        if (!empty($option->version)) {
                            $plugin['version'] = $option->version;
                        }
                    }
                }
                unset($plugin);
            }
        }

        if ($dryRun) {
            $this->info('Would seed ' . count($plugins) . ' plugins');
            $this->table(['Category', 'Name', 'Enabled'], collect($plugins)->map(fn($p) => [$p['category'], $p['name'], $p['enabled'] ? 'Yes' : 'No'])->toArray());
            return self::SUCCESS;
        }

        $inserted = 0;
        $updated = 0;

        // Store plugins in settings table as JSON
        try {
            $existing = DB::table('settings')
                ->where('group', 'system')
                ->where('key', 'plugins_list')
                ->first();

            if ($existing) {
                DB::table('settings')
                    ->where('id', $existing->id)
                    ->update([
                        'value' => json_encode($plugins),
                        'type' => 'json',
                        'description' => 'Installed plugins list from Heratio',
                        'updated_at' => now(),
                    ]);
                $updated++;
            } else {
                DB::table('settings')->insert([
                    'group' => 'system',
                    'key' => 'plugins_list',
                    'value' => json_encode($plugins),
                    'type' => 'json',
                    'description' => 'Installed plugins list from Heratio',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $inserted++;
            }

            // Also store individual plugin status
            foreach ($plugins as $plugin) {
                $statusKey = 'plugin_' . $plugin['code'] . '_enabled';
                DB::table('settings')->updateOrInsert(
                    ['group' => 'system', 'key' => $statusKey],
                    [
                        'value' => $plugin['enabled'] ? '1' : '0',
                        'type' => 'boolean',
                        'description' => 'Plugin: ' . $plugin['name'],
                        'updated_at' => now(),
                    ]
                );
            }

            $this->info("Plugins seeded: {$inserted} inserted, {$updated} updated");
        } catch (\Exception $e) {
            $this->error('Failed to seed plugins: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}