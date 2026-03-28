<?php

declare(strict_types=1);

namespace OpenRiC\Theme\Contracts;

/**
 * Theme service interface — adapted from Heratio ThemeService (130 lines).
 *
 * Collects all data needed by layout templates: user auth state, site settings,
 * navigation structure, menu data, version info, and display preferences.
 */
interface ThemeServiceInterface
{
    /**
     * Get all data needed by layout templates.
     *
     * Returns an array containing:
     * - appName, appVersion, locale, csrfToken
     * - user, isAuthenticated, isAdmin, isEditor, userName, userEmail
     * - siteTitle, siteDescription, repositoryName, repositoryCode, resultsPerPage
     * - primaryColor, sidebarCollapsed, displayMode, logoPath, footerText, showBranding
     * - currentViewMode
     * - navigationItems
     * - pendingTaskCount, pendingAccessRequestCount
     *
     * @return array<string, mixed>
     */
    public function getLayoutData(): array;

    /**
     * Get navigation items grouped by category.
     *
     * Returns an array of navigation groups (records, agents, context, etc.)
     * each containing label, icon, and items array.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getNavigationItems(): array;

    /**
     * Get the current view mode from session.
     *
     * @return string 'ric', 'traditional', or 'graph'
     */
    public function getCurrentViewMode(): string;
}
