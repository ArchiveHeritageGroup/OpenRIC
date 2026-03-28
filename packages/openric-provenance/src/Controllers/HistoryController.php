<?php

declare(strict_types=1);

namespace OpenRiC\Provenance\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Provenance\Contracts\DescriptionHistoryServiceInterface;
use OpenRiC\Provenance\Contracts\ProvenanceServiceInterface;

class HistoryController extends Controller
{
    public function __construct(
        private readonly DescriptionHistoryServiceInterface $historyService,
        private readonly ProvenanceServiceInterface $provenanceService,
    ) {}

    public function show(string $iri): View
    {
        $history = $this->historyService->getHistory($iri);
        $descriptionRecords = $this->historyService->getDescriptionRecords($iri);

        return view('provenance::history', [
            'iri' => $iri,
            'history' => $history,
            'descriptionRecords' => $descriptionRecords,
        ]);
    }

    public function provenance(string $iri): View
    {
        $timeline = $this->provenanceService->getTimeline($iri);
        $custodyChain = $this->provenanceService->getCustodyChain($iri);
        $activityTypes = $this->provenanceService->getActivityTypes();

        return view('provenance::provenance', [
            'iri' => $iri,
            'timeline' => $timeline,
            'custodyChain' => $custodyChain,
            'activityTypes' => $activityTypes,
        ]);
    }
}
