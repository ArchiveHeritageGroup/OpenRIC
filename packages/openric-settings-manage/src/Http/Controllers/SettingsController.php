<?php

declare(strict_types=1);

namespace OpenRiC\SettingsManage\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenRiC\SettingsManage\Contracts\SettingsManageServiceInterface;

/**
 * Settings controller — adapted from Heratio ahg-settings SettingsController (2,086 lines).
 *
 * Full admin UI for OpenRiC settings management: global, scoped, grouped, email,
 * themes, system info, services, error log, cron jobs, and 40+ dedicated setting pages.
 */
class SettingsController extends Controller
{
    private SettingsManageServiceInterface $service;

    private array $scopeLabels = [
        '_global'            => 'Global settings',
        'default_template'   => 'Default templates',
        'ui_label'           => 'User interface labels',
        'element_visibility' => 'Default page elements',
        'i18n_languages'     => 'Languages',
        'oai'                => 'OAI repository',
        'federation'         => 'Federation',
        'access_statement'   => 'Access statement',
    ];

    private array $scopeIcons = [
        '_global'            => 'fa-cogs',
        'default_template'   => 'fa-file-alt',
        'ui_label'           => 'fa-tags',
        'element_visibility' => 'fa-eye',
        'i18n_languages'     => 'fa-language',
        'oai'                => 'fa-cloud',
        'federation'         => 'fa-network-wired',
        'access_statement'   => 'fa-lock',
    ];

    private array $scopeDescriptions = [
        '_global'            => 'Site title, base URL, search, identifiers, digital object derivatives, and other global options.',
        'default_template'   => 'Default display templates for information objects, actors, and repositories.',
        'ui_label'           => 'Customize labels used throughout the user interface.',
        'element_visibility' => 'Control visibility of page elements for descriptive standards.',
        'i18n_languages'     => 'Enabled languages for internationalization.',
        'oai'                => 'OAI-PMH repository settings for metadata harvesting.',
        'federation'         => 'Federated search and repository federation settings.',
        'access_statement'   => 'Access statement configuration.',
    ];

    private array $menuNodes = [
        ['action' => 'index', 'label' => 'Settings Home', 'icon' => 'fa-home'],
        ['action' => 'clipboard', 'label' => 'Clipboard', 'icon' => 'fa-paperclip'],
        ['action' => 'csv-validator', 'label' => 'CSV Validator', 'icon' => 'fa-check-circle'],
        ['action' => 'visible-elements', 'label' => 'Default page elements', 'icon' => 'fa-eye'],
        ['action' => 'default-template', 'label' => 'Default template', 'icon' => 'fa-file-alt'],
        ['action' => 'diacritics', 'label' => 'Diacritics', 'icon' => 'fa-font'],
        ['action' => 'digital-objects', 'label' => 'Digital object derivatives', 'icon' => 'fa-photo-video'],
        ['action' => 'dip-upload', 'label' => 'DIP upload', 'icon' => 'fa-upload'],
        ['action' => 'email', 'label' => 'Email', 'icon' => 'fa-envelope'],
        ['action' => 'finding-aid', 'label' => 'Finding Aid', 'icon' => 'fa-book'],
        ['action' => 'global', 'label' => 'Global', 'icon' => 'fa-globe'],
        ['action' => 'languages', 'label' => 'I18n languages', 'icon' => 'fa-language'],
        ['action' => 'identifier', 'label' => 'Identifiers', 'icon' => 'fa-fingerprint'],
        ['action' => 'inventory', 'label' => 'Inventory', 'icon' => 'fa-clipboard-list'],
        ['action' => 'markdown', 'label' => 'Markdown', 'icon' => 'fa-pen-fancy'],
        ['action' => 'oai', 'label' => 'OAI repository', 'icon' => 'fa-cloud'],
        ['action' => 'permissions', 'label' => 'Permissions', 'icon' => 'fa-user-lock'],
        ['action' => 'privacy-notification', 'label' => 'Privacy Notification', 'icon' => 'fa-user-shield'],
        ['action' => 'security', 'label' => 'Security', 'icon' => 'fa-shield-alt'],
        ['action' => 'site-information', 'label' => 'Site information', 'icon' => 'fa-info-circle'],
        ['action' => 'treeview', 'label' => 'Treeview', 'icon' => 'fa-sitemap'],
        ['action' => 'uploads', 'label' => 'Uploads', 'icon' => 'fa-cloud-upload-alt'],
        ['action' => 'interface-labels', 'label' => 'User interface labels', 'icon' => 'fa-tags'],
        ['action' => 'ai-services', 'label' => 'AI services', 'icon' => 'fa-brain'],
        ['action' => 'header-customizations', 'label' => 'Header customizations', 'icon' => 'fa-heading'],
        ['action' => 'storage-service', 'label' => 'Storage service', 'icon' => 'fa-hdd'],
        ['action' => 'web-analytics', 'label' => 'Web analytics', 'icon' => 'fa-chart-bar'],
        ['action' => 'ldap', 'label' => 'LDAP authentication', 'icon' => 'fa-network-wired'],
        ['action' => 'levels', 'label' => 'Levels of description', 'icon' => 'fa-layer-group'],
        ['action' => 'paths', 'label' => 'Paths', 'icon' => 'fa-folder-open'],
        ['action' => 'preservation', 'label' => 'Preservation', 'icon' => 'fa-cloud-upload-alt'],
        ['action' => 'webhooks', 'label' => 'Webhooks', 'icon' => 'fa-broadcast-tower'],
        ['action' => 'tts', 'label' => 'Text-to-Speech', 'icon' => 'fa-volume-up'],
        ['action' => 'icip-settings', 'label' => 'ICIP Settings', 'icon' => 'fa-shield-alt'],
        ['action' => 'sector-numbering', 'label' => 'Sector numbering', 'icon' => 'fa-hashtag'],
        ['action' => 'numbering-schemes', 'label' => 'Numbering schemes', 'icon' => 'fa-hashtag'],
        ['action' => 'dam-tools', 'label' => 'DAM tools', 'icon' => 'fa-photo-video'],
        ['action' => 'page-elements', 'label' => 'Page elements', 'icon' => 'fa-th-large'],
        ['action' => 'system-info', 'label' => 'System information', 'icon' => 'fa-server'],
        ['action' => 'services', 'label' => 'Services monitor', 'icon' => 'fa-heartbeat'],
        ['action' => 'themes', 'label' => 'Theme configuration', 'icon' => 'fa-palette'],
        ['action' => 'error-log', 'label' => 'Error log', 'icon' => 'fa-exclamation-triangle'],
    ];

