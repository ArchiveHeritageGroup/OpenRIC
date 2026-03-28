<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\RecordManage\Contracts\HierarchyServiceInterface;

class HierarchyController extends Controller
{
    public function __construct(
        private readonly HierarchyServiceInterface $hierarchyService,
    ) {}

    public function index(): View
    {
        $roots = $this->hierarchyService->getRoots();

        return view('record-manage::hierarchy.index', [
            'roots' => $roots,
        ]);
    }

    public function children(Request $request): \Illuminate\Http\JsonResponse
    {
        $parentIri = $request->get('iri', '');

        if ($parentIri === '') {
            return response()->json([]);
        }

        $children = $this->hierarchyService->getChildren($parentIri);

        return response()->json($children);
    }

    public function tree(string $iri): View
    {
        $tree = $this->hierarchyService->getTree($iri, 4);
        $ancestors = $this->hierarchyService->getAncestors($iri);

        return view('record-manage::hierarchy.tree', [
            'tree' => $tree,
            'ancestors' => $ancestors,
        ]);
    }
}
