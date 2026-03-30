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

    // =====================================================================
    //  Scan — start a new duplicate scan.
    //  Adapted from Heratio DedupeController::scan() and scanStart().
    // =====================================================================

    public function scan()
    {
        return view('openric-dedupe::scan');
    }

    public function scanStart(Request $request)
    {
        $entityType = $request->input('entity_type', 'RecordSet');
        $threshold = (float) $request->input('threshold', 0.7);
        $limit = (int) $request->input('limit', 100);

        if ($entityType === 'all') {
            $this->service->findDuplicates(['entityType' => 'RecordSet', 'threshold' => $threshold, 'limit' => $limit]);
            $this->service->findDuplicateAgents(['threshold' => $threshold, 'limit' => $limit]);
        } elseif ($entityType === 'Agent') {
            $this->service->findDuplicateAgents(['threshold' => $threshold, 'limit' => $limit]);
        } else {
            $this->service->findDuplicates(['entityType' => $entityType, 'threshold' => $threshold, 'limit' => $limit]);
        }

        return redirect()->route('dedupe.dashboard')
            ->with('success', 'Scan completed. Check the dashboard for results.');
    }

    // =====================================================================
    //  Rules — list, create, store, edit, update, delete detection rules.
    //  Adapted from Heratio DedupeController rule methods.
    // =====================================================================

    public function rules()
    {
        $rules = $this->service->getRules();
        return view('openric-dedupe::rules', ['rules' => $rules]);
    }

    public function ruleCreate()
    {
        return view('openric-dedupe::rule-create', ['ruleTypes' => $this->service->getRuleTypes()]);
    }

    public function ruleStore(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'rule_type' => 'required|string|max:50',
            'threshold' => 'required|numeric|min:0|max:1',
        ]);

        $this->service->storeRule([
            'name'        => $request->input('name'),
            'rule_type'   => $request->input('rule_type'),
            'threshold'   => (float) $request->input('threshold'),
            'priority'    => (int) $request->input('priority', 100),
            'config_json' => $request->input('config_json') ?: null,
            'is_enabled'  => $request->has('is_enabled') ? 1 : 0,
            'is_blocking' => $request->has('is_blocking') ? 1 : 0,
        ]);

        return redirect()->route('dedupe.rules')->with('success', 'Detection rule created.');
    }

    public function ruleEdit(int $id)
    {
        $rule = $this->service->getRule($id);
        if (!$rule) {
            abort(404);
        }
        return view('openric-dedupe::rule-edit', [
            'rule'      => $rule,
            'ruleTypes' => $this->service->getRuleTypes(),
        ]);
    }

    public function ruleUpdate(Request $request, int $id)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'rule_type' => 'required|string|max:50',
            'threshold' => 'required|numeric|min:0|max:1',
        ]);

        $this->service->updateRule($id, [
            'name'        => $request->input('name'),
            'rule_type'   => $request->input('rule_type'),
            'threshold'   => (float) $request->input('threshold'),
            'priority'    => (int) $request->input('priority', 100),
            'config_json' => $request->input('config_json') ?: null,
            'is_enabled'  => $request->has('is_enabled') ? 1 : 0,
            'is_blocking' => $request->has('is_blocking') ? 1 : 0,
        ]);

        return redirect()->route('dedupe.rules')->with('success', 'Detection rule updated.');
    }

    public function ruleDelete(int $id)
    {
        $this->service->deleteRule($id);
        return redirect()->route('dedupe.rules')->with('success', 'Detection rule deleted.');
    }

    // =====================================================================
    //  Report — monthly stats and method breakdown.
    //  Adapted from Heratio DedupeController::report().
    // =====================================================================

    public function report()
    {
        $reportData = $this->service->getReportData();

        return view('openric-dedupe::report', [
            'monthlyStats'    => $reportData['monthlyStats'] ?? [],
            'methodBreakdown' => $reportData['methodBreakdown'] ?? [],
            'efficiency'      => $reportData['efficiency'] ?? [],
            'topClusters'     => $reportData['topClusters'] ?? [],
        ]);
    }

    // =====================================================================
    //  API: Real-time duplicate check during data entry.
    //  Adapted from Heratio DedupeController::apiRealtime().
    // =====================================================================

    public function apiRealtime(Request $request)
    {
        $title = (string) $request->query('title', '');
        if (strlen($title) < 5) {
            return response()->json(['matches' => []]);
        }

        $matches = $this->service->realtimeCheck($title, 10);
        return response()->json(['matches' => $matches]);
    }

    // =====================================================================
    //  Authority-related views.
    //  Adapted from Heratio DedupeController authority endpoints.
    // =====================================================================

    public function config(Request $request)
    {
        return view('openric-dedupe::config', ['record' => (object) []]);
    }

    public function contact(int $id)
    {
        $record = \DB::table('agents')
            ->leftJoin('agent_i18n', 'agents.id', '=', 'agent_i18n.agent_id')
            ->where('agents.id', $id)
            ->first();
        return view('openric-dedupe::contact', ['record' => $record ?? (object) []]);
    }

    public function authorityDashboard()
    {
        $stats = $this->service->getAuthorityStats();
        return view('openric-dedupe::authority-dashboard', $stats);
    }

    public function functionBrowse(Request $request)
    {
        $params = $request->only(['page', 'limit', 'query']);
        $result = $this->service->browseFunctions($params);
        return view('openric-dedupe::function-browse', ['rows' => collect($result['hits'] ?? [])]);
    }

    public function functions(int $id)
    {
        $functions = $this->service->getAgentFunctions($id);
        return view('openric-dedupe::functions', ['rows' => collect($functions)]);
    }

    public function identifiers(Request $request)
    {
        $params = $request->only(['page', 'limit', 'query']);
        $result = $this->service->getAuthorityIdentifiers($params);
        return view('openric-dedupe::identifiers', ['rows' => collect($result['hits'] ?? [])]);
    }

    public function occupations(Request $request)
    {
        $params = $request->only(['page', 'limit', 'query']);
        $result = $this->service->getAuthorityOccupations($params);
        return view('openric-dedupe::occupations', ['rows' => collect($result['hits'] ?? [])]);
    }

    public function split(Request $request, int $id)
    {
        $authority = $this->service->getAuthority($id);
        return view('openric-dedupe::split', ['authority' => $authority ? (object) $authority : (object) []]);
    }

    public function workqueue(Request $request)
    {
        $params = $request->only(['page', 'limit', 'status']);
        $result = $this->service->getWorkQueue($params);
        return view('openric-dedupe::workqueue', ['rows' => collect($result['hits'] ?? [])]);
    }
}
