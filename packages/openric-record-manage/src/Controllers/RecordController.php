<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\Core\Contracts\StandardsMappingServiceInterface;
use OpenRiC\RecordManage\Contracts\RecordServiceInterface;
use OpenRiC\RecordManage\Requests\StoreRecordRequest;

/**
 * Record controller — adapted from Heratio InformationObjectController (3,054 lines).
 *
 * All Heratio IO controller actions mapped:
 *   1.  index       — Browse/list records with filters, sorting, pagination, display modes
 *   2.  show        — View record detail (RiC or traditional view)
 *   3.  create      — Create form (RiC or ISAD(G) form)
 *   4.  store       — Store new record (POST)
 *   5.  edit        — Edit form (RiC or ISAD(G) form)
 *   6.  update      — Update record (PUT)
 *   7.  destroy     — Delete record (DELETE)
 *   8.  children    — Get direct children (for hierarchy/tree)
 *   9.  ancestors   — Get ancestor chain (breadcrumb)
 *  10.  autocomplete — Search suggestions (JSON)
 *  11.  print       — Printable view
 *  12.  move        — Move record to new parent
 *  13.  duplicate   — Duplicate a record
 *  14.  status      — Change publication status
 *  15.  inventory   — List/grid view of children
 *
 * Heratio differences: uses slug routing, MySQL. OpenRiC uses IRI routing, Fuseki SPARQL.
 */
class RecordController extends Controller
{
    public function __construct(
        private readonly RecordServiceInterface $service,
        private readonly StandardsMappingServiceInterface $mappingService,
    ) {}

