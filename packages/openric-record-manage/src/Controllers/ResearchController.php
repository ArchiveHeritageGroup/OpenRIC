<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Research controller — source assessment, annotations, trust score, citations.
 * Adapted from Heratio ResearchController (127 lines).
 */
class ResearchController extends Controller
{
    public function sourceAssessment(int $recordId): View
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        return view('record-manage::research.assessment', ['record' => $record]);
    }

    public function annotations(int $recordId): View
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        return view('record-manage::research.annotations', ['record' => $record]);
    }

    public function trustScore(int $recordId): View
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        return view('record-manage::research.trust', ['record' => $record]);
    }

    public function dashboard(): View
    {
        return view('record-manage::research.dashboard');
    }

    public function citation(int $recordId): View
    {
        $record = $this->getRecord($recordId);
        if (!$record) {
            abort(404);
        }

        $creators = collect();
        try {
            $creators = DB::table('record_agents')
                ->where('record_id', $recordId)
                ->where('relation_type', 'creator')
                ->select('agent_name as name')
                ->get();
        } catch (\Exception $e) {
            // table may not exist yet
        }

        $dates = collect();
        try {
            $dates = DB::table('record_events')
                ->where('record_id', $recordId)
                ->whereNotNull('date_display')
                ->select('date_display')
                ->first();
        } catch (\Exception $e) {
            // table may not exist yet
        }

        return view('record-manage::research.citation', [
            'record'   => $record,
            'creators' => $creators,
            'dates'    => $dates,
        ]);
    }

    private function getRecord(int $id): ?object
    {
        return DB::table('records')->where('id', $id)->first();
    }
}
