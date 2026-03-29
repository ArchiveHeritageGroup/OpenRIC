<?php

declare(strict_types=1);

namespace OpenRiC\LandingPage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\LandingPage\Contracts\LandingPageServiceInterface;

/**
 * Landing page controller -- adapted from Heratio AhgLandingPage\Controllers\LandingPageController.
 *
 * Handles public display, admin CRUD, block management (add/update/delete/reorder/duplicate/toggle),
 * page settings, versioning, and user dashboards.
 */
class LandingPageController extends Controller
{
    public function __construct(
        private readonly LandingPageServiceInterface $service,
    ) {}

    // ── Public ────────────────────────────────────────────────────────────

    /**
     * Public landing page display. Resolves page by slug or falls back to default.
     */
    public function index(Request $request): View
    {
        $slug = $request->get('slug');
        $page = $this->service->getPageBySlug($slug);

        abort_unless($page, 404);

        $blocks = $this->service->getPageBlocks((int) $page->id);

        return view('openric-landing-page::index', compact('page', 'blocks'));
    }

    // ── Admin Page CRUD ──────────────────────────────────────────────────

    /**
     * List all landing pages (admin).
     */
    public function list(): View
    {
        $pages = $this->service->getAllPages();

        return view('openric-landing-page::list', compact('pages'));
    }

    /**
     * Create new landing page form + POST handler.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|regex:/^[a-z0-9\-]+$/',
            ]);

            $result = $this->service->createPage($request->only([
                'name', 'slug', 'description', 'is_default', 'is_active',
            ]), (int) auth()->id());

            if ($result['success']) {
                return redirect()->route('landing-page.edit', $result['page_id']);
            }

            return back()->withErrors(['error' => $result['error']]);
        }

        return view('openric-landing-page::create');
    }

    /**
     * Page builder / editor view.
     */
    public function edit(int $id): View
    {
        $page = $this->service->getPage($id);
        abort_unless($page, 404);

        $blocks     = $this->service->getPageBlocks($id, false);
        $blockTypes = $this->service->getBlockTypes();
        $versions   = $this->service->getPageVersions($id);

        return view('openric-landing-page::edit', compact('page', 'blocks', 'blockTypes', 'versions'));
    }

