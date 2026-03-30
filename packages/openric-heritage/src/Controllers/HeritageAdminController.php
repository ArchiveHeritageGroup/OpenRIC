<?php

declare(strict_types=1);

namespace OpenRiC\Heritage\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * HeritageAdminController — heritage accounting standards, rules, regions.
 *
 * Adapted from Heratio AhgHeritageManage\Controllers\HeritageAdminController.
 */
class HeritageAdminController extends Controller
{
    public function index(): View { return view('heritage::heritage-admin.index', ['items' => collect()]); }
    public function regions(): View { $items = collect(); try { if (Schema::hasTable('heritage_region')) { $items = DB::table('heritage_region')->orderBy('name')->get(); } } catch (\Exception $e) {} return view('heritage::heritage-admin.regions', ['items' => $items]); }
    public function regionInfo(int $id): View { return view('heritage::heritage-admin.region-info', ['items' => collect()]); }
    public function ruleList(): View { $items = collect(); try { if (Schema::hasTable('heritage_rule')) { $items = DB::table('heritage_rule')->orderBy('name')->get(); } } catch (\Exception $e) {} return view('heritage::heritage-admin.rule-list', ['items' => $items]); }
    public function ruleAdd(): View { return view('heritage::heritage-admin.rule-add', ['item' => null, 'formAction' => '#']); }
    public function ruleEdit(int $id): View { $item = null; try { if (Schema::hasTable('heritage_rule')) { $item = DB::table('heritage_rule')->where('id', $id)->first(); } } catch (\Exception $e) {} return view('heritage::heritage-admin.rule-edit', ['item' => $item, 'formAction' => '#']); }
    public function standardList(): View { $items = collect(); try { if (Schema::hasTable('heritage_standard')) { $items = DB::table('heritage_standard')->orderBy('name')->get(); } } catch (\Exception $e) {} return view('heritage::heritage-admin.standard-list', ['items' => $items]); }
    public function standardAdd(): View { return view('heritage::heritage-admin.standard-add', ['item' => null, 'formAction' => '#']); }
    public function standardEdit(int $id): View { $item = null; try { if (Schema::hasTable('heritage_standard')) { $item = DB::table('heritage_standard')->where('id', $id)->first(); } } catch (\Exception $e) {} return view('heritage::heritage-admin.standard-edit', ['item' => $item, 'formAction' => '#']); }
}
