<?php

declare(strict_types=1);

namespace OpenRiC\Search\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Search\Contracts\FacetedBrowseServiceInterface;

class BrowseController extends Controller
{
    public function __construct(
        private readonly FacetedBrowseServiceInterface $browseService,
    ) {}

    public function index(Request $request): View
    {
        try {
            $result = $this->browseService->browse($request->all());
        } catch (\Exception) {
            $result = ['items' => [], 'total' => 0, 'facets' => ['entity_types' => [], 'creators' => []], 'page' => 1, 'limit' => 25];
        }

        return view('search::browse', [
            'items' => $result['items'],
            'total' => $result['total'],
            'facets' => $result['facets'],
            'page' => $result['page'],
            'limit' => $result['limit'],
            'params' => $request->all(),
        ]);
    }
}
