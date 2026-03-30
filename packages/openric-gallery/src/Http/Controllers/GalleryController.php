<?php

declare(strict_types=1);

namespace OpenRiC\Gallery\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use OpenRiC\Gallery\Contracts\GalleryServiceInterface;

/**
 * Gallery controller -- adapted from Heratio ahg-gallery GalleryController (612 lines).
 *
 * Routes:
 *   Public: gallery index, browse artworks, show artwork, browse artists, show artist.
 *   Admin: dashboard, CRUD artworks, CRUD artists, loans, valuations, venues,
 *          facility reports, gallery reports, gallery collection management.
 */
class GalleryController extends Controller
{
    public function __construct(
        private readonly GalleryServiceInterface $service,
    ) {
    }

    // =========================================================================
    // Gallery Collections (curated entity groups)
    // =========================================================================

    /**
     * Public gallery collection listing.
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
     * Public single gallery collection view.
     */
    public function show(string $slug): View
    {
        $gallery = DB::table('galleries')->where('slug', $slug)->where('is_public', true)->first();
        if (!$gallery) {
            abort(404, 'Gallery not found.');
        }

        $detail = $this->service->getGallery((int) $gallery->id);

        return view('openric-gallery::show', ['gallery' => $detail]);
    }

    /**
     * Admin gallery collection list.
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
     * Create gallery collection form.
     */
    public function create(): View
    {
        return view('openric-gallery::create');
    }

    /**
     * Store new gallery collection.
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
     * Edit gallery collection form.
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
     * Update gallery collection.
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
     * Delete gallery collection.
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->service->deleteGallery($id);

        return redirect()->route('gallery.admin')
            ->with('success', 'Gallery deleted.');
    }

    /**
     * Manage items in a gallery collection.
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
     * Add an item to a gallery collection.
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
     * Remove an item from a gallery collection.
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
     * Reorder items in a gallery collection.
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

    // =========================================================================
    // Artwork Browse / Show (CCO artworks)
    // =========================================================================

    /**
     * Browse gallery artworks with pagination, search, sort, repository filter.
     */
    public function browseArtworks(Request $request): View
    {
        $culture = app()->getLocale();

        $params = [
            'page' => $request->get('page', 1),
            'limit' => $request->get('limit', 10),
            'sort' => $request->get('sort', 'alphabetic'),
            'subquery' => $request->get('subquery', ''),
        ];

        $repositoryId = $request->get('repository');
        if ($repositoryId) {
            $params['filters']['repository_id'] = $repositoryId;
        }

        $result = $this->service->browse($params, $culture);

        // Build a simple pager object for the view
        $pager = new \stdClass();
        $pager->results = $result['results'];
        $pager->total = $result['total'];
        $pager->page = $result['page'];
        $pager->limit = $result['limit'];
        $pager->lastPage = (int) ceil($result['total'] / max(1, $result['limit']));

        // Get list of repositories for filter dropdown
        $repositories = DB::table('repository')
            ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
            ->where('actor_i18n.culture', $culture)
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->get();

        return view('openric-gallery::gallery.browse', [
            'pager' => $pager,
            'repositoryNames' => $result['repositoryNames'] ?? [],
            'repositories' => $repositories,
            'selectedRepository' => $repositoryId,
            'sortOptions' => [
                'alphabetic' => 'Title',
                'lastUpdated' => 'Date modified',
                'identifier' => 'Identifier',
                'artist' => 'Artist',
            ],
        ]);
    }

