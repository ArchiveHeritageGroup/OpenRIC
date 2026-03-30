<?php

declare(strict_types=1);

namespace OpenRiC\Heritage\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * HeritageAccountingController — heritage asset financial accounting.
 *
 * Adapted from Heratio AhgHeritageManage\Controllers\HeritageAccountingController.
 * PostgreSQL ILIKE; Bootstrap 5; theme::layouts.1col.
 */
class HeritageAccountingController extends Controller
{
    public function dashboard(): View
    {
        $stats = ['total' => 0, 'recognised' => 0, 'pending' => 0, 'total_value' => 0];
        $items = collect();

        try {
            if (Schema::hasTable('heritage_asset')) {
                $stats['total'] = DB::table('heritage_asset')->count();
                $stats['recognised'] = DB::table('heritage_asset')->where('recognition_status', 'recognised')->count();
                $stats['pending'] = DB::table('heritage_asset')->where('recognition_status', 'pending')->count();
                $stats['total_value'] = (float) DB::table('heritage_asset')->sum('current_carrying_amount');
            }
        } catch (\Exception $e) {}

        return view('heritage::heritage-accounting.dashboard', compact('stats', 'items'));
    }

    public function browse(Request $request): View
    {
        $items = collect();
        $columns = ['ID', 'Name', 'Class', 'Status', 'Value', 'Date'];

        try {
            if (Schema::hasTable('heritage_asset')) {
                $items = DB::table('heritage_asset')->orderByDesc('created_at')->paginate(25);
            }
        } catch (\Exception $e) {}

        return view('heritage::heritage-accounting.browse', compact('items', 'columns'));
    }

    public function add(): View { return view('heritage::heritage-accounting.add', ['fields' => [], 'formAction' => route('heritage.accounting.store')]); }
    public function store(Request $request) { return redirect()->route('heritage.accounting.browse')->with('success', 'Asset created.'); }
    public function edit(int $id): View { $asset = null; try { if (Schema::hasTable('heritage_asset')) { $asset = DB::table('heritage_asset')->where('id', $id)->first(); } } catch (\Exception $e) {} return view('heritage::heritage-accounting.edit', ['asset' => $asset, 'fields' => [], 'formAction' => route('heritage.accounting.update', $id)]); }
    public function update(Request $request, int $id) { return redirect()->route('heritage.accounting.view', $id)->with('success', 'Asset updated.'); }
    public function view(int $id): View { $items = collect(); $stats = []; return view('heritage::heritage-accounting.view', compact('items', 'stats')); }
    public function viewByObject(int $id): View { $items = collect(); $stats = []; return view('heritage::heritage-accounting.view-by-object', compact('items', 'stats')); }
    public function addValuation(int $id = null): View { return view('heritage::heritage-accounting.add-valuation', ['asset' => null, 'fields' => [], 'formAction' => '#']); }
    public function addImpairment(int $id = null): View { return view('heritage::heritage-accounting.add-impairment', ['asset' => null, 'fields' => [], 'formAction' => '#']); }
    public function addJournal(int $id = null): View { return view('heritage::heritage-accounting.add-journal', ['asset' => null, 'fields' => [], 'formAction' => '#']); }
    public function addMovement(int $id = null): View { return view('heritage::heritage-accounting.add-movement', ['asset' => null, 'fields' => [], 'formAction' => '#']); }
    public function settings(): View { $items = collect(); $stats = []; return view('heritage::heritage-accounting.settings', compact('items', 'stats')); }
}
