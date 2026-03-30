<?php

declare(strict_types=1);

namespace OpenRiC\Research\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * AuditController -- Audit trail management.
 *
 * Adapted from Heratio AhgResearch\Controllers\AuditController.
 * PostgreSQL ILIKE used for all text searches.
 */
class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('audit_log as a')
            ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
            ->select('a.*', 'u.name as user_name');

        if ($request->filled('table')) {
            $query->where('a.entity_type', $request->input('table'));
        }
        if ($request->filled('form_action')) {
            $query->where('a.action', $request->input('form_action'));
        }
        if ($request->filled('from_date')) {
            $query->where('a.created_at', '>=', $request->input('from_date') . ' 00:00:00');
        }
        if ($request->filled('to_date')) {
            $query->where('a.created_at', '<=', $request->input('to_date') . ' 23:59:59');
        }
        if ($request->filled('q')) {
            $search = '%' . $request->input('q') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('a.old_values', 'ILIKE', $search)
                  ->orWhere('a.new_values', 'ILIKE', $search);
            });
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 50;

        try {
            $totalCount = $query->count();
            $totalPages = max(1, (int) ceil($totalCount / $perPage));

            $logs = $query
                ->orderBy('a.created_at', 'desc')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->toArray();

            $tables = DB::table('audit_log')
                ->select('entity_type')
                ->distinct()
                ->orderBy('entity_type')
                ->pluck('entity_type')
                ->toArray();

            $stats = [
                'total'   => $totalCount,
                'today'   => (int) DB::table('audit_log')->whereDate('created_at', date('Y-m-d'))->count(),
                'creates' => (int) DB::table('audit_log')->where('action', 'create')->count(),
                'updates' => (int) DB::table('audit_log')->where('action', 'update')->count(),
                'deletes' => (int) DB::table('audit_log')->where('action', 'delete')->count(),
            ];
        } catch (\Exception $e) {
            $totalCount = 0;
            $totalPages = 1;
            $logs = [];
            $tables = [];
            $stats = ['total' => 0, 'today' => 0, 'creates' => 0, 'updates' => 0, 'deletes' => 0];
        }

        return view('research::audit.index', compact(
            'logs', 'tables', 'stats', 'totalCount', 'totalPages', 'page'
        ) + ['currentPage' => $page]);
    }

    public function view(int $id): View
    {
        try {
            $entry = DB::table('audit_log as a')
                ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
                ->select('a.*', 'u.name as user_name')
                ->where('a.id', $id)
                ->first();

            $changes = [];
            if ($entry && $entry->old_values && $entry->new_values) {
                $oldVals = json_decode($entry->old_values, true) ?: [];
                $newVals = json_decode($entry->new_values, true) ?: [];
                $allKeys = array_unique(array_merge(array_keys($oldVals), array_keys($newVals)));
                foreach ($allKeys as $key) {
                    $oldVal = $oldVals[$key] ?? null;
                    $newVal = $newVals[$key] ?? null;
                    if ($oldVal !== $newVal) {
                        $changes[$key] = ['old' => $oldVal, 'new' => $newVal];
                    }
                }
            }
        } catch (\Exception $e) {
            $entry = (object) [
                'id' => $id, 'action' => '', 'entity_type' => '', 'entity_id' => '',
                'created_at' => '', 'user_name' => 'System', 'old_values' => null,
                'new_values' => null, 'ip_address' => null,
            ];
            $changes = [];
        }

        return view('research::audit.view', compact('entry', 'changes'));
    }

    public function record(string $tableName, int $recordId): View
    {
        try {
            $history = DB::table('audit_log as a')
                ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
                ->select('a.*', 'u.name as user_name')
                ->where('a.entity_type', $tableName)
                ->where('a.entity_id', (string) $recordId)
                ->orderBy('a.created_at', 'desc')
                ->get();

            $timeline = [];
            foreach ($history as $entry) {
                $date = date('Y-m-d', strtotime($entry->created_at));
                $timeline[$date][] = $entry;
            }
        } catch (\Exception $e) {
            $history = collect();
            $timeline = [];
        }

        return view('research::audit.record', compact('tableName', 'recordId', 'history', 'timeline'));
    }

    public function user(int $userId): View
    {
        try {
            $user = DB::table('users')->where('id', $userId)->first();
            if (!$user) {
                $user = (object) ['id' => $userId, 'name' => 'Unknown'];
            }

            $tableStats = DB::table('audit_log')
                ->where('user_id', $userId)
                ->select('entity_type', DB::raw('COUNT(*) as count'))
                ->groupBy('entity_type')
                ->orderByDesc('count')
                ->get();

            $actionStats = DB::table('audit_log')
                ->where('user_id', $userId)
                ->select('action', DB::raw('COUNT(*) as count'))
                ->groupBy('action')
                ->get();

            $totalCount = (int) DB::table('audit_log')->where('user_id', $userId)->count();

            $activity = DB::table('audit_log')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
        } catch (\Exception $e) {
            $user = (object) ['id' => $userId, 'name' => 'Unknown'];
            $tableStats = collect();
            $actionStats = collect();
            $totalCount = 0;
            $activity = collect();
        }

        return view('research::audit.user', compact('user', 'tableStats', 'actionStats', 'totalCount', 'activity'));
    }
}
