<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Core\Contracts\StandardsMappingServiceInterface;
use OpenRiC\RecordManage\Contracts\HierarchyServiceInterface;
use OpenRiC\RecordManage\Contracts\RecordSetServiceInterface;

class FindingAidController extends Controller
{
    public function __construct(
        private readonly RecordSetServiceInterface $recordSetService,
        private readonly HierarchyServiceInterface $hierarchyService,
        private readonly StandardsMappingServiceInterface $mappingService,
    ) {}

    public function print(string $iri): View
    {
        $entity = $this->recordSetService->find($iri);

        if ($entity === null) {
            abort(404);
        }

        $isadg = $this->mappingService->renderIsadG($entity['properties'] ?? []);
        $tree = $this->hierarchyService->getTree($iri, 5);
        $creators = $this->recordSetService->getCreators($iri);

        return view('record-manage::finding-aid.print', [
            'entity' => $entity,
            'isadg' => $isadg,
            'tree' => $tree,
            'creators' => $creators,
        ]);
    }
}