    /**
     * Update page settings (AJAX).
     */
    public function updateSettings(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9\-]+$/',
        ]);

        $result = $this->service->updatePage($id, $request->only([
            'name', 'slug', 'description', 'is_default', 'is_active',
        ]), (int) auth()->id());

        return response()->json($result);
    }

    /**
     * Delete a page (AJAX).
     */
    public function deletePage(Request $request, int $id): JsonResponse
    {
        $result = $this->service->deletePage($id, (int) auth()->id());

        return response()->json($result);
    }

    /**
     * Handle POST actions from the admin list (delete, toggle_active).
     */
    public function post(Request $request): RedirectResponse
    {
        $action = $request->get('action');
        $id     = (int) $request->get('id');

        if ($action === 'delete' && $id) {
            $this->service->deletePage($id, (int) auth()->id());

            return redirect()->route('landing-page.list')->with('notice', 'Landing page deleted.');
        }

        if ($action === 'toggle_active' && $id) {
            $page = $this->service->getPage($id);
            if ($page) {
                $this->service->updatePage($id, ['is_active' => !$page->is_active], (int) auth()->id());
            }

            return redirect()->route('landing-page.list')->with('notice', 'Page status updated.');
        }

        return redirect()->back()->with('error', 'Invalid action.');
    }

    // ── Block Management (AJAX) ──────────────────────────────────────────

    /**
     * Add a new block to a page.
     */
    public function addBlock(Request $request): JsonResponse
    {
        $config  = json_decode($request->get('config', '{}'), true) ?? [];
        $options = [];

        if ($request->get('parent_block_id')) {
            $options['parent_block_id'] = (int) $request->get('parent_block_id');
            $options['column_slot']     = $request->get('column_slot');
        }

        $result = $this->service->addBlock(
            (int) $request->get('page_id'),
            (int) $request->get('block_type_id'),
            $config,
            (int) auth()->id(),
            $options,
        );

        return response()->json($result);
    }

    /**
     * Update a block's config, title, styling.
     */
    public function updateBlock(Request $request, int $blockId): JsonResponse
    {
        $data = [];

        if ($request->has('config')) {
            $decoded = json_decode($request->get('config'), true);
            if (is_array($decoded)) {
                $data['config'] = $decoded;
            }
        }

        $styleFields = [
            'title', 'css_classes', 'container_type', 'background_color',
            'text_color', 'padding_top', 'padding_bottom', 'col_span',
        ];

        foreach ($styleFields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->get($field);
            }
        }

        $result = $this->service->updateBlock($blockId, $data, (int) auth()->id());

        return response()->json($result);
    }

    /**
     * Delete a block.
     */
    public function deleteBlock(Request $request, int $blockId): JsonResponse
    {
        $result = $this->service->deleteBlock($blockId, (int) auth()->id());

        return response()->json($result);
    }

    /**
     * Reorder blocks on a page.
     */
    public function reorderBlocks(Request $request): JsonResponse
    {
        $order = json_decode($request->get('order', '[]'), true) ?? [];

        $result = $this->service->reorderBlocks(
            (int) $request->get('page_id'),
            $order,
            (int) auth()->id(),
        );

        return response()->json($result);
    }

    /**
     * Duplicate a block (including nested children).
     */
    public function duplicateBlock(Request $request, int $blockId): JsonResponse
    {
        $result = $this->service->duplicateBlock($blockId, (int) auth()->id());

        return response()->json($result);
    }

    /**
     * Toggle block visibility.
     */
    public function toggleVisibility(Request $request, int $blockId): JsonResponse
    {
        $result = $this->service->toggleBlockVisibility($blockId, (int) auth()->id());

        return response()->json($result);
    }

    // ── Versioning (AJAX) ────────────────────────────────────────────────

    /**
     * Save a version snapshot (draft or published).
     */
    public function saveVersion(Request $request, int $id): JsonResponse
    {
        $status = $request->get('status', 'draft');
        $result = $this->service->createVersion($id, $status, (int) auth()->id());

        return response()->json($result);
    }

    // ── User Dashboards ──────────────────────────────────────────────────

    /**
     * Show the user's default dashboard.
     */
    public function myDashboard(): View|RedirectResponse
    {
        $dashboards = $this->service->getUserDashboards((int) auth()->id());

        if ($dashboards->isEmpty()) {
            return redirect()->route('landing-page.myDashboard.create');
        }

        $page   = $dashboards->first();
        $blocks = $this->service->getPageBlocks((int) $page->id);

        return view('openric-landing-page::my-dashboard', compact('page', 'blocks'));
    }

    /**
     * List all user dashboards.
     */
    public function myDashboardList(): View
    {
        $pages = $this->service->getUserDashboards((int) auth()->id());

        return view('openric-landing-page::my-dashboard-list', compact('pages'));
    }

    /**
     * Create a new user dashboard form + POST handler.
     */
    public function myDashboardCreate(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $result = $this->service->createPage(
                $request->only(['name', 'slug', 'description']) + ['is_active' => true, 'page_type' => 'dashboard'],
                (int) auth()->id(),
            );

            if ($result['success']) {
                return redirect()->route('landing-page.myDashboard');
            }

            return back()->withErrors(['error' => $result['error']]);
        }

        $hasDashboards = $this->service->getUserDashboards((int) auth()->id())->isNotEmpty();

        return view('openric-landing-page::my-dashboard-create', compact('hasDashboards'));
    }

    // ── Admin Dashboard (overview) ───────────────────────────────────────

    /**
     * Admin overview with stats.
     */
    public function admin(): View
    {
        $pages = $this->service->getAllPages();
        $stats = [
            'total'  => $pages->count(),
            'active' => $pages->where('is_active', true)->count(),
        ];

        return view('openric-landing-page::list', compact('pages', 'stats'));
    }
}
