<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenRiC\RecordManage\Contracts\ExtendedRightsServiceInterface;

/**
 * Extended rights, embargo, TK labels controller.
 * Adapted from Heratio ExtendedRightsController (279 lines).
 */
class ExtendedRightsController extends Controller
{
    public function __construct(
        private readonly ExtendedRightsServiceInterface $service,
    ) {}

    public function add(int $recordId): View
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        $rights          = $this->service->getRightsForRecord($recordId);
        $extendedRights  = $this->service->getExtendedRights($recordId);
        $currentRights   = $extendedRights->firstWhere('is_primary', true);
        $embargo         = $this->service->getActiveEmbargo($recordId);

        return view('record-manage::rights.extended', [
            'record'           => $record,
            'rights'           => $rights,
            'extendedRights'   => $extendedRights,
            'currentRights'    => $currentRights,
            'rightsStatements' => $this->service->getRightsStatements(),
            'ccLicenses'       => $this->service->getCreativeCommonsLicenses(),
            'tkLabels'         => $this->service->getTkLabels(),
            'donors'           => $this->service->getDonors(),
            'embargo'          => $embargo,
        ]);
    }

    public function store(Request $request, int $recordId): RedirectResponse
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        $request->validate([
            'rights_statement_id' => 'nullable|integer',
            'cc_license_id'       => 'nullable|integer',
            'rights_holder'       => 'nullable|string|max:255',
            'rights_note'         => 'nullable|string|max:10000',
            'usage_conditions'    => 'nullable|string|max:10000',
            'copyright_notice'    => 'nullable|string|max:10000',
            'tk_label_ids'        => 'nullable|array',
            'tk_label_ids.*'      => 'integer',
        ]);

        $data = [
            'rights_statement_id'         => $request->input('rights_statement_id'),
            'creative_commons_license_id' => $request->input('cc_license_id'),
            'rights_holder'               => $request->input('rights_holder'),
            'rights_note'                 => $request->input('rights_note'),
            'usage_conditions'            => $request->input('usage_conditions'),
            'copyright_notice'            => $request->input('copyright_notice'),
            'tk_label_ids'                => $request->input('tk_label_ids', []),
            'is_primary'                  => true,
        ];

        $existing = DB::table('extended_rights')
            ->where('record_id', $recordId)->where('is_primary', true)->first();

        if ($existing) {
            $this->service->updateExtendedRight($existing->id, $data, auth()->id());
            $message = 'Extended rights updated.';
        } else {
            $this->service->saveExtendedRight($recordId, $data, auth()->id());
            $message = 'Extended rights created.';
        }

        return redirect()->route('record.rights.extended', $recordId)->with('success', $message);
    }

    public function embargo(int $recordId): View
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        return view('record-manage::rights.embargo', [
            'record'          => $record,
            'activeEmbargo'   => $this->service->getActiveEmbargo($recordId),
            'embargoes'       => $this->service->getAllEmbargoes($recordId),
            'descendantCount' => $this->service->getDescendantCount($recordId),
        ]);
    }

    public function storeEmbargo(Request $request, int $recordId): RedirectResponse
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        $request->validate([
            'embargo_type' => 'required|string|max:50',
            'start_date'   => 'required|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'reason'       => 'nullable|string|max:5000',
            'is_perpetual' => 'nullable|boolean',
        ]);

        $data = [
            'record_id'    => $recordId,
            'embargo_type' => $request->input('embargo_type'),
            'start_date'   => $request->input('start_date'),
            'end_date'     => $request->input('end_date'),
            'reason'       => $request->input('reason'),
            'is_perpetual' => $request->boolean('is_perpetual'),
            'created_by'   => auth()->id(),
        ];

        $applyToChildren = $request->boolean('apply_to_children');

        if ($applyToChildren) {
            $results = $this->service->createEmbargoWithPropagation($data, true);
            $message = "Embargo created for {$results['created']} record(s).";
        } else {
            $this->service->createEmbargo($data);
            $message = 'Embargo created successfully.';
        }

        return redirect()->route('record.rights.embargo', $recordId)->with('success', $message);
    }

    public function liftEmbargo(Request $request, int $id): RedirectResponse
    {
        $embargo = DB::table('embargoes')->where('id', $id)->first();
        if (!$embargo) {
            abort(404);
        }

        $this->service->liftEmbargo($id, auth()->id() ?? 0, $request->input('lift_reason', ''));

        return redirect()->route('record.rights.embargo', $embargo->record_id)
            ->with('success', 'Embargo lifted successfully.');
    }

    public function exportJsonLd(int $recordId): JsonResponse
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        return response()->json($this->service->exportJsonLd($recordId), 200, [
            'Content-Type' => 'application/ld+json',
        ]);
    }
}
