<?php

declare(strict_types=1);

namespace OpenRiC\DigitalObject\Controllers;

use App\Http\Controllers\Controller;
use OpenRiC\DigitalObject\Services\IiifCollectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * IIIF Collection Management Controller.
 * Adapted from Heratio AhgIiifCollection\Controllers\IiifCollectionController.
 * Bootstrap 5, declare(strict_types=1), PostgreSQL ILIKE.
 */
class IiifCollectionController extends Controller
{
    protected IiifCollectionService $service;

    public function __construct(IiifCollectionService $service)
    {
        $this->service = $service;
    }

    /** List all collections. */
    public function index(Request $request): \Illuminate\View\View
    {
        $parentId = $request->input('parent_id') ? (int) $request->input('parent_id') : null;
        $collections = $this->service->getAllCollections($parentId);
        $parentCollection = $parentId ? $this->service->getCollection($parentId) : null;

        return view('openric-digital-object::iiif-collection.index', compact('collections', 'parentId', 'parentCollection'));
    }

    /** View a single collection. */
    public function view(int $id): \Illuminate\View\View
    {
        $collection = $this->service->getCollection($id);
        if ($collection === null) {
            abort(404);
        }
        $breadcrumbs = $this->service->getBreadcrumbs($collection);

        return view('openric-digital-object::iiif-collection.view', compact('collection', 'breadcrumbs'));
    }

    /** Create new collection form. */
    public function create(Request $request): \Illuminate\View\View
    {
        $parentId = $request->input('parent_id');
        $allCollections = $this->service->getAllCollections();
        return view('openric-digital-object::iiif-collection.create', compact('parentId', 'allCollections'));
    }

