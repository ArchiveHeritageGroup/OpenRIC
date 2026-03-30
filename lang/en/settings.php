<?php

declare(strict_types=1);

/**
 * OpenRiC Settings Language File
 * 
 * Extends AtoM/Qubit translations from:
 * /usr/share/nginx/archive/apps/qubit/i18n/{locale}/messages.xml
 * 
 * @see https://github.com/artefactual/atom - AtoM/EAD
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Settings UI Labels
    |--------------------------------------------------------------------------
    */
    'back_to_settings' => 'Back to Settings',
    'save_changes' => 'Save Changes',
    'settings' => 'Settings',
    'configuration' => 'Configuration',
    'enabled' => 'Enabled',
    'disabled' => 'Disabled',

    /*
    |--------------------------------------------------------------------------
    | Settings Section Labels
    |--------------------------------------------------------------------------
    */
    'sections' => [
        'accession' => 'Accession Management',
        'ai_condition' => 'AI Condition Assessment',
        'compliance' => 'Compliance',
        'data_protection' => 'Data Protection',
        'email' => 'Email Settings',
        'encryption' => 'Encryption',
        'faces' => 'Face Detection',
        'features' => 'Features',
        'fuseki' => 'Fuseki / RIC',
        'general' => 'General / Theme',
        'iiif' => 'IIIF Viewer',
        'ingest' => 'Data Ingest',
        'integrity' => 'Integrity Checking',
        'jobs' => 'Background Jobs',
        'media' => 'Media Player',
        'metadata' => 'Metadata Extraction',
        'multi_tenant' => 'Multi-Tenancy',
        'photos' => 'Condition Photos',
        'portable_export' => 'Portable Export',
        'security' => 'Security',
        'spectrum' => 'Spectrum / Collections',
        'voice_ai' => 'Voice & AI',
        'oai' => 'OAI-PMH',
        'languages' => 'Languages',
        'ui_label' => 'Interface Labels',
    ],

    /*
    |--------------------------------------------------------------------------
    | IIIF Settings
    |--------------------------------------------------------------------------
    */
    'iiif' => [
        'iiif_enabled' => 'Enable IIIF support',
        'iiif_enabled.description' => 'Enable IIIF-compatible image viewing and API',
        'iiif_show_navigator' => 'Show image navigator',
        'iiif_show_navigator.description' => 'Display the image navigator/thumbnail strip',
        'iiif_show_rotation' => 'Allow rotation controls',
        'iiif_show_rotation.description' => 'Show rotation buttons in the viewer',
        'iiif_show_fullscreen' => 'Show fullscreen button',
        'iiif_show_fullscreen.description' => 'Enable fullscreen viewing',
        'iiif_enable_annotations' => 'Enable annotation support',
        'iiif_enable_annotations.description' => 'Allow annotations on images',
        'iiif_viewer' => 'Default IIIF viewer',
        'iiif_viewer.description' => 'Choose the default viewer for IIIF images',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fuseki / RIC Settings
    |--------------------------------------------------------------------------
    */
    'fuseki' => [
        'fuseki_sync_enabled' => 'Enable Fuseki sync',
        'fuseki_sync_enabled.description' => 'Synchronize data with Apache Jena Fuseki triplestore',
        'fuseki_queue_enabled' => 'Enable queue processing',
        'fuseki_queue_enabled.description' => 'Process sync operations via queue',
        'fuseki_sync_on_save' => 'Sync on save',
        'fuseki_sync_on_save.description' => 'Automatically sync when records are saved',
        'fuseki_sync_on_delete' => 'Sync on delete',
        'fuseki_sync_on_delete.description' => 'Remove from triplestore when records are deleted',
        'fuseki_cascade_delete' => 'Cascade delete',
        'fuseki_cascade_delete.description' => 'Delete related triples when parent is deleted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Settings
    |--------------------------------------------------------------------------
    */
    'features' => [
        'feature_accessions' => 'Enable accessions',
        'feature_accessions.description' => 'Show accession management in navigation',
        'feature_ingest' => 'Enable ingest',
        'feature_ingest.description' => 'Show data ingest functionality',
        'feature_preservation' => 'Enable preservation',
        'feature_preservation.description' => 'Enable preservation planning features',
        'feature_ai' => 'Enable AI features',
        'feature_ai.description' => 'Enable AI-powered features',
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    */
    'encryption' => [
        'encryption_enabled' => 'Enable encryption',
        'encryption_enabled.description' => 'Enable file encryption at rest',
        'encryption_encrypt_derivatives' => 'Encrypt derivatives',
        'encryption_encrypt_derivatives.description' => 'Also encrypt derivative files',
    ],

    /*
    |--------------------------------------------------------------------------
    | General / Theme Settings
    |--------------------------------------------------------------------------
    */
    'general' => [
        'site_title' => 'Site title',
        'site_title.description' => 'The name of your repository',
        'site_description' => 'Site description',
        'site_description.description' => 'Brief description shown in header',
        'openric_theme_enabled' => 'Enable OpenRiC theme',
        'openric_show_branding' => 'Show branding',
        'openric_primary_color' => 'Primary color',
        'openric_secondary_color' => 'Secondary color',
        'openric_card_header_bg' => 'Card header background',
        'openric_card_header_text' => 'Card header text',
        'openric_button_bg' => 'Button background',
        'openric_button_text' => 'Button text',
        'openric_link_color' => 'Link color',
        'openric_sidebar_bg' => 'Sidebar background',
        'openric_sidebar_text' => 'Sidebar text',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    */
    'email' => [
        'smtp_host' => 'SMTP host',
        'smtp_port' => 'SMTP port',
        'smtp_username' => 'SMTP username',
        'smtp_password' => 'SMTP password',
        'smtp_encryption' => 'SMTP encryption',
        'mail_from_address' => 'From email address',
        'mail_from_name' => 'From name',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ingest Settings
    |--------------------------------------------------------------------------
    */
    'ingest' => [
        'ingest_ner' => 'Named entity recognition',
        'ingest_ocr' => 'OCR on ingest',
        'ingest_virus_scan' => 'Virus scanning',
        'ingest_summarize' => 'AI summaries',
        'ingest_spellcheck' => 'Spell checking',
        'ingest_translate' => 'Translation',
        'ingest_format_id' => 'Format identification',
        'ingest_face_detect' => 'Face detection',
        'ingest_create_records' => 'Create records',
        'ingest_generate_sip' => 'Generate SIP',
        'ingest_generate_aip' => 'Generate AIP',
        'ingest_generate_dip' => 'Generate DIP',
        'ingest_thumbnails' => 'Generate thumbnails',
        'ingest_reference' => 'Reference copies',
        'ingest_default_sector' => 'Default sector',
        'ingest_default_standard' => 'Default standard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrity Settings
    |--------------------------------------------------------------------------
    */
    'integrity' => [
        'integrity_enabled' => 'Enable integrity checking',
        'integrity_auto_baseline' => 'Auto-create baselines',
        'integrity_notify_on_failure' => 'Notify on failure',
        'integrity_notify_on_mismatch' => 'Notify on mismatch',
        'integrity_default_algorithm' => 'Default algorithm',
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice & AI Settings
    |--------------------------------------------------------------------------
    */
    'voice_ai' => [
        'voice_enabled' => 'Enable voice AI',
        'voice_continuous_listening' => 'Continuous listening',
        'voice_show_floating_btn' => 'Show floating button',
        'voice_hover_read_enabled' => 'Hover-to-read',
        'voice_audit_ai_calls' => 'Audit AI calls',
        'voice_llm_provider' => 'LLM provider',
        'voice_cloud_model' => 'Cloud model',
        'voice_ollama_url' => 'Ollama URL',
        'voice_ollama_model' => 'Ollama model',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation & Common
    |--------------------------------------------------------------------------
    */
    'nav' => [
        'home' => 'Home',
        'records' => 'Records',
        'agents' => 'Agents',
        'places' => 'Places',
        'graph' => 'Graph Explorer',
        'settings' => 'Settings',
        'search' => 'Search',
    ],

    /*
    |--------------------------------------------------------------------------
    | Quick Actions
    |--------------------------------------------------------------------------
    */
    'quick_actions' => [
        'title' => 'Quick Actions',
        'browse_records' => 'Browse Records',
        'manage_agents' => 'Manage Agents',
        'manage_places' => 'Manage Places',
        'graph_explorer' => 'Graph Explorer',
        'settings' => 'Settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Actions
    |--------------------------------------------------------------------------
    */
    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'create' => 'Create',
        'view' => 'View',
        'search' => 'Search',
        'filter' => 'Filter',
        'export' => 'Export',
        'import' => 'Import',
    ],

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'saved' => 'Settings saved successfully.',
        'deleted' => 'Item deleted successfully.',
        'error' => 'An error occurred.',
        'confirm_delete' => 'Are you sure you want to delete this item?',
    ],
];