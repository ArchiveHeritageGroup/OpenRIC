<?php

declare(strict_types=1);

namespace OpenRiC\Heritage\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * GrapComplianceController — GRAP 103 heritage asset compliance.
 *
 * Adapted from Heratio AhgHeritageManage\Controllers\GrapComplianceController.
 */
class GrapComplianceController extends Controller
{
    public function dashboard(): View
    {
        $stats = ['total' => 0];
        $items = collect();

        try {
            if (Schema::hasTable('heritage_asset')) {
                $stats['total'] = DB::table('heritage_asset')->count();
            }
        } catch (\Exception $e) {}

        return view('heritage::grap-compliance.dashboard', compact('stats', 'items'));
    }

    public function batchCheck(): View { return view('heritage::grap-compliance.batch-check', ['stats' => [], 'items' => collect()]); }
    public function check(int $id = null): View { return view('heritage::grap-compliance.check', ['stats' => [], 'items' => collect()]); }
    public function nationalTreasuryReport(): View { return view('heritage::grap-compliance.national-treasury-report', ['stats' => [], 'items' => collect()]); }
}