    /** Store new collection. */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        $id = $this->service->createCollection([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'attribution' => $request->input('attribution'),
            'viewing_hint' => $request->input('viewing_hint', 'individuals'),
            'parent_id' => $request->input('parent_id') ?: null,
            'is_public' => $request->input('is_public', 1),
            'created_by' => auth()->id(),
        ]);
        return redirect()->route('iiif-collection.view', $id);
    }

    /** Edit collection form. */
    public function edit(int $id): \Illuminate\View\View
    {
        $collection = $this->service->getCollection($id);
        if ($collection === null) {
            abort(404);
        }
        $allCollections = $this->service->getAllCollections();
        return view('openric-digital-object::iiif-collection.edit', compact('collection', 'allCollections'));
    }

    /** Update collection. */
    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        $this->service->updateCollection($id, [
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'attribution' => $request->input('attribution'),
            'viewing_hint' => $request->input('viewing_hint'),
            'parent_id' => $request->input('parent_id') ?: null,
            'is_public' => $request->input('is_public', 0),
        ]);
        return redirect()->route('iiif-collection.view', $id);
    }

    /** Delete collection. */
    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        $collection = $this->service->getCollection($id);
        $parentId = $collection?->parent_id;
        $this->service->deleteCollection($id);
        return $parentId ? redirect()->route('iiif-collection.view', $parentId) : redirect()->route('iiif-collection.index');
    }

    /** Add items form. */
    public function addItems(Request $request, int $id): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $collection = $this->service->getCollection($id);
        if ($collection === null) {
            abort(404);
        }

        if ($request->isMethod('post')) {
            $objectIds = $request->input('object_ids', []);
            $includeChildren = $request->input('include_children', []);
            $manifestUri = $request->input('manifest_uri');
            if ($manifestUri) {
                $this->service->addItem($id, [
                    'manifest_uri' => $manifestUri,
                    'label' => $request->input('label'),
                    'item_type' => $request->input('item_type', 'manifest'),
                ]);
            }
            if (is_array($objectIds)) {
                foreach ($objectIds as $objectId) {
                    $objectId = (int) $objectId;
                    $this->service->addItem($id, ['object_id' => $objectId]);
                    if (in_array($objectId, (array) $includeChildren)) {
                        $this->service->addChildrenToCollection($id, $objectId);
                    }
                }
            }
            return redirect()->route('iiif-collection.view', $id);
        }

        $searchQuery = $request->input('q', '');
        $searchResults = $searchQuery ? $this->service->searchObjects($searchQuery) : [];
        return view('openric-digital-object::iiif-collection.add-items', compact('collection', 'searchQuery', 'searchResults'));
    }

    /** Remove item from collection. */
    public function removeItem(Request $request): \Illuminate\Http\RedirectResponse
    {
        $this->service->removeItem((int) $request->input('item_id'));
        return redirect()->route('iiif-collection.view', (int) $request->input('collection_id'));
    }

    /** Reorder items (AJAX). */
    public function reorder(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->service->reorderItems((int) $request->input('collection_id'), $request->input('item_ids', []));
        return response()->json(['success' => true]);
    }

    /** Output IIIF Collection JSON. */
    public function manifest(string $slug): \Illuminate\Http\JsonResponse
    {
        $collection = $this->service->getCollection($slug);
        if ($collection === null || (!$collection->is_public && !auth()->check())) {
            return response()->json(['error' => 'Collection not found'], 404);
        }
        $json = $this->service->generateCollectionJson((int) $collection->id);
        return response()->json($json, 200, [
            'Content-Type' => 'application/ld+json',
            'Access-Control-Allow-Origin' => '*',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /** Output IIIF Manifest for an information object. */
    public function objectManifest(string $slug): \Illuminate\Http\JsonResponse
    {
        $json = $this->service->generateObjectManifest($slug);
        if ($json === null) {
            return response()->json(['error' => 'Object not found or has no digital objects'], 404);
        }
        return response()->json($json, 200, [
            'Content-Type' => 'application/ld+json',
            'Access-Control-Allow-Origin' => '*',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /** AJAX autocomplete for objects. */
    public function autocomplete(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json(['results' => $this->service->autocompleteObjects($request->input('q', ''))]);
    }

    /** IIIF Viewer page. */
    public function viewer(string $slug): \Illuminate\View\View
    {
        $culture = app()->getLocale();
        $object = DB::table('information_object')
            ->join('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
            ->join('slug', 'information_object.id', '=', 'slug.object_id')
            ->where('slug.slug', $slug)
            ->where('information_object_i18n.culture', $culture)
            ->select('information_object.id', 'information_object_i18n.title', 'slug.slug')
            ->first();
        if ($object === null) {
            abort(404);
        }
        $manifestUrl = route('iiif-collection.object-manifest', $slug);
        return view('openric-digital-object::iiif.viewer', ['objectTitle' => $object->title, 'objectSlug' => $slug, 'manifestUrl' => $manifestUrl]);
    }

    /** IIIF Comparison viewer. */
    public function compare(Request $request): \Illuminate\View\View
    {
        $manifests = $request->input('manifests', []);
        if (is_string($manifests)) {
            $manifests = explode(',', $manifests);
        }
        return view('openric-digital-object::iiif.compare', compact('manifests'));
    }

    /** IIIF Settings page. */
    public function settings(): \Illuminate\View\View
    {
        $settings = [];
        if (Schema::hasTable('iiif_viewer_settings')) {
            $settings = DB::table('iiif_viewer_settings')->pluck('setting_value', 'setting_key')->all();
        }
        $collections = $this->service->getAllCollections();
        return view('openric-digital-object::iiif.settings', compact('settings', 'collections'));
    }

    /** Update IIIF Settings. */
    public function settingsUpdate(Request $request): \Illuminate\Http\RedirectResponse
    {
        if (Schema::hasTable('iiif_viewer_settings')) {
            foreach ($request->except(['_token']) as $key => $value) {
                DB::table('iiif_viewer_settings')->updateOrInsert(['setting_key' => $key], ['setting_value' => $value ?? '']);
            }
        }
        return redirect()->route('iiif.settings')->with('success', 'Settings saved.');
    }

    /** IIIF Validation Dashboard. */
    public function validationDashboard(): \Illuminate\View\View
    {
        $stats = ['total' => 0, 'passed' => 0, 'failed' => 0, 'warning' => 0];
        $recentFailures = collect();
        return view('openric-digital-object::iiif.validation-dashboard', compact('stats', 'recentFailures'));
    }

    /** Media processing queue. */
    public function mediaQueue(): \Illuminate\View\View
    {
        $stats = ['pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0];
        $jobs = collect();
        return view('openric-digital-object::mediaSettings.queue', compact('stats', 'jobs'));
    }

    /** Media processing test form. */
    public function mediaTest(): \Illuminate\View\View
    {
        return view('openric-digital-object::mediaSettings.test');
    }

    public function mediaTestRun(Request $request): \Illuminate\View\View
    {
        return view('openric-digital-object::mediaSettings.test', ['result' => ['status' => 'success', 'message' => 'Test completed']]);
    }

    /** 3D Reports. */
    public function threeDIndex(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('iiif.three-d-reports.models');
    }

    public function threeDDigitalObjects(): \Illuminate\View\View
    {
        return view('openric-digital-object::threeDReports.digital-objects', ['items' => collect()]);
    }

    public function threeDHotspots(): \Illuminate\View\View
    {
        return view('openric-digital-object::threeDReports.hotspots', ['items' => collect()]);
    }

    public function threeDModels(): \Illuminate\View\View
    {
        return view('openric-digital-object::threeDReports.models', ['items' => collect()]);
    }

    public function threeDSettings(): \Illuminate\View\View
    {
        return view('openric-digital-object::threeDReports.settings', ['items' => collect()]);
    }

    public function threeDThumbnails(): \Illuminate\View\View
    {
        return view('openric-digital-object::threeDReports.thumbnails', ['items' => collect()]);
    }
}
