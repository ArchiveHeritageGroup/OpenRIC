<?php

declare(strict_types=1);

namespace OpenRiC\StaticPage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenRiC\StaticPage\Contracts\StaticPageServiceInterface;

/**
 * Static page controller -- adapted from Heratio AhgStaticPage\Controllers\StaticPageController (316 lines).
 *
 * Provides browse, show, create, edit, update, delete, and confirmDelete actions.
 * All DB logic delegated to StaticPageService; this controller handles HTTP concerns,
 * validation, flash messages, and view rendering only.
 */
class StaticPageController extends Controller
{
    public function __construct(
        private readonly StaticPageServiceInterface $service,
    ) {}

    /* ------------------------------------------------------------------
     * Public: browse & show
     * ------------------------------------------------------------------ */

    /**
     * Public browse: list all published static pages.
     * Mirrors Heratio browse() with sidebar context.
     */
    public function browse(Request $request): \Illuminate\Contracts\View\View|JsonResponse
    {
        $culture = app()->getLocale();
        $pages   = $this->service->listPages($culture)
            ->filter(fn (\stdClass $p): bool => (bool) $p->is_published);

        if ($request->expectsJson()) {
            return response()->json(['pages' => $pages->values()]);
        }

        return view('openric-static-page::index', [
            'pages'          => $pages,
            'protectedSlugs' => $this->service->getProtectedSlugs(),
            'isAdmin'        => true, // browse is admin route
        ]);
    }

    /**
     * Show a single static page by slug with Markdown rendering.
     * Mirrors Heratio show() lines 247-314 including source-culture fallback and Markdown.
     */
    public function show(Request $request, string $slug): \Illuminate\Contracts\View\View|JsonResponse
    {
        $culture = app()->getLocale();
        $page    = $this->service->findBySlug($slug, $culture);

        if ($page === null) {
            abort(404, 'Static page not found.');
        }

        // Render Markdown content
        if (!empty($page->content)) {
            $page->rendered_content = $this->service->renderMarkdown($page->content);
        } else {
            $page->rendered_content = '';
        }

        if ($request->expectsJson()) {
            return response()->json(['page' => $page]);
        }

        return view('openric-static-page::show', [
            'page'           => $page,
            'protectedSlugs' => $this->service->getProtectedSlugs(),
        ]);
    }

    /* ------------------------------------------------------------------
     * Admin: list (same as browse but admin layout)
     * ------------------------------------------------------------------ */

    /**
     * Admin page list -- mirrors Heratio list() with pager-ready output.
     */
    public function list(Request $request): \Illuminate\Contracts\View\View|JsonResponse
    {
        $culture = app()->getLocale();
        $pages   = $this->service->listPages($culture);

        if ($request->expectsJson()) {
            return response()->json(['pages' => $pages->values()]);
        }

        return view('openric-static-page::index', [
            'pages'          => $pages,
            'protectedSlugs' => $this->service->getProtectedSlugs(),
            'isAdmin'        => true,
        ]);
    }

    /* ------------------------------------------------------------------
     * Admin: create / store
     * ------------------------------------------------------------------ */

    /**
     * Show create form -- mirrors Heratio create() line 108-114.
     */
    public function create(): \Illuminate\Contracts\View\View
    {
        return view('openric-static-page::edit', [
            'page'        => null,
            'slug'        => '',
            'isProtected' => false,
        ]);
    }

    /**
     * Store a new static page -- mirrors Heratio store() lines 116-155.
     * Validates title, slug, and content; creates via service; redirects to show.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:1024',
            'slug'    => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]+$/',
                'unique:static_pages,slug',
            ],
            'content' => 'nullable|string',
        ]);

        $culture = app()->getLocale();

        $this->service->create([
            'title'   => $validated['title'],
            'slug'    => $validated['slug'],
            'content' => $validated['content'] ?? '',
        ], $culture);

        return redirect()
            ->route('staticpage.show', $validated['slug'])
            ->with('success', __('Page created.'));
    }

    /* ------------------------------------------------------------------
     * Admin: edit / update
     * ------------------------------------------------------------------ */

    /**
     * Show edit form -- mirrors Heratio edit() lines 157-187.
     * Loads the page, checks protected status, returns the edit view.
     */
    public function edit(string $slug): \Illuminate\Contracts\View\View
    {
        $culture = app()->getLocale();
        $page    = $this->service->findBySlug($slug, $culture);

        if ($page === null) {
            abort(404, 'Static page not found.');
        }

        return view('openric-static-page::edit', [
            'page'        => $page,
            'slug'        => $slug,
            'isProtected' => $this->service->isProtected($slug),
        ]);
    }

    /**
     * Update a static page -- mirrors Heratio update() lines 189-244.
     * Validates input, delegates to service, handles protected slug enforcement.
     */
    public function update(Request $request, string $slug): RedirectResponse
    {
        $rules = [
            'title'   => 'required|string|max:1024',
            'content' => 'nullable|string',
        ];

        // Only validate slug if page is not protected
        if (!$this->service->isProtected($slug)) {
            $rules['slug'] = [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]+$/',
            ];
        }

        $validated = $request->validate($rules);

        $culture = app()->getLocale();
        $page    = $this->service->findBySlug($slug, $culture);

        if ($page === null) {
            abort(404, 'Static page not found.');
        }

        $finalSlug = $this->service->update(
            (int) $page->id,
            $slug,
            [
                'title'   => $validated['title'],
                'slug'    => $validated['slug'] ?? $slug,
                'content' => $validated['content'] ?? '',
            ],
            $culture,
        );

        return redirect()
            ->route('staticpage.show', $finalSlug)
            ->with('success', __('Page updated.'));
    }

    /* ------------------------------------------------------------------
     * Admin: confirmDelete / destroy
     * ------------------------------------------------------------------ */

    /**
     * Confirm deletion page -- mirrors Heratio confirmDelete() lines 39-72.
     * Prevents deletion of protected pages; shows confirmation view.
     */
    public function confirmDelete(string $slug): \Illuminate\Contracts\View\View|RedirectResponse
    {
        if ($this->service->isProtected($slug)) {
            return redirect()
                ->route('staticpage.show', $slug)
                ->with('error', __('Protected pages cannot be deleted.'));
        }

        $culture = app()->getLocale();
        $page    = $this->service->findBySlug($slug, $culture);

        if ($page === null) {
            abort(404, 'Static page not found.');
        }

        return view('openric-static-page::delete', [
            'page' => $page,
            'slug' => $slug,
        ]);
    }

    /**
     * Destroy a static page -- mirrors Heratio destroy() lines 74-106.
     * Refuses protected pages; deletes via service; redirects to list.
     */
    public function destroy(string $slug): RedirectResponse
    {
        if ($this->service->isProtected($slug)) {
            return redirect()
                ->route('staticpage.list')
                ->with('error', __('Protected pages cannot be deleted.'));
        }

        try {
            $this->service->delete($slug);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('staticpage.list')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('staticpage.list')
            ->with('success', __('Static page deleted successfully.'));
    }
}
