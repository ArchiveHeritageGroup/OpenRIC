<?php

declare(strict_types=1);

namespace OpenRiC\Theme\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use OpenRiC\Core\Contracts\SettingsServiceInterface;
use OpenRiC\Theme\Contracts\ThemeServiceInterface;

/**
 * Theme service — adapted from Heratio ThemeService (130 lines).
 *
 * Collects all data needed by layout templates: user auth state, site settings,
 * navigation structure, menu data, version info, plugin detection, and display preferences.
 */
class ThemeService implements ThemeServiceInterface
{
    public function __construct(
        private readonly SettingsServiceInterface $settings,
    ) {}

    /**
     * Get all data needed by layout templates.
     *
     * Composed onto all theme::layouts.* views via the service provider.
     * Heratio passes this as $themeData to master.blade.php and all child views.
     *
     * @return array<string, mixed>
     */
    public function getLayoutData(): array
    {
        $user = Auth::user();
        $isAuthenticated = Auth::check();
        $isAdmin = $user !== null && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
        $isEditor = $user !== null && method_exists($user, 'isEditor') ? $user->isEditor() : false;

        return [
            // App metadata
            'appName' => config('app.name', 'OpenRiC'),
            'appVersion' => $this->getVersion(),
            'locale' => app()->getLocale(),
            'csrfToken' => csrf_token(),

            // User state
            'user' => $user,
            'isAuthenticated' => $isAuthenticated,
            'isAdmin' => $isAdmin,
            'isEditor' => $isEditor,
            'userName' => $user->display_name ?? $user->username ?? null,
            'userEmail' => $user->email ?? null,

            // Site settings — from Heratio ThemeService pattern
            'siteTitle' => $this->settings->getString('general', 'site_title', 'OpenRiC'),
            'siteDescription' => $this->settings->getString('general', 'site_description', 'Records in Contexts'),
            'repositoryName' => $this->settings->getString('general', 'repository_name', ''),
            'repositoryCode' => $this->settings->getString('general', 'repository_code', ''),
            'resultsPerPage' => $this->settings->getInt('general', 'results_per_page', 25),

            // Theme settings
            'primaryColor' => $this->settings->getString('theme', 'primary_color', '#1a5276'),
            'headerBackgroundColor' => $this->settings->getString('theme', 'header_bg', '#212529'),
            'sidebarCollapsed' => $this->settings->getBool('theme', 'sidebar_collapsed', false),
            'displayMode' => $this->settings->getString('theme', 'display_mode', 'list'),
            'logoPath' => $this->settings->getString('theme', 'logo_path', ''),
            'footerText' => $this->settings->getString('theme', 'footer_text', '© OpenRiC — Records in Contexts'),
            'showBranding' => true,

            // UI toggles (from Heratio)
            'toggleLogo' => $this->settings->getBool('theme', 'toggle_logo', true),
            'toggleDescription' => $this->settings->getBool('theme', 'toggle_description', true),
            'toggleIoSlider' => $this->settings->getBool('theme', 'toggle_io_slider', true),
            'toggleLanguageMenu' => $this->settings->getBool('theme', 'toggle_language_menu', true),
            'toggleCopyrightFilter' => $this->settings->getBool('theme', 'toggle_copyright_filter', true),
            'toggleMaterialFilter' => $this->settings->getBool('theme', 'toggle_material_filter', true),

            // View mode
            'currentViewMode' => $this->getCurrentViewMode(),

            // Navigation
            'navigationItems' => $this->getNavigationItems(),

            // Badge counts (for menus) — only for authenticated users
            'pendingTaskCount' => $isAuthenticated ? $this->getPendingTaskCount($user->id) : 0,
            'pendingAccessRequestCount' => $isAdmin ? $this->getPendingAccessRequestCount() : 0,
        ];
    }

