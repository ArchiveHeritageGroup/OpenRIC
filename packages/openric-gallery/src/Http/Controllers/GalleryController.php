<?php

declare(strict_types=1);

namespace OpenRiC\Gallery\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenRiC\Gallery\Contracts\GalleryServiceInterface;

/**
 * Gallery controller — adapted from Heratio ahg-gallery GalleryController.
 *
 * Public routes: index (browse), show (single gallery).
 * Admin routes: admin list, create, store, edit, update, destroy, items manage, add/remove/reorder.
 */
class GalleryController extends Controller
{
    public function __construct(
        private readonly GalleryServiceInterface $service,
    ) {
    }

    // =========================================================================
    // Public Routes
    // =========================================================================

    /**
     * Public gallery listing.
     */
    public function index(Request $request): View
    {
        $result = $this->service->getGalleries([
            'page' => (int) $request->get('page', 1),
            'limit' => 24,
            'search' => $request->get('search', ''),
            'public_only' => true,
        ]);

        $featured = $this->service->getFeaturedGalleries(6);

        return view('openric-gallery::index', [
            'galleries' => $result['galleries'],
            'total' => $result['total'],
            'featured' => $featured,
        ]);
    }

    /**
     * Public single gallery view.
     */
    public function show(string $slug): View
    {
        $gallery = DB::table('galleries')->where('slug', $slug)->where('is_public', true)->first();
        if (!$gallery) {
            abort(404, 'Gallery not found.');
        }

        $detail = $this->service->getGallery($gallery->id);

        return view('openric-gallery::show', ['gallery' => $detail]);
    }

    // =========================================================================
    // Admin Routes
    // =========================================================================

    /**
     * Admin gallery list.
     */
    public function admin(Request $request): View
    {
        $result = $this->service->getGalleries([
            'page' => (int) $request->get('page', 1),
            'limit' => 25,
            'search' => $request->get('search', ''),
        ]);

        return view('openric-gallery::admin', [
            'galleries' => $result['galleries'],
            'total' => $result['total'],
        ]);
    }

    /**
     * Create gallery form.
     */
    public function create(): View
    {
        return view('openric-gallery::create');
    }

    /**
     * Store new gallery.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'cover_image' => 'nullable|string|max:500',
            'is_featured' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $id = $this->service->createGallery($request->only([
            'title', 'description', 'cover_image', 'is_featured', 'is_public', 'sort_order',
        ]));

        return redirect()->route('gallery.admin.edit', $id)
            ->with('success', 'Gallery created successfully.');
    }

    /**
     * Edit gallery form.
     */
    public function edit(int $id): View
    {
        $gallery = $this->service->getGallery($id);
        if (!$gallery) {
            abort(404, 'Gallery not found.');
        }

        return view('openric-gallery::edit', ['gallery' => $gallery]);
    }

    /**
     * Update gallery.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'cover_image' => 'nullable|string|max:500',
            'is_featured' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $this->service->updateGallery($id, $request->only([
            'title', 'description', 'cover_image', 'is_featured', 'is_public', 'sort_order',
        ]));

        return redirect()->route('gallery.admin.edit', $id)
            ->with('success', 'Gallery updated.');
    }

    /**
     * Delete gallery.
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->service->deleteGallery($id);

        return redirect()->route('gallery.admin')
            ->with('success', 'Gallery deleted.');
    }

    /**
     * Manage items in a gallery.
     */
    public function items(int $id): View
    {
        $gallery = $this->service->getGallery($id);
        if (!$gallery) {
            abort(404, 'Gallery not found.');
        }

        return view('openric-gallery::items', ['gallery' => $gallery]);
    }

    /**
     * Add an item to a gallery.
     */
    public function addItem(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'entity_iri' => 'required|string|max:2048',
            'entity_type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'thumbnail' => 'nullable|string|max:500',
        ]);

        $itemId = $this->service->addItem(
            $id,
            $request->input('entity_iri'),
            $request->input('entity_type'),
            $request->input('title'),
            $request->input('thumbnail')
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'item_id' => $itemId]);
        }

        return redirect()->route('gallery.admin.items', $id)
            ->with('success', 'Item added to gallery.');
    }

    /**
     * Remove an item from a gallery.
     */
    public function removeItem(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $request->validate(['item_id' => 'required|integer']);

        $this->service->removeItem((int) $request->input('item_id'));

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('gallery.admin.items', $id)
            ->with('success', 'Item removed from gallery.');
    }

    /**
     * Reorder items in a gallery.
     */
    public function reorder(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        $this->service->reorderItems($id, $request->input('order'));

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('gallery.admin.items', $id)
            ->with('success', 'Items reordered.');
    }
}
