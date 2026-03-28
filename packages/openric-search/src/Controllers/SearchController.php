<?php

declare(strict_types=1);

namespace OpenRiC\Search\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Search\Contracts\SearchServiceInterface;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchServiceInterface $searchService,
    ) {}

    public function index(Request $request): View
    {
        $query = $request->get('q', '');
        $items = [];
        $total = 0;

        if ($query !== '') {
            $result = $this->searchService->search(
                $query,
                $request->only(['entity_type']),
                (int) $request->get('limit', 25),
                (int) $request->get('offset', 0)
            );
            $items = $result['items'];
            $total = $result['total'];
        }

        return view('search::index', [
            'query' => $query,
            'items' => $items,
            'total' => $total,
        ]);
    }

    public function suggest(Request $request)
    {
        $query = $request->get('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $suggestions = $this->searchService->suggest($query);

        return response()->json($suggestions);
    }
}
