<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenRiC\RecordManage\Contracts\PreservationServiceInterface;

/**
 * Preservation controller — AIP packages and PREMIS objects.
 * Adapted from Heratio PreservationController (61 lines).
 */
class PreservationController extends Controller
{
    public function __construct(
        private readonly PreservationServiceInterface $service,
    ) {}

    public function index(int $recordId): View
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        $aips          = $this->service->getAipsForRecord($recordId);
        $premisObjects = $this->service->getPremisObjects($recordId);

        return view('record-manage::preservation.index', [
            'record'        => $record,
            'aips'          => $aips,
            'premisObjects' => $premisObjects,
        ]);
    }
}
