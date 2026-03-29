<?php

declare(strict_types=1);

namespace OpenRiC\StaticPage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use League\CommonMark\CommonMarkConverter;
use OpenRiC\StaticPage\Contracts\StaticPageServiceInterface;

/**
 * Static page service -- adapted from Heratio AhgStaticPage\Controllers\StaticPageController (316 lines).
 *
 * Full service layer for static page CRUD with i18n, Markdown rendering,
 * protected-page enforcement, and slug management. All DB logic extracted
 * from the Heratio controller into this service for testability and reuse.
 */
class StaticPageService implements StaticPageServiceInterface
{
    /**
     * Slugs that cannot be deleted or renamed.
     * Heratio protects home, about, contact; we add privacy and terms.
     */
    private const PROTECTED_SLUGS = ['home', 'about', 'contact', 'privacy', 'terms'];

    private readonly CommonMarkConverter $markdownConverter;

    public function __construct()
    {
        $this->markdownConverter = new CommonMarkConverter([
            'html_input'         => 'allow',
            'allow_unsafe_links' => false,
        ]);
    }

    /* ------------------------------------------------------------------
     * Query
     * ------------------------------------------------------------------ */

    public function listPages(string $culture): Collection
    {
        return DB::table('static_pages')
            ->leftJoin('static_page_translations', function ($join) use ($culture): void {
                $join->on('static_pages.id', '=', 'static_page_translations.static_page_id')
                    ->where('static_page_translations.culture', '=', $culture);
            })
            ->select([
                'static_pages.id',
                'static_pages.slug',
                'static_pages.is_protected',
                'static_pages.is_published',
                'static_pages.sort_order',
                'static_page_translations.title',
            ])
            ->orderBy('static_pages.sort_order')
            ->orderBy('static_page_translations.title')
            ->get();
    }

    public function findBySlug(string $slug, string $culture): ?\stdClass
    {
        $page = DB::table('static_pages')
            ->leftJoin('static_page_translations', function ($join) use ($culture): void {
                $join->on('static_pages.id', '=', 'static_page_translations.static_page_id')
                    ->where('static_page_translations.culture', '=', $culture);
            })
            ->where('static_pages.slug', $slug)
            ->select([
                'static_pages.id',
                'static_pages.slug',
                'static_pages.is_protected',
                'static_pages.is_published',
                'static_pages.sort_order',
                'static_pages.source_culture',
                'static_page_translations.title',
                'static_page_translations.content',
            ])
            ->first();

        if ($page === null) {
            return null;
        }

        // Fallback to source culture if no translation exists (mirrors Heratio show() lines 280-291)
        if (empty($page->title) && $culture !== $page->source_culture) {
            $fallback = DB::table('static_page_translations')
                ->where('static_page_id', $page->id)
                ->where('culture', $page->source_culture)
                ->select(['title', 'content'])
                ->first();

            if ($fallback !== null) {
                $page->title   = $fallback->title;
                $page->content = $fallback->content;
            }
        }

        return $page;
    }

    /* ------------------------------------------------------------------
     * Create
     * ------------------------------------------------------------------ */

    public function create(array $data, string $culture): int
    {
        return DB::transaction(function () use ($data, $culture): int {
            $slug        = $data['slug'];
            $isProtected = in_array($slug, self::PROTECTED_SLUGS, true);

            $id = DB::table('static_pages')->insertGetId([
                'slug'           => $slug,
                'is_protected'   => $isProtected,
                'is_published'   => $data['is_published'] ?? true,
                'sort_order'     => $this->nextSortOrder(),
                'source_culture' => $culture,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::table('static_page_translations')->insert([
                'static_page_id' => $id,
                'culture'        => $culture,
                'title'          => $data['title'],
                'content'        => $data['content'] ?? '',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return (int) $id;
        });
    }

    /* ------------------------------------------------------------------
     * Update
     * ------------------------------------------------------------------ */

    public function update(int $id, string $currentSlug, array $data, string $culture): string
    {
        return DB::transaction(function () use ($id, $currentSlug, $data, $culture): string {
            // Upsert translation (mirrors Heratio update() lines 207-225)
            $exists = DB::table('static_page_translations')
                ->where('static_page_id', $id)
                ->where('culture', $culture)
                ->exists();

            if ($exists) {
                DB::table('static_page_translations')
                    ->where('static_page_id', $id)
                    ->where('culture', $culture)
                    ->update([
                        'title'      => $data['title'],
                        'content'    => $data['content'] ?? '',
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('static_page_translations')->insert([
                    'static_page_id' => $id,
                    'culture'        => $culture,
                    'title'          => $data['title'],
                    'content'        => $data['content'] ?? '',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            // Update slug if changed and not protected (mirrors Heratio update() lines 228-231)
            $newSlug   = $data['slug'] ?? $currentSlug;
            $finalSlug = $currentSlug;

            if ($newSlug !== $currentSlug && !$this->isProtected($currentSlug)) {
                DB::table('static_pages')
                    ->where('id', $id)
                    ->update([
                        'slug'       => $newSlug,
                        'updated_at' => now(),
                    ]);
                $finalSlug = $newSlug;
            }

            // Update publish state if provided
            if (array_key_exists('is_published', $data)) {
                DB::table('static_pages')
                    ->where('id', $id)
                    ->update([
                        'is_published' => (bool) $data['is_published'],
                        'updated_at'   => now(),
                    ]);
            }

            // Touch updated_at
            DB::table('static_pages')
                ->where('id', $id)
                ->update(['updated_at' => now()]);

            return $finalSlug;
        });
    }

    /* ------------------------------------------------------------------
     * Delete
     * ------------------------------------------------------------------ */

    public function delete(string $slug): void
    {
        if ($this->isProtected($slug)) {
            throw new \RuntimeException('Protected pages cannot be deleted.');
        }

        $page = DB::table('static_pages')->where('slug', $slug)->first();

        if ($page === null) {
            throw new \RuntimeException('Static page not found.');
        }

        DB::transaction(function () use ($page): void {
            DB::table('static_page_translations')
                ->where('static_page_id', $page->id)
                ->delete();

            DB::table('static_pages')
                ->where('id', $page->id)
                ->delete();
        });
    }

    /* ------------------------------------------------------------------
     * Markdown
     * ------------------------------------------------------------------ */

    public function renderMarkdown(string $content): string
    {
        // Convert literal \n to actual newlines (DB may store escaped newlines)
        // Mirrors Heratio show() lines 302-303
        $content = str_replace(['\\n', '\n'], "\n", $content);

        return $this->markdownConverter->convert($content)->getContent();
    }

    /* ------------------------------------------------------------------
     * Protected pages
     * ------------------------------------------------------------------ */

    public function isProtected(string $slug): bool
    {
        return in_array($slug, self::PROTECTED_SLUGS, true);
    }

    public function getProtectedSlugs(): array
    {
        return self::PROTECTED_SLUGS;
    }

    /* ------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */

    private function nextSortOrder(): int
    {
        $max = DB::table('static_pages')->max('sort_order');

        return $max !== null ? ((int) $max + 10) : 10;
    }
}
