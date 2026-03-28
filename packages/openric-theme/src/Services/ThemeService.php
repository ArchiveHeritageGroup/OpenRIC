<?php

declare(strict_types=1);

namespace OpenRiC\Theme\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use OpenRiC\Theme\Contracts\ThemeServiceInterface;

class ThemeService implements ThemeServiceInterface
{
    public function getLayoutData(): array
    {
        $user = Auth::user();

        return [
            'appName' => config('app.name', 'OpenRiC'),
            'appVersion' => config('openric.version', '0.1.0'),
            'locale' => app()->getLocale(),
            'isAuthenticated' => Auth::check(),
            'userName' => $user?->name ?? null,
            'userEmail' => $user?->email ?? null,
            'isAdmin' => $user !== null && method_exists($user, 'isAdmin') ? $user->isAdmin() : false,
        ];
    }

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
                ],
            ],
            'search' => [
                'label' => 'Search',
                'icon' => 'bi-search',
                'items' => [
                    ['label' => 'Search', 'route' => 'search.index', 'icon' => 'bi-search'],
                ],
            ],
            'admin' => [
                'label' => 'Admin',
                'icon' => 'bi-shield-lock',
                'items' => [
                    ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'bi-person-gear'],
                    ['label' => 'Roles', 'route' => 'admin.roles.index', 'icon' => 'bi-person-badge'],
                    ['label' => 'Security Clearance', 'route' => 'admin.security-clearance.index', 'icon' => 'bi-shield-check'],
                    ['label' => 'Audit Trail', 'route' => 'admin.audit.index', 'icon' => 'bi-journal-text'],
                    ['label' => 'Settings', 'route' => 'admin.settings.index', 'icon' => 'bi-sliders'],
                    ['label' => 'Triplestore Status', 'route' => 'admin.triplestore.status', 'icon' => 'bi-database-check'],
                ],
            ],
        ];
    }

    public function getCurrentViewMode(): string
    {
        return Session::get('openric_view_mode', 'ric');
    }
}