    public function __construct(SettingsManageServiceInterface $service)
    {
        $this->service = $service;
    }

    // ─── Index ──────────────────────────────────────────────────────────

    public function index()
    {
        $scopes = DB::table('setting')
            ->where('editable', 1)
            ->select('scope')
            ->distinct()
            ->pluck('scope')
            ->map(fn ($s) => $s ?? '_global')
            ->unique()
            ->sort()
            ->values();

        $scopeCards = $scopes->map(function ($scope) {
            return (object) [
                'key' => $scope,
                'label' => $this->scopeLabels[$scope] ?? ucfirst(str_replace('_', ' ', $scope)),
                'icon' => $this->scopeIcons[$scope] ?? 'fa-sliders-h',
                'description' => $this->scopeDescriptions[$scope] ?? 'Manage ' . strtolower($this->scopeLabels[$scope] ?? str_replace('_', ' ', $scope)) . '.',
                'count' => DB::table('setting')
                    ->where('editable', 1)
                    ->when($scope === '_global', fn ($q) => $q->whereNull('scope'), fn ($q) => $q->where('scope', $scope))
                    ->count(),
            ];
        });

        $openricGroups = collect();
        if (Schema::hasTable('openric_settings')) {
            $openricGroups = DB::table('openric_settings')
                ->select('setting_group', DB::raw('COUNT(*) as cnt'))
                ->groupBy('setting_group')
                ->orderBy('setting_group')
                ->get()
                ->map(fn ($row) => (object) ['key' => $row->setting_group, 'label' => ucfirst(str_replace('_', ' ', $row->setting_group)), 'count' => $row->cnt]);
        }

        $groupIcons = [
            'accession' => 'fa-archive', 'ai_condition' => 'fa-robot', 'compliance' => 'fa-clipboard-check',
            'data_protection' => 'fa-shield-alt', 'email' => 'fa-envelope', 'encryption' => 'fa-key',
            'faces' => 'fa-user-circle', 'features' => 'fa-star', 'fuseki' => 'fa-project-diagram',
            'general' => 'fa-cogs', 'iiif' => 'fa-images', 'ingest' => 'fa-upload',
            'integrity' => 'fa-check-double', 'jobs' => 'fa-tasks', 'media' => 'fa-photo-video',
            'metadata' => 'fa-database', 'multi_tenant' => 'fa-building', 'photos' => 'fa-camera',
            'portable_export' => 'fa-file-export', 'security' => 'fa-lock',
            'spectrum' => 'fa-clipboard-list', 'voice_ai' => 'fa-microphone',
        ];

        return view('settings-manage::index', compact('scopeCards', 'openricGroups', 'groupIcons'));
    }

    // ─── Generic section handler ────────────────────────────────────────

    public function section(Request $request, string $section)
    {
        $redirectMap = [
            '_global' => 'settings.global',
            'default_template' => 'settings.default-template',
            'element_visibility' => 'settings.visible-elements',
            'i18n_languages' => 'settings.languages',
            'ui_label' => 'settings.interface-labels',
            'oai' => 'settings.oai',
        ];
        if (isset($redirectMap[$section])) {
            return redirect()->route($redirectMap[$section]);
        }

        $culture = app()->getLocale();
        $isGlobal = ($section === '_global');

        $query = DB::table('setting')
            ->leftJoin('setting_i18n', function ($join) use ($culture): void {
                $join->on('setting.id', '=', 'setting_i18n.id')
                    ->where('setting_i18n.culture', '=', $culture);
            })
            ->where('setting.editable', 1);

        if ($isGlobal) {
            $query->whereNull('setting.scope');
        } else {
            $query->where('setting.scope', $section);
        }

        $settings = $query->select('setting.id', 'setting.name', 'setting.scope', 'setting_i18n.value')
            ->orderBy('setting.name')
            ->get();

        $sectionLabel = $this->scopeLabels[$section] ?? ucfirst(str_replace('_', ' ', $section));

        if ($request->isMethod('post')) {
            foreach ($request->input('settings', []) as $id => $value) {
                DB::table('setting_i18n')->updateOrInsert(
                    ['id' => $id, 'culture' => $culture],
                    ['value' => $value]
                );
            }
            return redirect()->route('settings.section', $section)->with('success', $sectionLabel . ' settings saved.');
        }

        return view('settings-manage::section', compact('settings', 'section', 'sectionLabel'));
    }

    // ─── Default Template ───────────────────────────────────────────────

    public function defaultTemplate(Request $request)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu('default-template');

        $templateSettings = DB::table('setting')
            ->leftJoin('setting_i18n', function ($join) use ($culture): void {
                $join->on('setting.id', '=', 'setting_i18n.id')
                    ->where('setting_i18n.culture', '=', $culture);
            })
            ->where('setting.scope', 'default_template')
            ->where('setting.editable', 1)
            ->select('setting.id', 'setting.name', 'setting_i18n.value')
            ->orderBy('setting.name')
            ->get()
            ->keyBy('name');

        if ($request->isMethod('post')) {
            foreach ($request->input('settings', []) as $id => $value) {
                DB::table('setting_i18n')->updateOrInsert(
                    ['id' => $id, 'culture' => $culture],
                    ['value' => $value]
                );
            }
            return redirect()->route('settings.default-template')->with('success', 'Default templates saved.');
        }