    /**
     * Show a single gallery artwork.
     */
    public function showArtwork(string $slug): View
    {
        $culture = app()->getLocale();

        $artwork = $this->service->getBySlug($slug, $culture);

        if (!$artwork) {
            abort(404);
        }

        // Repository
        $repository = null;
        if ($artwork->repository_id) {
            $repository = DB::table('repository')
                ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
                ->join('slug', 'repository.id', '=', 'slug.object_id')
                ->where('repository.id', $artwork->repository_id)
                ->where('actor_i18n.culture', $culture)
                ->select('repository.id', 'actor_i18n.authorized_form_of_name as name', 'slug.slug')
                ->first();
        }

        // Digital objects
        $digitalObjects = [];
        try {
            $master = DB::table('digital_object')
                ->where('object_id', $artwork->id)
                ->whereNull('parent_id')
                ->first();
            if ($master) {
                $digitalObjects['master'] = $master;
                $ref = DB::table('digital_object')
                    ->where('parent_id', $master->id)
                    ->where('usage_id', 141)
                    ->first();
                if ($ref) {
                    $digitalObjects['reference'] = $ref;
                }
                $thumb = DB::table('digital_object')
                    ->where('parent_id', $master->id)
                    ->where('usage_id', 142)
                    ->first();
                if ($thumb) {
                    $digitalObjects['thumbnail'] = $thumb;
                }
            }
        } catch (\Exception $e) {
            // digital_object table may not exist
        }

        // Events (dates)
        $events = DB::table('event')
            ->join('event_i18n', 'event.id', '=', 'event_i18n.id')
            ->where('event.object_id', $artwork->id)
            ->where('event_i18n.culture', $culture)
            ->select(
                'event.id',
                'event.type_id',
                'event.actor_id',
                'event.start_date',
                'event.end_date',
                'event_i18n.date as date_display',
                'event_i18n.name as event_name'
            )
            ->get();

        // Creators (events where type_id = 111 = creation)
        $creators = DB::table('event')
            ->join('actor', 'event.actor_id', '=', 'actor.id')
            ->join('actor_i18n', 'event.actor_id', '=', 'actor_i18n.id')
            ->join('slug', 'event.actor_id', '=', 'slug.object_id')
            ->where('event.object_id', $artwork->id)
            ->where('event.type_id', 111)
            ->where('actor_i18n.culture', $culture)
            ->whereNotNull('event.actor_id')
            ->select(
                'event.actor_id as id',
                'actor_i18n.authorized_form_of_name as name',
                'actor_i18n.history',
                'slug.slug'
            )
            ->distinct()
            ->get();

        // Notes
        $notes = DB::table('note')
            ->join('note_i18n', 'note.id', '=', 'note_i18n.id')
            ->where('note.object_id', $artwork->id)
            ->where('note_i18n.culture', $culture)
            ->select('note.id', 'note.type_id', 'note_i18n.content')
            ->get();

        $noteTypeIds = $notes->pluck('type_id')->filter()->unique()->values()->toArray();
        $noteTypeNames = [];
        if (!empty($noteTypeIds)) {
            $noteTypeNames = DB::table('term_i18n')
                ->whereIn('id', $noteTypeIds)
                ->where('culture', $culture)
                ->pluck('name', 'id')
                ->toArray();
        }

        // Subject access points (taxonomy_id = 35)
        $subjects = DB::table('object_term_relation')
            ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', $artwork->id)
            ->where('term.taxonomy_id', 35)
            ->where('term_i18n.culture', $culture)
            ->select('term_i18n.name')
            ->get();

        // Place access points (taxonomy_id = 42)
        $places = DB::table('object_term_relation')
            ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', $artwork->id)
            ->where('term.taxonomy_id', 42)
            ->where('term_i18n.culture', $culture)
            ->select('term_i18n.name')
            ->get();

        // Genre access points (taxonomy_id = 78)
        $genres = DB::table('object_term_relation')
            ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', $artwork->id)
            ->where('term.taxonomy_id', 78)
            ->where('term_i18n.culture', $culture)
            ->select('term_i18n.name')
            ->get();

        // Publication status
        $publicationStatus = null;
        $publicationStatusId = null;
        $statusRow = DB::table('status')
            ->where('object_id', $artwork->id)
            ->where('type_id', 158)
            ->first();
        if ($statusRow && $statusRow->status_id) {
            $publicationStatusId = (int) $statusRow->status_id;
            $publicationStatus = DB::table('term_i18n')
                ->where('id', $statusRow->status_id)
                ->where('culture', $culture)
                ->value('name');
        }

        // Physical storage
        $physicalObjects = DB::table('relation')
            ->join('physical_object', 'relation.object_id', '=', 'physical_object.id')
            ->join('physical_object_i18n', 'physical_object.id', '=', 'physical_object_i18n.id')
            ->where('relation.subject_id', $artwork->id)
            ->where('relation.type_id', 151)
            ->where('physical_object_i18n.culture', $culture)
            ->select('physical_object.id', 'physical_object_i18n.name', 'physical_object_i18n.location', 'physical_object.type_id')
            ->get();

        // Level name
        $levelName = null;
        if ($artwork->level_of_description_id) {
            $levelName = DB::table('term_i18n')
                ->where('id', $artwork->level_of_description_id)
                ->where('culture', $culture)
                ->value('name');
        }

        // Parent breadcrumbs
        $breadcrumbs = [];
        $parentId = $artwork->parent_id;
        while ($parentId && $parentId != 1) {
            $parent = DB::table('information_object')
                ->join('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
                ->join('slug', 'information_object.id', '=', 'slug.object_id')
                ->where('information_object.id', $parentId)
                ->where('information_object_i18n.culture', $culture)
                ->select('information_object.id', 'information_object.parent_id', 'information_object_i18n.title', 'slug.slug')
                ->first();
            if (!$parent) {
                break;
            }
            array_unshift($breadcrumbs, $parent);
            $parentId = $parent->parent_id;
        }

        // Related gallery artist record (if creator_identity matches)
        $galleryArtist = null;
        if ($artwork->creator_identity) {
            $galleryArtist = DB::table('gallery_artist')
                ->where('display_name', $artwork->creator_identity)
                ->first();
        }

        return view('openric-gallery::gallery.show', [
            'artwork' => $artwork,
            'levelName' => $levelName,
            'repository' => $repository,
            'digitalObjects' => $digitalObjects,
            'events' => $events,
            'creators' => $creators,
            'notes' => $notes,
            'noteTypeNames' => $noteTypeNames,
            'subjects' => $subjects,
            'places' => $places,
            'genres' => $genres,
            'publicationStatus' => $publicationStatus,
            'publicationStatusId' => $publicationStatusId,
            'physicalObjects' => $physicalObjects,
            'breadcrumbs' => $breadcrumbs,
            'galleryArtist' => $galleryArtist,
        ]);
    }

    /**
     * Show create form for a gallery artwork.
     */
    public function createArtwork(Request $request): View
    {
        $culture = app()->getLocale();
        $choices = $this->service->getFormChoices($culture);
        $editExtras = $this->service->getEditExtras(null, $culture);

        return view('openric-gallery::gallery.edit', array_merge([
            'artwork' => null,
            'isNew' => true,
        ], $choices, $editExtras));
    }

    /**
     * Store a new gallery artwork.
     */
    public function storeArtwork(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:65535',
            'work_type' => 'nullable|string|max:255',
            'classification' => 'nullable|string|max:255',
            'identifier' => 'nullable|string|max:255',
            'creator_identity' => 'nullable|string|max:1024',
            'creator_role' => 'nullable|string|max:255',
            'creation_date_display' => 'nullable|string|max:255',
            'creation_date_earliest' => 'nullable|string|max:255',
            'creation_date_latest' => 'nullable|string|max:255',
            'creation_place' => 'nullable|string|max:1024',
            'style' => 'nullable|string|max:255',
            'period' => 'nullable|string|max:255',
            'movement' => 'nullable|string|max:255',
            'school' => 'nullable|string|max:255',
            'measurements' => 'nullable|string|max:1024',
            'dimensions' => 'nullable|string|max:1024',
            'materials' => 'nullable|string|max:1024',
            'techniques' => 'nullable|string|max:1024',
            'scope_and_content' => 'nullable|string|max:65535',
            'inscription' => 'nullable|string|max:65535',
            'mark_description' => 'nullable|string|max:65535',
            'condition_term' => 'nullable|string|max:255',
            'condition_description' => 'nullable|string|max:65535',
            'provenance' => 'nullable|string|max:65535',
            'current_location' => 'nullable|string|max:1024',
            'rights_type' => 'nullable|string|max:255',
            'rights_holder' => 'nullable|string|max:1024',
            'cataloger_name' => 'nullable|string|max:255',
            'cataloging_date' => 'nullable|string|max:255',
            'repository_id' => 'nullable|integer',
        ]);

