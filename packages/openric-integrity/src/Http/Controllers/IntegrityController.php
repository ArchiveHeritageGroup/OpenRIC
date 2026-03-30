<?php

declare(strict_types=1);

namespace OpenRiC\Integrity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use OpenRiC\Integrity\Contracts\IntegrityServiceInterface;

/**
 * Integrity controller -- adapted from Heratio AhgIntegrity\Controllers\IntegrityController (173 lines).
 *
 * Provides dashboard, run checks, results, alerts, dead-letter, disposition,
 * export, holds, ledger, policies, report, runs, schedules.
 */
class IntegrityController extends Controller
{
    public function __construct(
        private readonly IntegrityServiceInterface $service,
    ) {}

    /**
     * Dashboard: show current stats and most recent results.
     */
    public function dashboard(Request $request): View|JsonResponse
    {
        $stats   = $this->service->getStats();
        $results = $this->service->getResults();

        if ($request->expectsJson()) {
            return response()->json(['stats' => $stats, 'results' => $results]);
        }

        return view('openric-integrity::dashboard', [
            'stats'   => $stats,
            'results' => $results,
        ]);
    }

    /**
     * Run a new integrity check (POST).
     */
    public function runCheck(Request $request): JsonResponse|RedirectResponse
    {
        $results = $this->service->runChecks();

        if ($request->expectsJson()) {
            return response()->json($results);
        }

        return redirect()->route('integrity.dashboard')
            ->with('success', 'Integrity check completed.');
    }

    /**
     * View results of a specific run, or the most recent run.
     */
    public function results(Request $request, ?string $runId = null): View|JsonResponse
    {
        $results = $this->service->getResults($runId);

        if ($request->expectsJson()) {
            if (!$results) {
                return response()->json(['error' => 'No results found.'], 404);
            }
            return response()->json($results);
        }

        return view('openric-integrity::results', ['results' => $results]);
    }

    // =========================================================================
    // Sub-pages adapted from Heratio IntegrityController lines 158-172
    // =========================================================================

    public function alerts(): View
    {
        $alerts = Schema::hasTable('integrity_alerts') ? DB::table('integrity_alerts')->orderByDesc('created_at')->limit(100)->get() : collect();
        return view('openric-integrity::integrity.alerts', compact('alerts'));
    }

    public function deadLetter(): View
    {
        $deadLetters = Schema::hasTable('integrity_dead_letters') ? DB::table('integrity_dead_letters')->orderByDesc('created_at')->limit(100)->get() : collect();
        return view('openric-integrity::integrity.dead-letter', compact('deadLetters'));
    }

    public function disposition(): View
    {
        $dispositions = Schema::hasTable('integrity_dispositions') ? DB::table('integrity_dispositions')->orderByDesc('created_at')->get() : collect();
        return view('openric-integrity::integrity.disposition', compact('dispositions'));
    }

    public function export(): View
    {
        return view('openric-integrity::integrity.export');
    }

    public function holds(): View
    {
        $holds = Schema::hasTable('integrity_holds') ? DB::table('integrity_holds')->orderByDesc('created_at')->get() : collect();
        return view('openric-integrity::integrity.holds', compact('holds'));
    }

    public function ledger(): View
    {
        $items = Schema::hasTable('integrity_ledger') ? DB::table('integrity_ledger')->orderByDesc('verified_at')->limit(100)->get() : collect();
        return view('openric-integrity::integrity.ledger', compact('items'));
    }

    public function policies(): View
    {
        $items = Schema::hasTable('integrity_policies') ? DB::table('integrity_policies')->orderBy('name')->get() : collect();
        return view('openric-integrity::integrity.policies', compact('items'));
    }

    public function policyEdit(int $id): View
    {
        $policy = Schema::hasTable('integrity_policies') ? DB::table('integrity_policies')->where('id', $id)->first() : null;
        if (!$policy) {
            abort(404);
        }
        return view('openric-integrity::integrity.policy-edit', compact('policy'));
    }

    public function policyUpdate(Request $request, int $id): RedirectResponse
    {
        if (Schema::hasTable('integrity_policies')) {
            DB::table('integrity_policies')->where('id', $id)->update(
                $request->only(['name', 'description', 'frequency']) + [
                    'is_active'  => $request->boolean('is_active'),
                    'updated_at' => now(),
                ]
            );
        }
        return redirect()->route('integrity.policies')->with('success', 'Policy updated.');
    }

    public function report(): View
    {
        $items = collect();
        return view('openric-integrity::integrity.report', compact('items'));
    }

    public function runs(): View
    {
        // Show in-memory runs from the service
        $allResults = [];
        $latest = $this->service->getResults();
        if ($latest) {
            $allResults[$latest['run_id']] = $latest;
        }
        return view('openric-integrity::integrity.runs', ['items' => $allResults]);
    }

    public function runDetail(string $runId): View
    {
        $run = $this->service->getResults($runId);
        return view('openric-integrity::integrity.run-detail', compact('run'));
    }

    public function schedules(): View
    {
        $items = Schema::hasTable('integrity_schedules') ? DB::table('integrity_schedules')->orderBy('name')->get() : collect();
        return view('openric-integrity::integrity.schedules', compact('items'));
    }

    public function scheduleEdit(int $id): View
    {
        $schedule = Schema::hasTable('integrity_schedules') ? DB::table('integrity_schedules')->where('id', $id)->first() : null;
        if (!$schedule) {
            abort(404);
        }
        return view('openric-integrity::integrity.schedule-edit', compact('schedule'));
    }

    public function scheduleUpdate(Request $request, int $id): RedirectResponse
    {
        if (Schema::hasTable('integrity_schedules')) {
            DB::table('integrity_schedules')->where('id', $id)->update(
                $request->only(['name', 'cron_expression']) + [
                    'is_active'  => $request->boolean('is_active'),
                    'updated_at' => now(),
                ]
            );
        }
        return redirect()->route('integrity.schedules')->with('success', 'Schedule updated.');
    }
}
