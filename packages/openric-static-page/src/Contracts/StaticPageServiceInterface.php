<?php

declare(strict_types=1);

namespace OpenRiC\StaticPage\Contracts;

use Illuminate\Support\Collection;

/**
 * Static page service interface -- adapted from Heratio AhgStaticPage\Controllers\StaticPageController (316 lines).
 *
 * Provides CRUD operations for static pages with i18n support, Markdown rendering,
 * slug management, and protected-page enforcement.
 */
interface StaticPageServiceInterface
{
    /**
     * List all pages with their translations for the given culture.
     *
     * @return Collection<int, \stdClass> Each item has: id, slug, title, is_protected, is_published, sort_order
     */
    public function listPages(string $culture): Collection;

    /**
     * Find a single page by its slug, with translation for the given culture.
     * Falls back to source culture if no translation exists.
     *
     * @return \stdClass|null Object with: id, slug, title, content, is_protected, is_published, source_culture
     */
    public function findBySlug(string $slug, string $culture): ?\stdClass;

    /**
     * Create a new static page with its translation and slug.
     *
     * @param array{title: string, slug: string, content: string|null, is_published?: bool} $data
     */
    public function create(array $data, string $culture): int;

    /**
     * Update an existing static page. Handles i18n upsert and slug change
     * (unless the page is protected).
     *
     * @param array{title: string, slug?: string, content: string|null, is_published?: bool} $data
     */
    public function update(int $id, string $currentSlug, array $data, string $culture): string;

    /**
     * Delete a static page and all related rows. Refuses to delete protected pages.
     *
     * @throws \RuntimeException if the page is protected
     */
    public function delete(string $slug): void;

    /**
     * Render Markdown content to HTML using League\CommonMark.
     * Converts escaped newlines, allows safe HTML, blocks unsafe links.
     */
    public function renderMarkdown(string $content): string;

    /**
     * Check whether a slug belongs to a protected page.
     */
    public function isProtected(string $slug): bool;

    /**
     * Get the list of protected slugs.
     *
     * @return string[]
     */
    public function getProtectedSlugs(): array;
}
