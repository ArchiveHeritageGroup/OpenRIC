<?php

declare(strict_types=1);

namespace OpenRiC\LandingPage\Contracts;

/**
 * Landing page service interface -- adapted from Heratio AhgLandingPage\Services\LandingPageService (208 lines).
 *
 * Uses the settings table (group='landing_page') for content storage rather than dedicated tables,
 * keeping the schema lightweight while the Heratio pattern had full page/block tables.
 */
interface LandingPageServiceInterface
{
    /**
     * Get landing page content from settings.
     *
     * @return array<string, string>
     */
    public function getPageContent(): array;

    /**
     * Update landing page content in settings.
     */
    public function updatePageContent(array $data): void;

    /**
     * Get ordered list of active widgets for the landing page.
     *
     * @return array<int, array{key: string, label: string, enabled: bool, position: int}>
     */
    public function getWidgets(): array;

    /**
     * Reorder widgets by providing an ordered array of widget keys.
     */
    public function reorderWidgets(array $orderedKeys): void;

    /**
     * Get statistics for the landing page: record counts by type, recent additions.
     *
     * @return array{counts: array<string, int>, recent: array}
     */
    public function getStats(): array;
}