    /**
     * Get hierarchical navigation items for sidebar and header menus.
     *
     * Adapted from Heratio main-menu.blade.php menu structure.
     * Each group has a label, icon, and items array.
     *
     * @return array<string, array>
     */
    public function getNavigationItems(): array
    {
        return [
            'records' => [
                'label' => 'Records',
                'icon' => 'bi-archive',
                'items' => [
                    ['label' => 'Record Sets', 'route' => 'record-sets.index', 'icon' => 'bi-collection'],
                    ['label' => 'Records', 'route' => 'records.index', 'icon' => 'bi-file-earmark-text'],
                    ['label' => 'Record Parts', 'route' => 'record-parts.index', 'icon' => 'bi-files'],
                    ['label' => 'Finding Aids', 'route' => 'finding-aids.index', 'icon' => 'bi-book'],
                    ['label' => 'Hierarchy', 'route' => 'hierarchy.index', 'icon' => 'bi-diagram-3'],
                ],
            ],
            'agents' => [
                'label' => 'Agents',
                'icon' => 'bi-people',
                'items' => [
                    ['label' => 'Persons', 'route' => 'persons.index', 'icon' => 'bi-person'],
                    ['label' => 'Corporate Bodies', 'route' => 'corporate-bodies.index', 'icon' => 'bi-building'],
                    ['label' => 'Families', 'route' => 'families.index', 'icon' => 'bi-people-fill'],
                ],
            ],
            'context' => [
                'label' => 'Context',
                'icon' => 'bi-diagram-3',
                'items' => [
                    ['label' => 'Activities', 'route' => 'activities.index', 'icon' => 'bi-activity'],
                    ['label' => 'Places', 'route' => 'places.index', 'icon' => 'bi-geo-alt'],
                    ['label' => 'Dates', 'route' => 'dates.index', 'icon' => 'bi-calendar-event'],
                    ['label' => 'Mandates', 'route' => 'mandates.index', 'icon' => 'bi-file-earmark-ruled'],
                    ['label' => 'Functions', 'route' => 'functions.index', 'icon' => 'bi-gear'],
                ],
            ],
            'physical_digital' => [
                'label' => 'Physical / Digital',
                'icon' => 'bi-hdd',
                'items' => [
                    ['label' => 'Instantiations', 'route' => 'instantiations.index', 'icon' => 'bi-box'],
                    ['label' => 'Condition Assessments', 'route' => 'conditions.index', 'icon' => 'bi-clipboard-pulse'],
                ],
            ],
            'discover' => [
                'label' => 'Discover',
                'icon' => 'bi-search',
                'items' => [
                    ['label' => 'Search', 'route' => 'search.index', 'icon' => 'bi-search'],
                    ['label' => 'Browse', 'route' => 'browse.index', 'icon' => 'bi-list-ul'],
                    ['label' => 'Graph Explorer', 'route' => 'graph.explore', 'icon' => 'bi-share'],
                    ['label' => 'SPARQL', 'route' => 'sparql.index', 'icon' => 'bi-terminal'],
                ],
            ],
            'export' => [
                'label' => 'Export',
                'icon' => 'bi-box-arrow-up-right',
                'items' => [
                    ['label' => 'Export Formats', 'route' => 'export.index', 'icon' => 'bi-file-earmark-arrow-down'],
                    ['label' => 'OAI-PMH', 'route' => 'oai.index', 'icon' => 'bi-cloud-download'],
                ],
            ],
            'workflow' => [
                'label' => 'Workflow',
                'icon' => 'bi-kanban',
                'requiresAuth' => true,
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'workflow.dashboard', 'icon' => 'bi-speedometer2'],
                    ['label' => 'My Tasks', 'route' => 'workflow.my-tasks', 'icon' => 'bi-check2-square'],
                    ['label' => 'Pool Tasks', 'route' => 'workflow.pool', 'icon' => 'bi-inbox'],
                ],
            ],
            'admin' => [
                'label' => 'Admin',
                'icon' => 'bi-shield-lock',
                'requiresAdmin' => true,
                'items' => [
                    ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'bi-person-gear'],
                    ['label' => 'Roles & Groups', 'route' => 'admin.roles.index', 'icon' => 'bi-person-badge'],
                    ['label' => 'ACL Groups', 'route' => 'admin.acl.groups', 'icon' => 'bi-shield-shaded'],
                    ['label' => 'Security Clearance', 'route' => 'admin.security-clearance.index', 'icon' => 'bi-shield-check'],
                    ['label' => 'Audit Trail', 'route' => 'audit.browse', 'icon' => 'bi-journal-text'],
                    ['label' => 'Settings', 'route' => 'admin.settings.index', 'icon' => 'bi-sliders'],
                    ['label' => 'Dropdowns', 'route' => 'admin.dropdowns.index', 'icon' => 'bi-list-check'],
                    ['label' => 'Workflows', 'route' => 'admin.workflows.index', 'icon' => 'bi-kanban'],
                    ['label' => 'Publish Gates', 'route' => 'admin.publish-gates.index', 'icon' => 'bi-funnel'],
                    ['label' => 'Triplestore Status', 'route' => 'admin.triplestore.status', 'icon' => 'bi-database-check'],
                    ['label' => 'RiC Sync', 'route' => 'admin.ric-sync.dashboard', 'icon' => 'bi-arrow-repeat'],
                    ['label' => 'Standards Mapping', 'route' => 'admin.mappings.index', 'icon' => 'bi-arrow-left-right'],
                ],
            ],
        ];
    }

    /**
     * Get the current view mode from session.
     */
    public function getCurrentViewMode(): string
    {
        return Session::get('openric_view_mode', 'ric');
    }

    /**
     * Get application version from version.json.
     */
    private function getVersion(): string
    {
        $versionFile = base_path('version.json');
        if (file_exists($versionFile)) {
            $data = json_decode(file_get_contents($versionFile), true);
            return $data['version'] ?? '0.1.0';
        }

        return config('openric.version', '0.1.0');
    }

    /**
     * Get languages enabled in admin settings for the nav dropdown.
     *
     * Reads from settings table (group = i18n_languages). Falls back to
     * scanning all lang/ directories if nothing has been configured yet.
     */
    public function getEnabledLanguages(): array
    {
        try {
            $rows = DB::table('settings')
                ->where('group', 'i18n_languages')
                ->pluck('value', 'key')
                ->map(fn ($v) => json_decode($v, true))
                ->filter(fn ($v) => !empty($v['enabled']))
                ->sortKeys()
                ->toArray();

            if (!empty($rows)) {
                return $rows;
            }
        } catch (\Exception) {
            // Table may not exist yet during initial setup
        }

        // Fallback: show all available lang/ directories
        $dirs = array_filter(
            array_map('basename', glob(lang_path('*'), GLOB_ONLYDIR) ?: []),
            fn (string $d) => preg_match('/^[a-z]{2}(_[A-Z]{2})?(@\w+)?$/', $d)
        );
        sort($dirs);

        $result = [];
        foreach ($dirs as $code) {
            $result[$code] = [
                'name'      => ucfirst(\Locale::getDisplayLanguage($code, $code)),
                'enabled'   => true,
                'direction' => in_array($code, ['ar', 'fa', 'he', 'ur'], true) ? 'rtl' : 'ltr',
            ];
        }

        return $result;
    }

    /**
     * Count pending workflow tasks for a user.
     */
    private function getPendingTaskCount(int $userId): int
    {
        try {
            return DB::table('workflow_tasks')
                ->where('assigned_to', $userId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();
        } catch (\Exception) {
            return 0;
        }
    }

    /**
     * Count pending security access requests (admin only).
     */
    private function getPendingAccessRequestCount(): int
    {
        try {
            return DB::table('security_access_requests')
                ->where('status', 'pending')
                ->count();
        } catch (\Exception) {
            return 0;
        }
    }
}