    /**
     * #1 — Browse/list records with filters, sorting, pagination.
     */
    public function index(Request $request): View
    {
        $page = max(1, (int) $request->input('page', 1));
        $limit = (int) $request->input('limit', 25);
        $offset = ($page - 1) * $limit;

        $filters = $request->only([
            'q', 'level', 'parent_iri', 'creator_iri',
            'date_from', 'date_to', 'sort', 'direction',
        ]);

        $result = $this->service->browse($filters, $limit, $offset);
        $totalPages = $limit > 0 ? (int) ceil($result['total'] / $limit) : 1;

        $displayMode = $request->input('display', session('openric_display_mode', 'list'));

        return view('record-manage::record.index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'offset' => $offset,
            'facets' => $result['facets'] ?? [],
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'displayMode' => $displayMode,
        ]);
    }

    /**
     * #2 — View record detail (RiC-native or traditional ISAD(G) lens).
     */
    public function show(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404, 'Record not found');
        }

        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));

        if ($viewMode === 'traditional') {
            $isadg = $this->mappingService->renderIsadG($entity['properties'] ?? []);

            return view('record-manage::record.show-traditional', [
                'entity' => $entity,
                'isadg' => $isadg,
                'viewMode' => $viewMode,
                'children' => $entity['children'] ?? [],
                'ancestors' => $entity['ancestors'] ?? [],
                'childCount' => $this->service->getChildCount($iri),
            ]);
        }

        return view('record-manage::record.show', [
            'entity' => $entity,
            'viewMode' => $viewMode,
            'children' => $entity['children'] ?? [],
            'ancestors' => $entity['ancestors'] ?? [],
            'childCount' => $this->service->getChildCount($iri),
        ]);
    }

    /**
     * #3 — Create form (RiC or ISAD(G) form).
     */
    public function create(Request $request): View
    {
        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));
        $parentIri = $request->input('parent');

        $parent = $parentIri ? $this->service->find($parentIri) : null;

        if ($viewMode === 'traditional') {
            return view('record-manage::record.create-traditional', [
                'parent' => $parent,
                'parentIri' => $parentIri,
            ]);
        }

        return view('record-manage::record.create', [
            'parent' => $parent,
            'parentIri' => $parentIri,
        ]);
    }

    /**
     * #4 — Store new record (POST).
     */
    public function store(StoreRecordRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        // Handle ISAD(G) form → RiC-O property mapping
        if ($request->input('_form_type') === 'isadg') {
            $data = array_merge($data, ['_rico_properties' => $this->mappingService->isadgToRico($data)]);
        }

        $iri = $this->service->create($data, $user->getIri(), 'Created via OpenRiC UI');

        return redirect()->route('records.show', ['iri' => urlencode($iri)])
            ->with('success', 'Record created successfully.');
    }

    /**
     * #5 — Edit form.
     */
    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404, 'Record not found');
        }

        $viewMode = session('openric_view_mode', config('openric.default_view', 'ric'));

        if ($viewMode === 'traditional') {
            $isadg = $this->mappingService->renderIsadG($entity['properties'] ?? []);

            return view('record-manage::record.edit-traditional', [
                'entity' => $entity,
                'isadg' => $isadg,
            ]);
        }

        return view('record-manage::record.edit', ['entity' => $entity]);
    }

    /**
     * #6 — Update record (PUT).
     */
    public function update(StoreRecordRequest $request, string $iri): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        if ($request->input('_form_type') === 'isadg') {
            $data = array_merge($data, ['_rico_properties' => $this->mappingService->isadgToRico($data)]);
        }

        $this->service->update($iri, $data, $user->getIri(), 'Updated via OpenRiC UI');

        return redirect()->route('records.show', ['iri' => urlencode($iri)])
            ->with('success', 'Record updated successfully.');
    }

    /**
     * #7 — Delete record (DELETE).
     */
    public function destroy(string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->delete($iri, $user->getIri(), 'Deleted via OpenRiC UI');

        return redirect()->route('records.index')->with('success', 'Record deleted successfully.');
    }

    /**
     * #8 — Get direct children for a record (AJAX or page).
     */
    public function children(Request $request, string $iri): View|JsonResponse
    {
        $children = $this->service->getChildren($iri);

        if ($request->wantsJson()) {
            return response()->json(['children' => $children]);
        }

        $entity = $this->service->find($iri);

        return view('record-manage::record.children', [
            'entity' => $entity,
            'children' => $children,
        ]);
    }

    /**
     * #9 — Get ancestor chain (breadcrumb) for a record.
     */
    public function ancestors(string $iri): JsonResponse
    {
        $ancestors = $this->service->getAncestors($iri);

        return response()->json(['ancestors' => $ancestors]);
    }

    /**
     * #10 — Autocomplete search (JSON response).
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = $this->service->autocomplete($query, 10);

        return response()->json($results);
    }

    /**
     * #11 — Printable view of a record.
     */
    public function print(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404, 'Record not found');
        }

        $isadg = $this->mappingService->renderIsadG($entity['properties'] ?? []);
        $children = $entity['children'] ?? [];
        $ancestors = $entity['ancestors'] ?? [];

        return view('record-manage::record.print', [
            'entity' => $entity,
            'isadg' => $isadg,
            'children' => $children,
            'ancestors' => $ancestors,
        ]);
    }

    /**
     * #12 — Move record to a new parent (POST).
     */
    public function move(Request $request, string $iri): RedirectResponse
    {
        $request->validate([
            'new_parent_iri' => 'required|string|max:2048',
        ]);

        $user = Auth::user();
        $newParentIri = $request->input('new_parent_iri');

        // Update the parent relationship
        $this->service->update($iri, ['parent_iri' => $newParentIri], $user->getIri(), 'Moved to new parent');

        return redirect()->route('records.show', ['iri' => urlencode($iri)])
            ->with('success', 'Record moved successfully.');
    }

    /**
     * #13 — Duplicate a record (POST).
     */
    public function duplicate(string $iri): RedirectResponse
    {
        $user = Auth::user();
        $original = $this->service->find($iri);

        if ($original === null) {
            abort(404, 'Record not found');
        }

        // Extract properties and create a copy
        $properties = $original['properties'] ?? [];
        $data = [];

        // Map RiC-O properties back to form fields
        $reverseMap = array_flip(array_map(fn ($m) => $m['property'], RecordServiceInterface::FIELD_MAP ?? []));

        foreach ($properties as $prop => $value) {
            $field = $reverseMap[$prop] ?? null;
            if ($field) {
                $data[$field] = is_array($value) ? ($value['value'] ?? $value[0]['value'] ?? '') : $value;
            }
        }

        // Modify title to indicate copy
        $data['title'] = ($data['title'] ?? 'Untitled') . ' [Copy]';

        $newIri = $this->service->create($data, $user->getIri(), 'Duplicated from: ' . $iri);

        return redirect()->route('records.show', ['iri' => urlencode($newIri)])
            ->with('success', 'Record duplicated successfully.');
    }

    /**
     * #14 — Change publication status (POST).
     */
    public function status(Request $request, string $iri): RedirectResponse
    {
        $request->validate([
            'status' => 'required|string|in:draft,published,unpublished',
        ]);

        $user = Auth::user();
        $this->service->update(
            $iri,
            ['publication_status' => $request->input('status')],
            $user->getIri(),
            'Publication status changed to: ' . $request->input('status')
        );

        return redirect()->route('records.show', ['iri' => urlencode($iri)])
            ->with('success', 'Publication status updated.');
    }

    /**
     * #15 — Inventory view (children list with detail).
     */
    public function inventory(string $iri, Request $request): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404, 'Record not found');
        }

        $children = $this->service->getChildren($iri, 500);
        $displayMode = $request->input('display', 'list');

        return view('record-manage::record.inventory', [
            'entity' => $entity,
            'children' => $children,
            'displayMode' => $displayMode,
        ]);
    }
}
