<?php

declare(strict_types=1);

namespace OpenRiC\LandingPage\Contracts;

use Illuminate\Support\Collection;

/**
 * Landing page service interface -- adapted from Heratio AhgLandingPage\Services\LandingPageService.
 *
 * Full block-based page builder with CRUD for pages, blocks, block types, versioning,
 * user dashboards, and nested column layouts. PostgreSQL storage.
 */
interface LandingPageServiceInterface
{
    // ── Page CRUD ────────────────────────────────────────────────────────

    public function getAllPages(): Collection;

    public function getPage(int $id): ?object;

    public function getPageBySlug(?string $slug): ?object;

    /**
     * @return array{success: bool, page_id?: int, error?: string}
     */
    public function createPage(array $data, int $userId): array;

    /**
     * @return array{success: bool}
     */
    public function updatePage(int $id, array $data, int $userId): array;

    /**
     * @return array{success: bool}
     */
    public function deletePage(int $id, int $userId): array;

    // ── Block CRUD ───────────────────────────────────────────────────────

    public function getPageBlocks(int $pageId, bool $visibleOnly = true): Collection;

    public function getBlockTypes(): Collection;

    /**
     * @return array{success: bool, block_id?: int}
     */
    public function addBlock(int $pageId, int $blockTypeId, array $config, int $userId, array $options = []): array;

    /**
     * @return array{success: bool}
     */
    public function updateBlock(int $blockId, array $data, int $userId): array;

    /**
     * @return array{success: bool}
     */
    public function deleteBlock(int $blockId, int $userId): array;

    /**
     * @return array{success: bool}
     */
    public function reorderBlocks(int $pageId, array $order, int $userId): array;

    /**
     * @return array{success: bool, block_id?: int, error?: string}
     */
    public function duplicateBlock(int $blockId, int $userId): array;

    /**
     * @return array{success: bool, is_visible?: bool, error?: string}
     */
    public function toggleBlockVisibility(int $blockId, int $userId): array;

    // ── Versioning ───────────────────────────────────────────────────────

    public function getPageVersions(int $pageId): Collection;

    /**
     * @return array{success: bool, version_id?: int}
     */
    public function createVersion(int $pageId, string $status, int $userId): array;

    // ── User Dashboards ──────────────────────────────────────────────────

    public function getUserDashboards(int $userId): Collection;

    // ── Statistics ────────────────────────────────────────────────────────

    /**
     * @return array{counts: array<string, int>, recent: array}
     */
    public function getStats(): array;

    // ── Child blocks for column layouts ──────────────────────────────────

    public function getChildBlocks(int $parentBlockId, bool $visibleOnly = true): Collection;
}
