<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenRiC\RecordManage\Contracts\ConditionServiceInterface;

/**
 * Condition assessment controller.
 * Adapted from Heratio ConditionController (207 lines).
 */
class ConditionController extends Controller
{
    public function __construct(
        private readonly ConditionServiceInterface $service,
    ) {}

    /**
     * List condition reports for a record.
     */
    public function index(int $recordId): View
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        $reports = $this->service->getReportsForRecord($recordId);
        $latest  = $this->service->getLatestReport($recordId);

        return view('record-manage::condition.index', [
            'record'  => $record,
            'reports' => $reports,
            'latest'  => $latest,
        ]);
    }

    /**
     * Show the create-report form.
     */
    public function create(int $recordId): View
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        return view('record-manage::condition.create', [
            'record'          => $record,
            'ratingOptions'   => $this->service->getRatingOptions(),
            'contextOptions'  => $this->service->getContextOptions(),
            'priorityOptions' => $this->service->getPriorityOptions(),
            'damageTypes'     => $this->service->getDamageTypeOptions(),
            'severityOptions' => $this->service->getSeverityOptions(),
        ]);
    }

    /**
     * Store a new condition report.
     */
    public function store(Request $request, int $recordId): RedirectResponse
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        $validated = $request->validate([
            'assessment_date'     => 'required|date',
            'overall_rating'      => 'required|string|max:47',
            'context'             => 'nullable|string|max:121',
            'summary'             => 'nullable|string',
            'recommendations'     => 'nullable|string',
            'priority'            => 'nullable|string|max:32',
            'next_check_date'     => 'nullable|date',
            'environmental_notes' => 'nullable|string',
            'handling_notes'      => 'nullable|string',
            'display_notes'       => 'nullable|string',
            'storage_notes'       => 'nullable|string',
        ]);

        $validated['record_id'] = $recordId;
        $validated['assessor_user_id'] = auth()->id();

        $reportId = $this->service->createReport($validated);

        $damageTypes = $request->input('damage_type', []);
        foreach ($damageTypes as $i => $type) {
            if (empty($type)) {
                continue;
            }
            $this->service->addDamage($reportId, [
                'damage_type'        => $type,
                'location'           => $request->input('damage_location.' . $i, 'overall'),
                'severity'           => $request->input('damage_severity.' . $i, 'minor'),
                'description'        => $request->input('damage_description.' . $i),
                'dimensions'         => $request->input('damage_dimensions.' . $i),
                'is_active'          => true,
                'treatment_required' => $request->has('damage_treatment_required.' . $i),
                'treatment_notes'    => $request->input('damage_treatment_notes.' . $i),
            ]);
        }

        return redirect()->route('record.condition.index', $recordId)
            ->with('success', 'Condition report created successfully.');
    }

    /**
     * Show a single condition report.
     */
    public function show(int $reportId): View
    {
        $report = $this->service->getReport($reportId);
        if (!$report) {
            abort(404);
        }

        $record = $this->getRecord($report->record_id);

        return view('record-manage::condition.show', [
            'record' => $record,
            'report' => $report,
        ]);
    }

    private function getRecord(int $id): ?object
    {
        return DB::table('records')->where('id', $id)->first();
    }
}
