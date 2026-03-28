<?php

declare(strict_types=1);

namespace OpenRiC\Dedupe\Controllers;

use OpenRiC\Dedupe\Contracts\DedupeServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Dedupe controller — adapted from Heratio DedupeController (741 lines).
 *
 * Heratio combines service logic and controller logic in one file, with
 * MySQL queries inline. OpenRiC delegates all logic to DedupeServiceInterface.
 *
 * Heratio actions: index (dashboard), browse, compare, dismiss, rules,
 * scan, scanStart, merge, mergeExecute, ruleCreate/Store/Edit/Update/Delete,
 * report, apiRealtime.
 *
 * OpenRiC actions: dashboard, records, agents, compare, merge, resolve.
 */
class DedupeController extends Controller
{
    private DedupeServiceInterface $service;

    public function __construct(DedupeServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Dashboard — stats, top pending, recent activity.
     *
     * Adapted from Heratio DedupeController::index() which queries
     * ahg_duplicate_detection for counts, top pending, recent scans,
     * and method breakdown.
     */
    public function dashboard()
    {
        $stats = $this->service->getStats();

        $topPending = $this->service->getPendingDuplicates([
            'status' => 'pending',
            'limit'  => 10,
            'page'   => 1,
        ]);

        return view('openric-dedupe::dashboard', [
            'stats'      => $stats,
            'topPending' => $topPending['hits'],
        ]);
    }

    /**
     * Browse duplicate record candidates with filters.
     *
     * Adapted from Heratio DedupeController::browse() which paginates
     * ahg_duplicate_detection with status, method, and score filters.
     */
    public function records(Request $request)
    {
        $result = $this->service->getPendingDuplicates([
            'page'       => (int) $request->get('page', 1),
            'limit'      => (int) $request->get('limit', 25),
            'status'     => $request->get('status', ''),
            'entityType' => $request->get('entityType', 'RecordSet'),
            'minScore'   => (float) $request->get('minScore', 0),
        ]);

        return view('openric-dedupe::records', [
            'hits'    => $result['hits'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'limit'   => $result['limit'],
            'filters' => $request->only(['status', 'entityType', 'minScore']),
            'statusOptions' => [
                ''              => 'All',
                'pending'       => 'Pending',
                'merged'        => 'Merged',
                'not_duplicate' => 'Not Duplicate',
            ],
        ]);
    }

    /**
     * Browse duplicate agent candidates with filters.
     */
    public function agents(Request $request)
    {
        $result = $this->service->getPendingDuplicates([
            'page'       => (int) $request->get('page', 1),
            'limit'      => (int) $request->get('limit', 25),
            'status'     => $request->get('status', ''),
            'entityType' => 'Agent',
            'minScore'   => (float) $request->get('minScore', 0),
        ]);

        return view('openric-dedupe::agents', [
            'hits'    => $result['hits'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'limit'   => $result['limit'],
            'filters' => $request->only(['status', 'minScore']),
            'statusOptions' => [
                ''              => 'All',
                'pending'       => 'Pending',
                'merged'        => 'Merged',
                'not_duplicate' => 'Not Duplicate',
            ],
        ]);
    }

    /**
     * Compare two entities side-by-side.
     *
     * Adapted from Heratio DedupeController::compare() which queries
     * information_object + i18n + actor_i18n + term_i18n for both records
     * and builds a field-by-field comparison.
     */
    public function compare(Request $request, int $id)
    {
        $candidate = \DB::table('duplicate_candidates')->where('id', $id)->first();

        if ($candidate === null) {
            abort(404, 'Duplicate candidate not found');
        }

        $comparison = $this->service->comparePair(
            $candidate->entity_a_iri,
            $candidate->entity_b_iri
        );

        return view('openric-dedupe::compare', [
            'candidate'  => (array) $candidate,
            'entityA'    => $comparison['entityA'],
            'entityB'    => $comparison['entityB'],
            'comparison' => $comparison['comparison'],
            'score'      => $comparison['similarityScore'],
        ]);
    }

    /**
     * Merge two records — show merge confirmation form.
     *
     * Adapted from Heratio DedupeController::merge() which shows
     * both records with a "choose primary" selection.
     */
    public function merge(Request $request, int $id)
    {
        $candidate = \DB::table('duplicate_candidates')->where('id', $id)->first();

        if ($candidate === null) {
            abort(404, 'Duplicate candidate not found');
        }

        if ($request->isMethod('POST')) {
            $request->validate([
                'canonical_iri' => 'required|string',
            ]);

            $canonicalIri = $request->input('canonical_iri');
            $duplicateIri = ($canonicalIri === $candidate->entity_a_iri)
                ? $candidate->entity_b_iri
                : $candidate->entity_a_iri;

            $userId = (int) auth()->id();

            $this->service->resolveDuplicate($id, 'merged', $userId);

            return redirect()
                ->route('dedupe.records')
                ->with('success', 'Records merged successfully. Canonical: ' . $canonicalIri);
        }

        $comparison = $this->service->comparePair(
            $candidate->entity_a_iri,
            $candidate->entity_b_iri
        );

        return view('openric-dedupe::merge', [
            'candidate'  => (array) $candidate,
            'entityA'    => $comparison['entityA'],
            'entityB'    => $comparison['entityB'],
            'comparison' => $comparison['comparison'],
        ]);
    }

    /**
     * Resolve a duplicate candidate (dismiss or merge via AJAX).
     *
     * Adapted from Heratio DedupeController::dismiss() which updates
     * ahg_duplicate_detection status via AJAX.
     */
    public function resolve(Request $request, int $id)
    {
        $request->validate([
            'resolution' => 'required|string|in:not_duplicate,merged',
        ]);

        $userId = (int) auth()->id();
        $resolution = $request->input('resolution');

        $this->service->resolveDuplicate($id, $resolution, $userId);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $resolution === 'merged'
                    ? 'Records merged successfully.'
                    : 'Marked as not duplicate.',
            ]);
        }

        return redirect()
            ->route('dedupe.records')
            ->with('success', $resolution === 'merged'
                ? 'Records merged successfully.'
                : 'Marked as not duplicate.');
    }
}
