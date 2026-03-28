<?php

declare(strict_types=1);

namespace OpenRiC\Core\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Core\Contracts\StandardsMappingServiceInterface;

class MappingController extends Controller
{
    public function __construct(
        private readonly StandardsMappingServiceInterface $mappingService,
    ) {}

    public function index(): View
    {
        return view('openric-core::mappings.index', [
            'isadg' => $this->mappingService->getIsadgMapping(),
            'isaarCpf' => $this->mappingService->getIsaarCpfMapping(),
        ]);
    }
}
