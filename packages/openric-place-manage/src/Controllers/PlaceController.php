<?php

declare(strict_types=1);

namespace OpenRiC\PlaceManage\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\PlaceManage\Contracts\PlaceServiceInterface;

class PlaceController extends Controller
{
    public function __construct(
        private readonly PlaceServiceInterface $service,
    ) {}

    public function index(Request $request): View
    {
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);
        $filters = $request->only(['q', 'place_type', 'country_code', 'parent_iri']);
        $result = $this->service->browse($filters, $limit, $offset);

        $placeTypes = $this->service->getPlaceTypes();

        return view('place-manage::places.index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
            'filters' => $filters,
            'placeTypes' => $placeTypes,
        ]);
    }

    public function show(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        return view('place-manage::places.show', [
            'entity' => $entity,
            'viewMode' => session('openric_view_mode', config('openric.default_view', 'ric')),
        ]);
    }

    public function create(): View
    {
        $placeTypes = $this->service->getPlaceTypes();

        return view('place-manage::places.create', [
            'placeTypes' => $placeTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'            => 'required|string|max:1000',
            'identifier'       => 'nullable|string|max:255',
            'place_type'       => 'nullable|string|max:2048',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'parent_place'     => 'nullable|string|max:2048',
            'descriptive_note' => 'nullable|string|max:5000',
            'alternate_names'  => 'nullable|array',
            'alternate_names.*' => 'string|max:1000',
            'jurisdiction_of'  => 'nullable|array',
            'jurisdiction_of.*' => 'string|max:2048',
            'location_of'     => 'nullable|array',
            'location_of.*'    => 'string|max:2048',
            'country_code'     => 'nullable|string|max:3',
            'postal_code'      => 'nullable|string|max:20',
        ]);

        $user = Auth::user();
        $iri = $this->service->create($data, $user->getIri(), 'Created via OpenRiC UI');

        return redirect()
            ->route('places.show', ['iri' => urlencode($iri)])
            ->with('success', 'Place created.');
    }

    public function edit(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        $placeTypes = $this->service->getPlaceTypes();

        return view('place-manage::places.edit', [
            'entity' => $entity,
            'placeTypes' => $placeTypes,
        ]);
    }

    public function update(Request $request, string $iri): RedirectResponse
    {
        $data = $request->validate([
            'title'            => 'required|string|max:1000',
            'identifier'       => 'nullable|string|max:255',
            'place_type'       => 'nullable|string|max:2048',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'parent_place'     => 'nullable|string|max:2048',
            'descriptive_note' => 'nullable|string|max:5000',
            'alternate_names'  => 'nullable|array',
            'alternate_names.*' => 'string|max:1000',
            'jurisdiction_of'  => 'nullable|array',
            'jurisdiction_of.*' => 'string|max:2048',
            'location_of'     => 'nullable|array',
            'location_of.*'    => 'string|max:2048',
            'country_code'     => 'nullable|string|max:3',
            'postal_code'      => 'nullable|string|max:20',
        ]);

        $user = Auth::user();
        $this->service->update($iri, $data, $user->getIri(), 'Updated via OpenRiC UI');

        return redirect()
            ->route('places.show', ['iri' => urlencode($iri)])
            ->with('success', 'Place updated.');
    }

    public function destroy(string $iri): RedirectResponse
    {
        $user = Auth::user();
        $this->service->delete($iri, $user->getIri(), 'Deleted via OpenRiC UI');

        return redirect()
            ->route('places.index')
            ->with('success', 'Place deleted.');
    }

    public function children(string $iri): View
    {
        $entity = $this->service->find($iri);
        if ($entity === null) {
            abort(404);
        }

        $children = $this->service->getChildren($iri);

        return view('place-manage::places.children', [
            'entity' => $entity,
            'children' => $children,
        ]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $query = (string) $request->get('q', '');
        $limit = (int) $request->get('limit', 10);

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $results = $this->service->autocomplete($query, $limit);

        return response()->json($results);
    }
}
