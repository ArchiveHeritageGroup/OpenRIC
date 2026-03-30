<?php

declare(strict_types=1);

namespace OpenRiC\Heritage\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * HeritageReportController — heritage asset reports.
 *
 * Adapted from Heratio AhgHeritageManage\Controllers\HeritageReportController.
 */
class HeritageReportController extends Controller
{
    public function index(): View { return view('heritage::heritage-report.index', ['items' => collect()]); }
    public function assetRegister(): View { $items = collect(); try { if (Schema::hasTable('heritage_asset')) { $items = DB::table('heritage_asset')->orderByDesc('created_at')->paginate(25); } } catch (\Exception $e) {} return view('heritage::heritage-report.asset-register', compact('items')); }
    public function movement(): View { $items = collect(); try { if (Schema::hasTable('heritage_asset_movement')) { $items = DB::table('heritage_asset_movement')->orderByDesc('created_at')->paginate(25); } } catch (\Exception $e) {} return view('heritage::heritage-report.movement', compact('items')); }
    public function valuation(): View { $items = collect(); try { if (Schema::hasTable('heritage_asset_valuation')) { $items = DB::table('heritage_asset_valuation')->orderByDesc('created_at')->paginate(25); } } catch (\Exception $e) {} return view('heritage::heritage-report.valuation', compact('items')); }
}
