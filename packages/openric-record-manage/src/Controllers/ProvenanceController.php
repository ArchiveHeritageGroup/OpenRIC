<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenRiC\RecordManage\Contracts\ProvenanceServiceInterface;

/**
 * Provenance controller — chain management and timeline.
 * Adapted from Heratio ProvenanceController (203 lines).
 */
class ProvenanceController extends Controller
{
    public function __construct(
        private readonly ProvenanceServiceInterface $service,
    ) {}

    public function index(int $recordId): View
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        $events = $this->service->getChain($recordId);

        return view('record-manage::provenance.index', [
            'record' => $record,
            'events' => $events,
        ]);
    }

    public function timeline(int $recordId): View
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        $events       = $this->service->getChain($recordId);
        $timelineData = $this->service->getTimelineData($recordId);

        return view('record-manage::provenance.timeline', [
            'record'       => $record,
            'events'       => $events,
            'timelineData' => $timelineData,
        ]);
    }

    public function store(Request $request, int $recordId): RedirectResponse
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        $validated = $request->validate([
            'owner_name'           => 'required|string|max:500',
            'owner_type'           => 'nullable|string|max:97',
            'owner_agent_id'       => 'nullable|integer',
            'owner_location'       => 'nullable|string|max:255',
            'start_date'           => 'nullable|string|max:50',
            'start_date_qualifier' => 'nullable|string|max:31',
            'end_date'             => 'nullable|string|max:50',
            'end_date_qualifier'   => 'nullable|string|max:31',
            'transfer_type'        => 'nullable|string|max:123',
            'transfer_details'     => 'nullable|string',
            'sale_price'           => 'nullable|numeric',
            'sale_currency'        => 'nullable|string|max:10',
            'auction_house'        => 'nullable|string|max:255',
            'auction_lot'          => 'nullable|string|max:50',
            'certainty'            => 'nullable|string|max:53',
            'sources'              => 'nullable|string',
            'notes'                => 'nullable|string',
            'is_gap'               => 'nullable|boolean',
            'gap_explanation'      => 'nullable|string',
        ]);

        $validated['record_id'] = $recordId;
        $this->service->createEntry($validated);

        return redirect()->route('record.provenance.index', $recordId)
            ->with('success', 'Provenance entry added.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $entry = $this->service->getEntry($id);
        if (!$entry) {
            abort(404);
        }

        $validated = $request->validate([
            'owner_name'           => 'required|string|max:500',
            'owner_type'           => 'nullable|string|max:97',
            'transfer_type'        => 'nullable|string|max:123',
            'transfer_details'     => 'nullable|string',
            'start_date'           => 'nullable|string|max:50',
            'end_date'             => 'nullable|string|max:50',
            'certainty'            => 'nullable|string|max:53',
            'notes'                => 'nullable|string',
        ]);

        $this->service->updateEntry($id, $validated);

        return redirect()->route('record.provenance.index', $entry->record_id)
            ->with('success', 'Provenance entry updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $entry = $this->service->getEntry($id);
        if (!$entry) {
            abort(404);
        }

        $this->service->deleteEntry($id);

        return redirect()->route('record.provenance.index', $entry->record_id)
            ->with('success', 'Provenance entry deleted.');
    }

    private function getRecord(int $id): ?object
    {
        return DB::table('records')->where('id', $id)->first();
    }
}
