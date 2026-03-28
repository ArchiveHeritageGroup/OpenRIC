<?php

declare(strict_types=1);

namespace OpenRiC\Integrity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenRiC\Integrity\Contracts\IntegrityServiceInterface;

/**
 * Integrity controller -- adapted from Heratio AhgIntegrity\Controllers\IntegrityController (173 lines).
 *
 * Provides a dashboard for running integrity checks, viewing results, and monitoring stats.
 */
class IntegrityController extends Controller
{
    public function __construct(
        private readonly IntegrityServiceInterface $service,
    ) {}

    /**
     * Dashboard: show current stats and most recent results.
     */
    public function dashboard(Request $request): \Illuminate\Contracts\View\View|JsonResponse
    {
        $stats   = $this->service->getStats();
        $results = $this->service->getResults();

        if ($request->expectsJson()) {
            return response()->json([
                'stats'   => $stats,
                'results' => $results,
            ]);
        }

        return view('openric-integrity::dashboard', [
            'stats'   => $stats,
            'results' => $results,
        ]);
    }

    /**
     * Run a new integrity check (POST).
     */
    public function runCheck(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
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
    public function results(Request $request, ?string $runId = null): \Illuminate\Contracts\View\View|JsonResponse
    {
        $results = $this->service->getResults($runId);

        if (!$results) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No results found. Run an integrity check first.'], 404);
            }

            return view('openric-integrity::results', ['results' => null]);
        }

        if ($request->expectsJson()) {
            return response()->json($results);
        }

        return view('openric-integrity::results', ['results' => $results]);
    }
}