        $slug = $this->service->createArtwork($request->all(), app()->getLocale());

        return redirect()
            ->route('gallery.artwork.show', $slug)
            ->with('success', 'Gallery artwork created successfully.');
    }

    /**
     * Show edit form for a gallery artwork.
     */
    public function editArtwork(string $slug): View
    {
        $culture = app()->getLocale();

        $artwork = $this->service->getBySlug($slug, $culture);

        if (!$artwork) {
            abort(404);
        }

        $choices = $this->service->getFormChoices($culture);
        $editExtras = $this->service->getEditExtras($artwork->id ?? null, $culture);

        return view('openric-gallery::gallery.edit', array_merge([
            'artwork' => $artwork,
            'isNew' => false,
        ], $choices, $editExtras));
    }

    /**
     * Update a gallery artwork.
     */
    public function updateArtwork(Request $request, string $slug): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:65535',
            'work_type' => 'nullable|string|max:255',
            'classification' => 'nullable|string|max:255',
            'identifier' => 'nullable|string|max:255',
            'creator_identity' => 'nullable|string|max:1024',
            'creator_role' => 'nullable|string|max:255',
            'creation_date_display' => 'nullable|string|max:255',
            'creation_date_earliest' => 'nullable|string|max:255',
            'creation_date_latest' => 'nullable|string|max:255',
            'creation_place' => 'nullable|string|max:1024',
            'style' => 'nullable|string|max:255',
            'period' => 'nullable|string|max:255',
            'movement' => 'nullable|string|max:255',
            'school' => 'nullable|string|max:255',
            'measurements' => 'nullable|string|max:1024',
            'dimensions' => 'nullable|string|max:1024',
            'materials' => 'nullable|string|max:1024',
            'techniques' => 'nullable|string|max:1024',
            'scope_and_content' => 'nullable|string|max:65535',
            'inscription' => 'nullable|string|max:65535',
            'mark_description' => 'nullable|string|max:65535',
            'condition_term' => 'nullable|string|max:255',
            'condition_description' => 'nullable|string|max:65535',
            'provenance' => 'nullable|string|max:65535',
            'current_location' => 'nullable|string|max:1024',
            'rights_type' => 'nullable|string|max:255',
            'rights_holder' => 'nullable|string|max:1024',
            'cataloger_name' => 'nullable|string|max:255',
            'cataloging_date' => 'nullable|string|max:255',
            'repository_id' => 'nullable|integer',
        ]);

        $this->service->updateArtwork($slug, $request->all(), app()->getLocale());

        return redirect()
            ->route('gallery.artwork.show', $slug)
            ->with('success', 'Gallery artwork updated successfully.');
    }

    /**
     * Delete a gallery artwork.
     */
    public function destroyArtwork(string $slug): RedirectResponse
    {
        $this->service->deleteArtwork($slug);

        return redirect()
            ->route('gallery.artwork.browse')
            ->with('success', 'Gallery artwork deleted successfully.');
    }

    // =========================================================================
    // Artists
    // =========================================================================

    /**
     * Browse gallery artists.
     */
    public function artists(Request $request): View
    {
        $params = [
            'page' => $request->get('page', 1),
            'limit' => $request->get('limit', 10),
            'sort' => $request->get('sort', 'alphabetic'),
            'subquery' => $request->get('subquery', ''),
        ];

        $result = $this->service->getArtists($params);

        $pager = new \stdClass();
        $pager->results = $result['results'];
        $pager->total = $result['total'];
        $pager->page = $result['page'];
        $pager->limit = $result['limit'];
        $pager->lastPage = (int) ceil($result['total'] / max(1, $result['limit']));

        return view('openric-gallery::gallery.artists', [
            'pager' => $pager,
            'sortOptions' => [
                'alphabetic' => 'Name',
                'lastUpdated' => 'Date modified',
                'nationality' => 'Nationality',
            ],
        ]);
    }

    /**
     * Show a single gallery artist.
     */
    public function showArtist(int $id): View
    {
        $artist = $this->service->getArtist($id);

        if (!$artist) {
            abort(404);
        }

        return view('openric-gallery::gallery.artist-show', [
            'artist' => $artist,
        ]);
    }

    /**
     * Show create form for a gallery artist.
     */
    public function createArtist(): View
    {
        $culture = app()->getLocale();
        $choices = $this->service->getFormChoices($culture);

        return view('openric-gallery::gallery.artist-create', [
            'artistTypes' => $choices['artistTypes'],
        ]);
    }

    /**
     * Store a new gallery artist.
     */
    public function storeArtist(Request $request): RedirectResponse
    {
        $request->validate([
            'display_name' => 'required|string|max:1024',
            'sort_name' => 'nullable|string|max:1024',
            'birth_date' => 'nullable|string|max:255',
            'birth_place' => 'nullable|string|max:1024',
            'death_date' => 'nullable|string|max:255',
            'death_place' => 'nullable|string|max:1024',
            'nationality' => 'nullable|string|max:255',
            'artist_type' => 'nullable|string|max:255',
            'medium_specialty' => 'nullable|string|max:1024',
            'movement_style' => 'nullable|string|max:1024',
            'active_period' => 'nullable|string|max:255',
            'represented' => 'nullable|string|max:1024',
            'biography' => 'nullable|string|max:65535',
            'artist_statement' => 'nullable|string|max:65535',
            'cv' => 'nullable|string|max:65535',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:1024',
            'studio_address' => 'nullable|string|max:1024',
            'instagram' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:65535',
            'is_active' => 'nullable|boolean',
        ]);

        $id = $this->service->createArtist($request->all());

        return redirect()
            ->route('gallery.artists.show', $id)
            ->with('success', 'Gallery artist created successfully.');
    }

    // =========================================================================
    // Dashboard
    // =========================================================================

    /**
     * Gallery dashboard.
     */
    public function dashboard(): View
    {
        $stats = $this->service->getDashboardStats();

        return view('openric-gallery::gallery.dashboard', $stats);
    }

    /**
     * Gallery index page.
     */
    public function galleryIndex(): View
    {
        return view('openric-gallery::gallery.index');
    }

    // =========================================================================
    // Loans
    // =========================================================================

    /**
     * Loans list.
     */
    public function loans(): View
    {
        $loans = Schema::hasTable('gallery_loan') ? DB::table('gallery_loan')->orderBy('created_at', 'desc')->get() : collect();

        return view('openric-gallery::gallery.loans', compact('loans'));
    }

    /**
     * Show a single loan.
     */
    public function showLoan(int $id): View
    {
        $loan = DB::table('gallery_loan')->where('id', $id)->first();
        if (!$loan) {
            abort(404);
        }

        return view('openric-gallery::gallery.view-loan', compact('loan'));
    }

    /**
     * Create loan form.
     */
    public function createLoan(): View
    {
        return view('openric-gallery::gallery.create-loan');
    }

    /**
     * Store a new loan.
     */
    public function storeLoan(Request $request): RedirectResponse
    {
        $request->validate(['title' => 'required|string|max:1024']);

        if (Schema::hasTable('gallery_loan')) {
            DB::table('gallery_loan')->insert(array_merge(
                $request->only(['title', 'loan_type', 'borrower_name', 'start_date', 'end_date', 'insurance_value', 'loan_fee', 'conditions', 'notes']),
                ['status' => 'pending', 'created_at' => now(), 'updated_at' => now()]
            ));
        }

        return redirect()->route('gallery.loans')->with('success', 'Loan created.');
    }

    // =========================================================================
    // Valuations
    // =========================================================================

    /**
     * Valuations list.
     */
    public function valuations(): View
    {
        $valuations = Schema::hasTable('gallery_valuation') ? DB::table('gallery_valuation')->orderBy('created_at', 'desc')->get() : collect();

        return view('openric-gallery::gallery.valuations', compact('valuations'));
    }

    /**
     * Show a single valuation.
     */
    public function showValuation(int $id): View
    {
        $valuation = DB::table('gallery_valuation')->where('id', $id)->first();
        if (!$valuation) {
            abort(404);
        }

        return view('openric-gallery::gallery.valuations', ['valuations' => collect([$valuation])]);
    }

    /**
     * Create valuation form.
     */
    public function createValuation(): View
    {
        return view('openric-gallery::gallery.create-valuation');
    }

    /**
     * Store a new valuation.
     */
    public function storeValuation(Request $request): RedirectResponse
    {
        $request->validate(['value' => 'required|numeric']);

        if (Schema::hasTable('gallery_valuation')) {
            DB::table('gallery_valuation')->insert(array_merge(
                $request->only(['valuation_type', 'value', 'valuation_date', 'appraiser', 'notes']),
                ['created_at' => now(), 'updated_at' => now()]
            ));
        }

        return redirect()->route('gallery.valuations')->with('success', 'Valuation created.');
    }

    // =========================================================================
    // Venues
    // =========================================================================

    /**
     * Venues list.
     */
    public function venues(): View
    {
        $venues = Schema::hasTable('gallery_venue') ? DB::table('gallery_venue')->orderBy('name')->get() : collect();

        return view('openric-gallery::gallery.venues', compact('venues'));
    }

    /**
     * Show a single venue.
     */
    public function showVenue(int $id): View
    {
        $venue = DB::table('gallery_venue')->where('id', $id)->first();
        if (!$venue) {
            abort(404);
        }

        return view('openric-gallery::gallery.view-venue', compact('venue'));
    }

    /**
     * Create venue form.
     */
    public function createVenue(): View
    {
        return view('openric-gallery::gallery.create-venue');
    }

    /**
     * Store a new venue.
     */
    public function storeVenue(Request $request): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:1024']);

        if (Schema::hasTable('gallery_venue')) {
            DB::table('gallery_venue')->insert(array_merge(
                $request->only(['name', 'venue_type', 'address', 'city', 'country', 'contact_person', 'email', 'notes']),
                ['created_at' => now(), 'updated_at' => now()]
            ));
        }

        return redirect()->route('gallery.venues')->with('success', 'Venue created.');
    }

    // =========================================================================
    // Facility Reports
    // =========================================================================

    /**
     * Facility Report detail.
     */
    public function facilityReport(int $id): View
    {
        $report = Schema::hasTable('gallery_facility_report') ? DB::table('gallery_facility_report')->where('id', $id)->first() : null;
        if (!$report) {
            abort(404);
        }

        return view('openric-gallery::gallery.facility-report', compact('report'));
    }

    // =========================================================================
    // Gallery Reports
    // =========================================================================

    /**
     * Gallery Reports Dashboard.
     */
    public function reportsIndex(): View
    {
        $stats = $this->service->getReportStats();

        return view('openric-gallery::galleryReports.index', compact('stats'));
    }

    /**
     * Exhibitions report.
     */
    public function reportsExhibitions(): View
    {
        $items = Schema::hasTable('gallery_exhibition') ? DB::table('gallery_exhibition')->orderBy('created_at', 'desc')->get() : collect();

        return view('openric-gallery::galleryReports.exhibitions', compact('items'));
    }

    /**
     * Facility reports report.
     */
    public function reportsFacilityReports(): View
    {
        $items = Schema::hasTable('gallery_facility_report') ? DB::table('gallery_facility_report')->orderBy('created_at', 'desc')->get() : collect();

        return view('openric-gallery::galleryReports.facility-reports', compact('items'));
    }

    /**
     * Loans report.
     */
    public function reportsLoans(): View
    {
        $items = Schema::hasTable('gallery_loan') ? DB::table('gallery_loan')->orderBy('created_at', 'desc')->get() : collect();

        return view('openric-gallery::galleryReports.loans', compact('items'));
    }

    /**
     * Spaces report.
     */
    public function reportsSpaces(): View
    {
        $items = Schema::hasTable('gallery_space') ? DB::table('gallery_space')->orderBy('name')->get() : collect();

        return view('openric-gallery::galleryReports.spaces', compact('items'));
    }

    /**
     * Valuations report.
     */
    public function reportsValuations(): View
    {
        $items = Schema::hasTable('gallery_valuation') ? DB::table('gallery_valuation')->orderBy('created_at', 'desc')->get() : collect();

        return view('openric-gallery::galleryReports.valuations', compact('items'));
    }
}
