<?php

declare(strict_types=1);

namespace OpenRiC\Theme\Contracts;

interface ThemeServiceInterface
{
    /**
     * Get layout data for the master template.
     *
     * Returns an array containing:
     * - appName: string
     * - appVersion: string
     * - locale: string
     * - isAuthenticated: bool
     * - userName: string|null
     * - userEmail: string|null
     * - isAdmin: bool
     *
     * @return array<string, mixed>
     */
    public function getLayoutData(): array;

    /**
     * Get navigation items grouped by category.
     *
     * Returns an array of navigation groups, each containing:
     * - label: string
     * - icon: string (Bootstrap Icons class)
     * - items: array of ['label' => string, 'route' => string, 'icon' => string]
     *
     * @return array<string, array<string, mixed>>
     */
    public function getNavigationItems(): array;

    /**
     * Get the current view mode from session.
     *
     * @return string 'ric' or 'traditional'
     */
    public function getCurrentViewMode(): string;
}