        $ioChoices = [
            'isad' => 'ISAD(G), 2nd ed. International Council on Archives',
            'dc' => 'Dublin Core, Version 1.1. Dublin Core Metadata Initiative',
            'mods' => 'MODS, Version 3.3. U.S. Library of Congress',
            'rad' => 'RAD, July 2008 version. Canadian Council of Archives',
            'dacs' => 'DACS, 2nd ed. Society of American Archivists',
        ];
        $actorChoices = ['isaar' => 'ISAAR(CPF), 2nd ed. International Council on Archives'];
        $repoChoices = ['isdiah' => 'ISDIAH, 1st ed. International Council on Archives'];

        return view('settings-manage::default-template', compact('templateSettings', 'ioChoices', 'actorChoices', 'repoChoices', 'menu'));
    }

    // ─── Global ─────────────────────────────────────────────────────────

    public function global(Request $request)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu('global');

        if ($request->isMethod('post')) {
            $this->service->saveGlobalSettings($request->input('settings', []), $culture);
            return redirect()->route('settings.global')->with('success', 'Global settings saved.');
        }

        $settings = $this->service->getGlobalSettings($culture);
        return view('settings-manage::global', compact('settings', 'menu'));
    }

    // ─── Site Information ───────────────────────────────────────────────

    public function siteInformation(Request $request)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu('site-information');

        if ($request->isMethod('post')) {
            foreach (['siteTitle', 'siteDescription', 'siteBaseUrl'] as $name) {
                $this->service->saveSetting($name, null, $request->input($name, ''), $culture);
            }
            return redirect()->route('settings.site-information')->with('success', 'Site information saved.');
        }

        $settings = $this->service->getSiteInformation($culture);
        return view('settings-manage::site-information', compact('settings', 'menu'));
    }

    // ─── Security ───────────────────────────────────────────────────────

    public function security(Request $request)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu('security');

        if ($request->isMethod('post')) {
            foreach (['limit_admin_ip', 'require_ssl_admin', 'require_strong_passwords'] as $name) {
                $this->service->saveSetting($name, null, $request->input($name, ''), $culture);
            }
            return redirect()->route('settings.security')->with('success', 'Security settings saved.');
        }

        $settings = $this->service->getSecuritySettings($culture);
        return view('settings-manage::security', compact('settings', 'menu'));
    }

    // ─── Identifier ─────────────────────────────────────────────────────

    public function identifier(Request $request)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu('identifier');

        if ($request->isMethod('post')) {
            foreach ($request->input('settings', []) as $name => $value) {
                $this->service->saveSetting($name, null, $value ?? '', $culture);
            }
            return redirect()->route('settings.identifier')->with('success', 'Identifier settings saved.');
        }

        $settings = $this->service->getIdentifierSettings($culture);
        return view('settings-manage::identifier', compact('settings', 'menu'));
    }

    // ─── Email ──────────────────────────────────────────────────────────

    public function email(Request $request)
    {
        $menu = $this->buildMenu('email');
        $emailData = $this->service->getEmailSettings();

        if ($request->isMethod('post')) {
            $this->service->saveEmailSettings(
                $request->input('settings', []),
                $request->input('notif_toggles', [])
            );
            return redirect()->route('settings.email')->with('success', 'Email settings saved.');
        }

        return view('settings-manage::email', [
            'menu' => $menu,
            'smtpSettings' => $emailData['smtp'],
            'notificationSettings' => $emailData['notification'],
            'templateSettings' => $emailData['template'],
            'notifToggles' => $emailData['toggles'],
        ]);
    }

    // ─── Treeview ───────────────────────────────────────────────────────

    public function treeview(Request $request)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu('treeview');

        if ($request->isMethod('post')) {
            foreach ($request->input('settings', []) as $name => $value) {
                $this->service->saveSetting($name, null, $value ?? '', $culture);
            }
            return redirect()->route('settings.treeview')->with('success', 'Treeview settings saved.');
        }

        $settings = $this->service->getTreeviewSettings($culture);
        return view('settings-manage::treeview', compact('settings', 'menu'));
    }

    // ─── Digital Objects ────────────────────────────────────────────────

    public function digitalObjects(Request $request)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu('digital-objects');

        if ($request->isMethod('post')) {
            foreach ($request->input('settings', []) as $name => $value) {
                $this->service->saveSetting($name, null, $value ?? '', $culture);
            }
            return redirect()->route('settings.digital-objects')->with('success', 'Digital object settings saved.');
        }

        $settings = $this->service->getDigitalObjectSettings($culture);
        return view('settings-manage::digital-objects', compact('settings', 'menu'));
    }

    // ─── Interface Labels ───────────────────────────────────────────────

    public function interfaceLabels(Request $request)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu('interface-labels');

        if ($request->isMethod('post')) {
            foreach ($request->input('settings', []) as $id => $value) {
                DB::table('setting_i18n')->updateOrInsert(
                    ['id' => $id, 'culture' => $culture],
                    ['value' => $value]
                );
            }
            return redirect()->route('settings.interface-labels')->with('success', 'Interface labels saved.');
        }

        $settings = DB::table('setting')
            ->leftJoin('setting_i18n', function ($join) use ($culture): void {
                $join->on('setting.id', '=', 'setting_i18n.id')
                    ->where('setting_i18n.culture', '=', $culture);
            })
            ->where('setting.scope', 'ui_label')
            ->where('setting.editable', 1)
            ->select('setting.id', 'setting.name', 'setting_i18n.value')
            ->orderBy('setting.name')
            ->get();

        return view('settings-manage::interface-labels', compact('settings', 'menu'));
    }

    // ─── Visible Elements ───────────────────────────────────────────────

    public function visibleElements(Request $request)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu('visible-elements');

        $settings = DB::table('setting')
            ->leftJoin('setting_i18n', function ($join) use ($culture): void {
                $join->on('setting.id', '=', 'setting_i18n.id')
                    ->where('setting_i18n.culture', '=', $culture);
            })
            ->where('setting.scope', 'element_visibility')
            ->where('setting.editable', 1)
            ->select('setting.id', 'setting.name', 'setting_i18n.value')
            ->orderBy('setting.name')
            ->get()
            ->keyBy('name');

        if ($request->isMethod('post')) {
            foreach ($settings as $name => $setting) {
                $value = $request->has("settings.{$setting->id}") ? '1' : '0';
                DB::table('setting_i18n')->updateOrInsert(
                    ['id' => $setting->id, 'culture' => $culture],
                    ['value' => $value]
                );
            }
            return redirect()->route('settings.visible-elements')->with('success', 'Visible elements saved.');
        }

        $groups = [];
        foreach ($settings as $name => $setting) {
            $parts = explode('_', $name, 2);
            $prefix = $parts[0] ?? 'other';
            $groups[$prefix][] = $setting;
        }

        return view('settings-manage::visible-elements', compact('settings', 'groups', 'menu'));
    }

    // ─── Languages ──────────────────────────────────────────────────────

    public function languages(Request $request)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu('languages');

        $languages = DB::table('setting')
            ->leftJoin('setting_i18n', function ($join) use ($culture): void {
                $join->on('setting.id', '=', 'setting_i18n.id')
                    ->where('setting_i18n.culture', '=', $culture);
            })
            ->where('setting.scope', 'i18n_languages')
            ->where('setting.editable', 1)
            ->select('setting.id', 'setting.name', 'setting_i18n.value')
            ->orderBy('setting.name')
            ->get();

        if ($request->isMethod('post') && $request->input('action') === 'add') {
            $code = strtolower(trim($request->input('languageCode', '')));
            if (preg_match('/^[a-z]{2,3}$/', $code)) {
                $exists = DB::table('setting')
                    ->where('scope', 'i18n_languages')
                    ->where('name', $code)
                    ->exists();
                if (!$exists) {
                    $id = DB::table('setting')->insertGetId([
                        'name' => $code,
                        'scope' => 'i18n_languages',
                        'editable' => 1,
                        'deleteable' => 1,
                        'source_culture' => $culture,
                        'serial_number' => 0,
                    ]);
                    DB::table('setting_i18n')->insert(['id' => $id, 'culture' => $culture, 'value' => $code]);
                    return redirect()->route('settings.languages')->with('success', "Language '{$code}' added.");
                }
                return redirect()->route('settings.languages')->with('error', "Language '{$code}' already exists.");
            }
            return redirect()->route('settings.languages')->with('error', 'Invalid language code. Use 2-3 lowercase letters (e.g. en, fr, af).');
        }

        if ($request->isMethod('post') && $request->input('action') === 'delete') {
            $deleteId = (int) $request->input('delete_id');
            $setting = DB::table('setting')->where('id', $deleteId)->where('scope', 'i18n_languages')->first();
            if ($setting && $setting->deleteable) {
                DB::table('setting_i18n')->where('id', $deleteId)->delete();
                DB::table('setting')->where('id', $deleteId)->delete();
                return redirect()->route('settings.languages')->with('success', 'Language removed.');
            }
            return redirect()->route('settings.languages')->with('error', 'This language cannot be deleted.');
        }

        return view('settings-manage::languages', compact('languages', 'menu'));
    }

    // ─── OAI ────────────────────────────────────────────────────────────

    public function oai(Request $request)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu('oai');

        if ($request->isMethod('post')) {
            foreach ($request->input('settings', []) as $name => $value) {
                $setting = DB::table('setting')->where('name', $name)->where('scope', 'oai')->first();
                if ($setting) {
                    DB::table('setting_i18n')->updateOrInsert(
                        ['id' => $setting->id, 'culture' => $culture],
                        ['value' => $value ?? '']
                    );
                }
            }
            return redirect()->route('settings.oai')->with('success', 'OAI repository settings saved.');
        }

        $settings = $this->service->getOaiSettings($culture);
        return view('settings-manage::oai', compact('settings', 'menu'));
    }

    // ─── System Info ────────────────────────────────────────────────────

    public function systemInfo()
    {
        $menu = $this->buildMenu('system-info');
        $info = $this->service->getSystemInfo();
        return view('settings-manage::system-info', compact('info', 'menu'));
    }

    // ─── Services Monitor ───────────────────────────────────────────────

    public function services()
    {
        $menu = $this->buildMenu('services');
        $serviceChecks = [];

        // Check PostgreSQL
        try {
            DB::select('SELECT 1');
            $serviceChecks['PostgreSQL'] = ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Exception $e) {
            $serviceChecks['PostgreSQL'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Check Elasticsearch
        try {
            $esHost = config('services.elasticsearch.host', 'localhost:9200');
            $ch = curl_init("http://{$esHost}/_cluster/health");
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode === 200) {
                $health = json_decode($response, true);
                $serviceChecks['Elasticsearch'] = ['status' => $health['status'] ?? 'ok', 'message' => 'Cluster: ' . ($health['cluster_name'] ?? 'unknown') . ' (' . ($health['status'] ?? 'unknown') . ')'];
            } else {
                $serviceChecks['Elasticsearch'] = ['status' => 'warning', 'message' => "HTTP {$httpCode}"];
            }
        } catch (\Exception) {
            $serviceChecks['Elasticsearch'] = ['status' => 'error', 'message' => 'Not available'];
        }

        // Check Fuseki
        try {
            $fusekiHost = config('services.fuseki.host', 'localhost:3030');
            $ch = curl_init("http://{$fusekiHost}/$/ping");
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $serviceChecks['Apache Jena Fuseki'] = [
                'status' => $httpCode === 200 ? 'ok' : 'warning',
                'message' => $httpCode === 200 ? 'Running' : "HTTP {$httpCode}",
            ];
        } catch (\Exception) {
            $serviceChecks['Apache Jena Fuseki'] = ['status' => 'error', 'message' => 'Not available'];
        }

        // Check Qdrant
        try {
            $qdrantHost = config('services.qdrant.host', 'localhost:6333');
            $ch = curl_init("http://{$qdrantHost}/");
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $serviceChecks['Qdrant'] = [
                'status' => $httpCode === 200 ? 'ok' : 'warning',
                'message' => $httpCode === 200 ? 'Running' : "HTTP {$httpCode}",
            ];
        } catch (\Exception) {
            $serviceChecks['Qdrant'] = ['status' => 'error', 'message' => 'Not available'];
        }

        // Check Redis
        try {
            $redis = app('redis');
            $redis->ping();
            $serviceChecks['Redis'] = ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Exception) {
            $serviceChecks['Redis'] = ['status' => 'error', 'message' => 'Not available'];
        }

        // Check disk space
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        $usedPct = round((1 - $free / $total) * 100, 1);
        $serviceChecks['Disk space'] = [
            'status' => $usedPct > 90 ? 'error' : ($usedPct > 75 ? 'warning' : 'ok'),
            'message' => round($free / 1073741824, 1) . ' GB free (' . $usedPct . '% used)',
        ];

        return view('settings-manage::services', compact('serviceChecks', 'menu'));
    }

    // ─── Themes ─────────────────────────────────────────────────────────

    public function themes(Request $request)
    {
        $themeKeys = [
            'openric_theme_enabled', 'openric_primary_color', 'openric_secondary_color',
            'openric_body_bg', 'openric_body_text',
            'openric_footer_bg', 'openric_footer_text_color', 'openric_footer_copyright',
            'openric_footer_disclaimer', 'openric_footer_system_name',
            'openric_footer_org_name', 'openric_footer_org_url',
            'openric_header_bg', 'openric_header_text',
            'openric_card_header_bg', 'openric_card_header_text',
            'openric_button_bg', 'openric_button_text',
            'openric_link_color', 'openric_sidebar_bg', 'openric_sidebar_text',
            'openric_logo_path', 'openric_show_branding', 'openric_custom_css',
            'openric_success_color', 'openric_danger_color', 'openric_warning_color',
            'openric_info_color', 'openric_light_color', 'openric_dark_color',
            'openric_muted_color', 'openric_border_color',
        ];

        if ($request->isMethod('post')) {
            foreach ($themeKeys as $key) {
                $value = $request->input($key, '');
                DB::table('openric_settings')
                    ->where('setting_key', $key)
                    ->update(['setting_value' => $value]);
            }
            $this->regenerateThemeCss();
            return redirect()->route('settings.themes')->with('success', 'Theme settings saved.');
        }

        $settings = DB::table('openric_settings')
            ->whereIn('setting_key', $themeKeys)
            ->pluck('setting_value', 'setting_key');

        return view('settings-manage::themes', ['settings' => $settings]);
    }

    public function dynamicCss()
    {
        $rows = DB::table('openric_settings')
            ->where('setting_group', 'general')
            ->pluck('setting_value', 'setting_key');

        $css = "/* OpenRiC Theme - Dynamic CSS */\n:root {\n";
        $vars = $this->getThemeVars();
        foreach ($vars as $key => [$var, $default]) {
            $css .= "    {$var}: " . ($rows[$key] ?? $default) . ";\n";
        }
        $css .= "}\n";
        $css .= $this->getThemeRules();

        $customCss = $rows['openric_custom_css'] ?? '';
        if (!empty(trim($customCss))) {
            $css .= "\n/* Custom CSS */\n" . $customCss . "\n";
        }

        return response($css, 200)->header('Content-Type', 'text/css');
    }

    private function regenerateThemeCss(): void
    {
        $rows = DB::table('openric_settings')
            ->where('setting_group', 'general')
            ->pluck('setting_value', 'setting_key');

        $css = "/* OpenRiC Theme - Generated CSS */\n:root {\n";
        $vars = $this->getThemeVars();
        foreach ($vars as $key => [$var, $default]) {
            $css .= "    {$var}: " . ($rows[$key] ?? $default) . ";\n";
        }
        $css .= "}\n";
        $css .= $this->getThemeRules();

        $dynamicPath = public_path('css/openric-theme-dynamic.css');
        $dir = dirname($dynamicPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dynamicPath, $css);
    }

    private function getThemeVars(): array
    {
        return [
            'openric_primary_color'    => ['--openric-primary', '#005837'],
            'openric_secondary_color'  => ['--openric-secondary', '#37A07F'],
            'openric_body_bg'          => ['--openric-background-light', '#ffffff'],
            'openric_body_text'        => ['--openric-body-text', '#212529'],
            'openric_footer_bg'        => ['--openric-footer-bg', '#005837'],
            'openric_footer_text_color' => ['--openric-footer-text', '#ffffff'],
            'openric_header_bg'        => ['--openric-header-bg', '#212529'],
            'openric_header_text'      => ['--openric-header-text', '#ffffff'],
            'openric_card_header_bg'   => ['--openric-card-header-bg', '#005837'],
            'openric_card_header_text' => ['--openric-card-header-text', '#ffffff'],
            'openric_button_bg'        => ['--openric-btn-bg', '#005837'],
            'openric_button_text'      => ['--openric-btn-text', '#ffffff'],
            'openric_link_color'       => ['--openric-link-color', '#005837'],
            'openric_sidebar_bg'       => ['--openric-sidebar-bg', '#f8f9fa'],
            'openric_sidebar_text'     => ['--openric-sidebar-text', '#333333'],
            'openric_success_color'    => ['--openric-success', '#28a745'],
            'openric_danger_color'     => ['--openric-danger', '#dc3545'],
            'openric_warning_color'    => ['--openric-warning', '#ffc107'],
            'openric_info_color'       => ['--openric-info', '#17a2b8'],
            'openric_light_color'      => ['--openric-light', '#f8f9fa'],
            'openric_dark_color'       => ['--openric-dark', '#343a40'],
            'openric_muted_color'      => ['--openric-muted', '#6c757d'],
            'openric_border_color'     => ['--openric-border', '#dee2e6'],
        ];
    }

    private function getThemeRules(): string
    {
        return ".card-header { background-color: var(--openric-card-header-bg) !important; color: var(--openric-card-header-text) !important; }\n"
            . ".card-header * { color: var(--openric-card-header-text) !important; }\n"
            . ".btn-primary { background-color: var(--openric-btn-bg) !important; border-color: var(--openric-btn-bg) !important; color: var(--openric-btn-text) !important; }\n"
            . ".btn-primary:hover, .btn-primary:focus { filter: brightness(0.9); }\n"
            . "a:not(.btn):not(.nav-link):not(.dropdown-item) { color: var(--openric-link-color); }\n"
            . ".sidebar, #sidebar-content { background-color: var(--openric-sidebar-bg) !important; color: var(--openric-sidebar-text) !important; }\n"
            . "body { background-color: var(--openric-background-light) !important; color: var(--openric-body-text) !important; }\n"
            . "#top-bar { background-color: var(--openric-header-bg) !important; }\n"
            . "#top-bar, #top-bar .navbar-brand, #top-bar .nav-link { color: var(--openric-header-text) !important; }\n";
    }

    // ─── OpenRiC Group Section (catch-all for grouped settings) ─────────

    public function openricSection(Request $request, string $group)
    {
        $checkboxFields = $this->getCheckboxFields();
        $selectFields = $this->getSelectFields();
        $colorFields = $this->getColorFields();
        $passwordFields = ['fuseki_password', 'voice_anthropic_api_key', 'ai_condition_api_key'];
        $textareaFields = ['openric_custom_css'];

        if ($request->isMethod('post')) {
            $postedSettings = $request->input('settings', []);
            $allKeys = DB::table('openric_settings')
                ->where('setting_group', $group)
                ->pluck('setting_key')
                ->toArray();
            foreach ($allKeys as $key) {
                if (in_array($key, $checkboxFields)) {
                    $value = isset($postedSettings[$key]) ? '1' : '0';
                } else {
                    $value = $postedSettings[$key] ?? '';
                }
                DB::table('openric_settings')
                    ->where('setting_key', $key)
                    ->update(['setting_value' => $value]);
            }
            if ($group === 'general') {
                $this->regenerateThemeCss();
            }
            return redirect()->route('settings.openric', $group)->with('success', ucfirst(str_replace('_', ' ', $group)) . ' settings saved.');
        }

        $settings = DB::table('openric_settings')
            ->where('setting_group', $group)
            ->orderBy('setting_key')
            ->select('id', 'setting_key', 'setting_value', 'setting_group', 'description', 'setting_type')
            ->get();

        $groupLabels = [
            'general' => 'Theme Configuration', 'email' => 'Email', 'metadata' => 'Metadata Extraction',
            'media' => 'Media Player', 'jobs' => 'Background Jobs', 'spectrum' => 'Spectrum / Collections',
            'photos' => 'Condition Photos', 'data_protection' => 'Data Protection', 'iiif' => 'IIIF Viewer',
            'faces' => 'Face Detection', 'fuseki' => 'Fuseki / RIC', 'ingest' => 'Data Ingest',
            'accession' => 'Accession Management', 'encryption' => 'Encryption', 'voice_ai' => 'Voice & AI',
            'integrity' => 'Integrity', 'multi_tenant' => 'Multi-Tenancy', 'portable_export' => 'Portable Export',
            'security' => 'Security', 'features' => 'Features', 'compliance' => 'Compliance',
            'ai_condition' => 'AI Condition',
        ];
        $groupLabel = $groupLabels[$group] ?? ucfirst(str_replace('_', ' ', $group));

        $groupIcons = [
            'accession' => 'fa-inbox', 'ai_condition' => 'fa-robot', 'compliance' => 'fa-clipboard-check',
            'data_protection' => 'fa-user-shield', 'email' => 'fa-envelope', 'encryption' => 'fa-lock',
            'faces' => 'fa-user-circle', 'features' => 'fa-star', 'fuseki' => 'fa-project-diagram',
            'general' => 'fa-palette', 'iiif' => 'fa-images', 'ingest' => 'fa-file-import',
            'integrity' => 'fa-check-double', 'jobs' => 'fa-tasks', 'media' => 'fa-play-circle',
            'metadata' => 'fa-tags', 'multi_tenant' => 'fa-building', 'photos' => 'fa-camera',
            'portable_export' => 'fa-compact-disc', 'security' => 'fa-shield-alt',
            'spectrum' => 'fa-archive', 'voice_ai' => 'fa-microphone',
        ];
        $groupIcon = $groupIcons[$group] ?? 'fa-puzzle-piece';

        return view('settings-manage::openric-section', compact(
            'settings', 'group', 'groupLabel', 'groupIcon',
            'checkboxFields', 'selectFields', 'colorFields', 'passwordFields', 'textareaFields'
        ));
    }

    // ─── Simple setting pages ───────────────────────────────────────────

    public function clipboard(Request $request)
    {
        return $this->simpleSettingPage($request, 'clipboard', [
            'clipboard_save_max_age' => '0',
            'clipboard_send_enabled' => '0',
            'clipboard_send_url' => '',
            'clipboard_send_button_text' => 'Send',
            'clipboard_send_message_html' => 'Sending...',
            'clipboard_send_http_method' => 'POST',
            'clipboard_export_digitalobjects_enabled' => '0',
        ]);
    }

    public function csvValidator(Request $request)
    {
        return $this->simpleSettingPage($request, 'csv-validator', [
            'csv_validator_default_import_behaviour' => '0',
        ]);
    }

    public function diacritics(Request $request)
    {
        return $this->simpleSettingPage($request, 'diacritics', ['diacritics' => '0']);
    }

    public function dipUpload(Request $request)
    {
        return $this->simpleSettingPage($request, 'dip-upload', ['stripExtensions' => '0']);
    }

    public function findingAid(Request $request)
    {
        return $this->simpleSettingPage($request, 'finding-aid', [
            'finding_aids_enabled' => '1',
            'finding_aid_format' => 'pdf',
            'finding_aid_model' => 'inventory-summary',
            'public_finding_aid' => '1',
        ]);
    }

    public function inventory(Request $request)
    {
        return $this->simpleSettingPage($request, 'inventory', ['inventory_levels' => '']);
    }

    public function markdown(Request $request)
    {
        return $this->simpleSettingPage($request, 'markdown', ['markdown_enabled' => '1']);
    }

    public function permissions(Request $request)
    {
        return $this->simpleSettingPage($request, 'permissions', []);
    }

    public function privacyNotification(Request $request)
    {
        return $this->simpleSettingPage($request, 'privacy-notification', [
            'privacy_notification_enabled' => '0',
            'privacy_notification' => '',
        ]);
    }

    public function uploads(Request $request)
    {
        return $this->simpleSettingPage($request, 'uploads', [
            'upload_quota' => '-1',
            'enable_repository_quotas' => '1',
            'repository_quota' => '0',
            'explode_multipage_files' => '0',
        ]);
    }

    public function headerCustomizations(Request $request)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu('header-customizations');

        if ($request->isMethod('post')) {
            $this->service->saveSetting('header_background_colour', null, $request->input('settings.header_background_colour', ''), $culture);

            $uploadsDir = public_path('uploads');
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $logoFile = $request->file('logo');
                if ($logoFile->getMimeType() === 'image/png') {
                    $logoFile->move($uploadsDir, 'logo.png');
                }
            }

            if ($request->hasFile('favicon') && $request->file('favicon')->isValid()) {
                $faviconFile = $request->file('favicon');
                $mime = $faviconFile->getMimeType();
                if (in_array($mime, ['image/x-icon', 'image/vnd.microsoft.icon', 'image/ico', 'image/icon'])) {
                    $faviconFile->move($uploadsDir, 'favicon.ico');
                    copy($uploadsDir . DIRECTORY_SEPARATOR . 'favicon.ico', public_path('favicon.ico'));
                }
            }

            return redirect()->route('settings.header-customizations')->with('success', 'Header customizations saved.');
        }

        $settings = [
            'header_background_colour' => $this->service->getSetting('header_background_colour', null, $culture) ?? '',
        ];

        return view('settings-manage::header-customizations', compact('settings', 'menu'));
    }

    public function webAnalytics(Request $request)
    {
        return $this->simpleSettingPage($request, 'web-analytics', [
            'google_analytics_api_key' => '',
            'google_tag_manager_id' => '',
        ]);
    }

    public function aiServices(Request $request)
    {
        return $this->simpleSettingPage($request, 'ai-services', []);
    }

    public function storageService(Request $request)
    {
        return $this->simpleSettingPage($request, 'storage-service', [
            'storage_service_url' => '',
            'storage_service_api_key' => '',
            'storage_service_username' => '',
            'storage_service_type' => '',
            'storage_service_enabled' => '0',
        ]);
    }

    public function ldap(Request $request)
    {
        return $this->simpleSettingPage($request, 'ldap', [
            'ldapHost' => '',
            'ldapPort' => '389',
            'ldapBaseDn' => '',
            'ldapBindAttribute' => 'uid',
        ]);
    }

    public function levels(Request $request)
    {
        return $this->simpleSettingPage($request, 'levels', []);
    }

    public function paths(Request $request)
    {
        return $this->simpleSettingPage($request, 'paths', [
            'bulk' => '', 'bulk_index' => '', 'bulk_optimize_index' => '', 'bulk_rename' => '',
        ]);
    }

    public function preservation(Request $request)
    {
        $menu = $this->buildMenu('preservation');
        return view('settings-manage::preservation', compact('menu'));
    }

    public function webhooks(Request $request)
    {
        $menu = $this->buildMenu('webhooks');
        return view('settings-manage::webhooks', compact('menu'));
    }

    public function tts(Request $request)
    {
        return $this->simpleSettingPage($request, 'tts', []);
    }

    public function icipSettings(Request $request)
    {
        return $this->simpleSettingPage($request, 'icip-settings', [
            'enable_public_notices' => '0', 'enable_staff_notices' => '0',
            'require_acknowledgement_default' => '0', 'require_community_consent' => '0',
            'consultation_period_days' => '30',
        ]);
    }

    public function sectorNumbering(Request $request)
    {
        return $this->simpleSettingPage($request, 'sector-numbering', []);
    }

    public function numberingSchemes(Request $request)
    {
        $menu = $this->buildMenu('numbering-schemes');
        return view('settings-manage::numbering-schemes', compact('menu'));
    }

    public function damTools(Request $request)
    {
        return $this->simpleSettingPage($request, 'dam-tools', []);
    }

    public function pageElements(Request $request)
    {
        return $this->simpleSettingPage($request, 'page-elements', []);
    }

    // ─── Error Log ──────────────────────────────────────────────────────

    public function errorLog(Request $request)
    {
        if ($request->isMethod('post')) {
            $authUser = \Illuminate\Support\Facades\Auth::id();

            if ($request->filled('resolve_id')) {
                DB::table('openric_error_log')
                    ->where('id', $request->input('resolve_id'))
                    ->update(['resolved_at' => now(), 'resolved_by' => $authUser]);
                return redirect()->route('settings.error-log')->with('success', 'Error resolved.');
            }

            if ($request->filled('reopen_id')) {
                DB::table('openric_error_log')
                    ->where('id', $request->input('reopen_id'))
                    ->update(['resolved_at' => null, 'resolved_by' => null]);
                return redirect()->route('settings.error-log')->with('success', 'Error reopened.');
            }

            if ($request->has('mark_read')) {
                DB::table('openric_error_log')->where('is_read', 0)->update(['is_read' => 1]);
                return redirect()->route('settings.error-log')->with('success', 'All errors marked as read.');
            }

            if ($request->has('resolve_all')) {
                DB::table('openric_error_log')
                    ->whereNull('resolved_at')
                    ->update(['resolved_at' => now(), 'resolved_by' => $authUser]);
                return redirect()->route('settings.error-log')->with('success', 'All open errors resolved.');
            }

            if ($request->has('clear_old')) {
                $days = max(1, (int) $request->input('clear_days', 30));
                DB::table('openric_error_log')
                    ->where('created_at', '<', now()->subDays($days))
                    ->delete();
                return redirect()->route('settings.error-log')->with('success', "Errors older than {$days} days cleared.");
            }

            if ($request->filled('delete_id')) {
                DB::table('openric_error_log')->where('id', $request->input('delete_id'))->delete();
                return redirect()->route('settings.error-log')->with('success', 'Error deleted.');
            }

            return redirect()->route('settings.error-log');
        }

        $statusFilter = $request->get('status', '');
        $levelFilter = $request->get('level', '');
        $searchFilter = $request->get('search', '');
        $page = max(1, (int) $request->get('page', 1));
        $limit = 25;

        $query = DB::table('openric_error_log');

        if ($statusFilter === 'open') {
            $query->whereNull('resolved_at');
        } elseif ($statusFilter === 'resolved') {
            $query->whereNotNull('resolved_at');
        }

        if ($levelFilter) {
            $query->where('level', $levelFilter);
        }

        if ($searchFilter) {
            $like = '%' . $searchFilter . '%';
            $query->where(function ($q) use ($like): void {
                $q->where('message', 'ILIKE', $like)
                  ->orWhere('url', 'ILIKE', $like)
                  ->orWhere('file', 'ILIKE', $like);
            });
        }

        $total = (clone $query)->count();
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        $entries = $query
            ->orderByDesc('created_at')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $openCount = DB::table('openric_error_log')->whereNull('resolved_at')->count();
        $resolvedCount = DB::table('openric_error_log')->whereNotNull('resolved_at')->count();
        $unreadCount = DB::table('openric_error_log')->where('is_read', 0)->count();
        $todayCount = DB::table('openric_error_log')->where('created_at', '>=', now()->startOfDay())->count();

        return view('settings-manage::error-log', [
            'entries' => $entries, 'total' => $total, 'page' => $page, 'limit' => $limit,
            'totalPages' => $totalPages, 'openCount' => $openCount, 'resolvedCount' => $resolvedCount,
            'unreadCount' => $unreadCount, 'todayCount' => $todayCount,
            'filters' => ['status' => $statusFilter, 'level' => $levelFilter, 'search' => $searchFilter],
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    private function buildMenu(string $active): array
    {
        return collect($this->menuNodes)->map(function ($node) use ($active) {
            $node['active'] = ($node['action'] === $active);
            return $node;
        })->toArray();
    }

    private function simpleSettingPage(Request $request, string $page, array $defaults)
    {
        $culture = app()->getLocale();
        $menu = $this->buildMenu($page);
        $settingNames = array_keys($defaults);

        if ($request->isMethod('post')) {
            foreach ($settingNames as $name) {
                $value = $request->input("settings.{$name}", '');
                $this->service->saveSetting($name, null, $value, $culture);
            }
            $label = ucfirst(str_replace('-', ' ', $page));
            return redirect()->route('settings.' . $page)->with('success', "{$label} settings saved.");
        }

        $settings = [];
        foreach ($settingNames as $name) {
            $settings[$name] = $this->service->getSetting($name, null, $culture) ?? ($defaults[$name] ?? '');
        }

        return view('settings-manage::' . $page, compact('settings', 'menu'));
    }

    private function getCheckboxFields(): array
    {
        return [
            'openric_theme_enabled', 'openric_show_branding',
            'fuseki_sync_enabled', 'fuseki_queue_enabled', 'fuseki_sync_on_save',
            'fuseki_sync_on_delete', 'fuseki_cascade_delete',
            'ingest_ner', 'ingest_ocr', 'ingest_virus_scan', 'ingest_summarize',
            'ingest_spellcheck', 'ingest_translate', 'ingest_format_id', 'ingest_face_detect',
            'ingest_create_records', 'ingest_generate_sip', 'ingest_generate_aip', 'ingest_generate_dip',
            'ingest_thumbnails', 'ingest_reference',
            'integrity_enabled', 'integrity_auto_baseline', 'integrity_notify_on_failure',
            'integrity_notify_on_mismatch',
            'encryption_enabled', 'encryption_encrypt_derivatives',
            'ai_condition_auto_scan', 'ai_condition_overlay_enabled',
            'voice_enabled', 'voice_continuous_listening', 'voice_show_floating_btn',
            'voice_hover_read_enabled', 'voice_audit_ai_calls',
            'iiif_enabled', 'iiif_show_navigator', 'iiif_show_rotation', 'iiif_show_fullscreen',
            'iiif_enable_annotations',
        ];
    }

    private function getSelectFields(): array
    {
        return [
            'ingest_default_sector' => ['archive' => 'Archive', 'museum' => 'Museum', 'library' => 'Library', 'gallery' => 'Gallery', 'dam' => 'DAM'],
            'ingest_default_standard' => ['isadg' => 'ISAD(G)', 'dacs' => 'DACS', 'rad' => 'RAD', 'dc' => 'Dublin Core'],
            'integrity_default_algorithm' => ['sha256' => 'SHA-256', 'sha512' => 'SHA-512', 'md5' => 'MD5'],
            'voice_llm_provider' => ['local' => 'Local (Ollama)', 'cloud' => 'Cloud (Anthropic)', 'hybrid' => 'Hybrid'],
            'voice_cloud_model' => ['claude-sonnet-4-20250514' => 'Claude Sonnet 4', 'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5'],
            'iiif_viewer' => ['openseadragon' => 'OpenSeadragon', 'mirador' => 'Mirador', 'leaflet' => 'Leaflet-IIIF'],
        ];
    }

    private function getColorFields(): array
    {
        return [
            'openric_primary_color', 'openric_secondary_color', 'openric_card_header_bg', 'openric_card_header_text',
            'openric_button_bg', 'openric_button_text', 'openric_link_color', 'openric_sidebar_bg', 'openric_sidebar_text',
            'openric_success_color', 'openric_danger_color', 'openric_warning_color', 'openric_info_color',
            'openric_light_color', 'openric_dark_color', 'openric_muted_color', 'openric_border_color',
            'openric_body_bg', 'openric_body_text',
        ];
    }
}
