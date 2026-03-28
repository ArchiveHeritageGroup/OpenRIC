<?php

declare(strict_types=1);

namespace OpenRiC\Help\Contracts;

/**
 * Help service interface -- adapted from Heratio AhgHelp\Services\HelpArticleService (167 lines).
 *
 * Provides a static in-memory help system. Topics are organized by category
 * with title, slug, and markdown content. No database required.
 */
interface HelpServiceInterface
{
    /**
     * Get all help topics organized by category.
     *
     * @return array<string, array<int, array{title: string, slug: string, category: string, content: string}>>
     */
    public function getTopics(): array;

    /**
     * Get a single help topic by its slug.
     *
     * @return array{title: string, slug: string, category: string, content: string}|null
     */
    public function getTopic(string $slug): ?array;

    /**
     * Search help topics by keyword across title and content.
     *
     * @return array<int, array{title: string, slug: string, category: string, snippet: string}>
     */
    public function searchTopics(string $query): array;
}
