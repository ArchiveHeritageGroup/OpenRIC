<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Spectrum controller — condition checks, valuations, locations, heritage assets.
 * Adapted from Heratio SpectrumController (243 lines).
 */
class SpectrumController extends Controller
{
    public function index(int $recordId): View
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        $conditionChecks = collect();
        try {
            $conditionChecks = DB::table('spectrum_condition_checks')
                ->where('record_id', $recordId)
                ->orderBy('check_date', 'desc')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            // table may not exist
        }

        $valuations = collect();
        try {
            $valuations = DB::table('spectrum_valuations')
                ->where('record_id', $recordId)
                ->orderBy('valuation_date', 'desc')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            // table may not exist
        }

        $locations = collect();
        try {
            $locations = DB::table('spectrum_locations')
                ->where('record_id', $recordId)
                ->orderBy('is_current', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            // table may not exist
        }

        return view('record-manage::spectrum.index', [
            'record'          => $record,
            'conditionChecks' => $conditionChecks,
            'valuations'      => $valuations,
            'locations'       => $locations,
        ]);
    }

    public function heritage(int $recordId): View
    {
        $record = DB::table('records')->where('id', $recordId)->first();
        if (!$record) {
            abort(404);
        }

        $currentValuation = null;
        $valuationHistory = collect();
        $grapAsset = null;
        $grapData = null;
        $grapDepreciation = collect();
        $grapRevaluations = collect();

        try {
            $currentValuation = DB::table('spectrum_valuations')
                ->where('record_id', $recordId)->where('is_current', true)->first();
            $valuationHistory = DB::table('spectrum_valuations')
                ->where('record_id', $recordId)->orderBy('valuation_date', 'desc')->get();
        } catch (\Illuminate\Database\QueryException $e) {
            // table may not exist
        }

        try {
            $grapAsset = DB::table('grap_heritage_assets')
                ->where('record_id', $recordId)->first();
            $grapData = DB::table('spectrum_grap_data')
                ->where('record_id', $recordId)->first();
            if ($grapData) {
                $grapDepreciation = DB::table('spectrum_grap_depreciation_schedules')
                    ->where('grap_data_id', $grapData->id)->orderBy('fiscal_year', 'desc')->get();
                $grapRevaluations = DB::table('spectrum_grap_revaluation_histories')
                    ->where('grap_data_id', $grapData->id)->orderBy('revaluation_date', 'desc')->get();
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // tables may not exist
        }

        return view('record-manage::spectrum.heritage', [
            'record'            => $record,
            'currentValuation'  => $currentValuation,
            'valuationHistory'  => $valuationHistory,
            'grapAsset'         => $grapAsset,
            'grapData'          => $grapData,
            'grapDepreciation'  => $grapDepreciation,
            'grapRevaluations'  => $grapRevaluations,
        ]);
    }
}
