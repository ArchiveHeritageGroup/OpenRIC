<?php

declare(strict_types=1);

namespace OpenRiC\Rights\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenRiC\Rights\Contracts\RightsServiceInterface;

/**
 * RightsAdminController — admin-only rights management UI.
 *
 * Adapted from Heratio RightsAdminController (133 lines) which provides
 * admin interfaces for embargoes, orphan works, rights reports, statements,
 * and TK labels management.
 *
 * OpenRiC stores all data in PostgreSQL: rights_statements, embargoes,
 * tk_labels, orphan_works tables.
 */
class RightsAdminController extends Controller
{
    protected RightsServiceInterface $service;

    public function __construct(RightsServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Admin dashboard with stats.
     *
     * Adapted from Heratio RightsAdminController::index() which computes
     * total_rights, active_embargoes, orphan_works counts.
     */
    public function index(): View
    {
        $stats = $this->service->getRightsStats();

        $stats['orphan_works'] = (int) DB::table('orphan_works')->count();

        return view('rights::rightsAdmin.index', compact('stats'));
    }

    /**
     * List all embargoes for admin management.
     *
     * Adapted from Heratio RightsAdminController::embargoes() which joins
     * embargo with information_object_i18n for titles.
     */
    public function embargoes(): View
    {
        $embargoes = DB::table('embargoes')
            ->orderByDesc('created_at')
            ->get();

        return view('rights::rightsAdmin.embargoes', compact('embargoes'));
    }

    /**
     * Edit an embargo.
     *
     * Adapted from Heratio RightsAdminController::embargoEdit().
     */
    public function embargoEdit(int $id): View
    {
        $embargo = DB::table('embargoes')->where('id', $id)->first();

        if (!$embargo) {
            abort(404, 'Embargo not found.');
        }

        return view('rights::rightsAdmin.embargo-edit', compact('embargo'));
    }

    /**
     * Update an embargo.
     *
     * Adapted from Heratio RightsAdminController::embargoUpdate() which
     * updates embargo_type, dates, reason, and status.
     */
    public function embargoUpdate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'embargo_start' => 'required|date',
        ]);

        DB::table('embargoes')->where('id', $id)->update([
            'embargo_start' => $request->input('embargo_start'),
            'embargo_end'   => $request->input('embargo_end'),
            'reason'        => $request->input('reason'),
            'status'        => $request->input('status', 'active'),
            'updated_at'    => now(),
        ]);

        return redirect()
            ->route('rights.admin.embargoes')
            ->with('success', 'Embargo updated.');
    }

    /**
     * List orphan works.
     *
     * Adapted from Heratio RightsAdminController::orphanWorks() which queries
     * the orphan_work table.
     */
    public function orphanWorks(): View
    {
        $orphanWorks = DB::table('orphan_works')
            ->orderByDesc('created_at')
            ->get();

        return view('rights::rightsAdmin.orphan-works', compact('orphanWorks'));
    }

    /**
     * Edit an orphan work record.
     *
     * Adapted from Heratio RightsAdminController::orphanWorkEdit().
     */
    public function orphanWorkEdit(int $id): View
    {
        $orphanWork = DB::table('orphan_works')->where('id', $id)->first();

        if (!$orphanWork) {
            abort(404, 'Orphan work not found.');
        }

        return view('rights::rightsAdmin.orphan-work-edit', compact('orphanWork'));
    }

    /**
     * Update an orphan work record.
     *
     * Adapted from Heratio RightsAdminController::orphanWorkUpdate().
     */
    public function orphanWorkUpdate(Request $request, int $id): RedirectResponse
    {
        DB::table('orphan_works')->where('id', $id)->update([
            'designation_date' => $request->input('designation_date'),
            'search_status'    => $request->input('search_status'),
            'search_notes'     => $request->input('search_notes'),
            'updated_at'       => now(),
        ]);

        return redirect()
            ->route('rights.admin.orphan-works')
            ->with('success', 'Orphan work updated.');
    }

    /**
     * Rights coverage report.
     *
     * Adapted from Heratio RightsAdminController::report() which computes
     * coverage percentages and breaks down by basis and holder.
     */
    public function report(): View
    {
        $totalStatements = (int) DB::table('rights_statements')->count();

        $byBasis = DB::table('rights_statements')
            ->selectRaw('rights_basis, COUNT(*) as count')
            ->groupBy('rights_basis')
            ->orderByDesc('count')
            ->get()
            ->toArray();

        $byHolder = DB::table('rights_statements')
            ->whereNotNull('rights_holder_name')
            ->where('rights_holder_name', '!=', '')
            ->selectRaw('rights_holder_name as name, COUNT(*) as count')
            ->groupBy('rights_holder_name')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->toArray();

        $stats = $this->service->getRightsStats();

        return view('rights::rightsAdmin.report', compact('stats', 'byBasis', 'byHolder', 'totalStatements'));
    }

    /**
     * Manage rights statements configuration.
     *
     * Adapted from Heratio RightsAdminController::statements() which lists
     * all rights_statement records.
     */
    public function statements(): View
    {
        $statements = DB::table('rights_statements')
            ->orderByDesc('created_at')
            ->get();

        return view('rights::rightsAdmin.statements', compact('statements'));
    }

    /**
     * Manage TK Labels configuration.
     *
     * Adapted from Heratio RightsAdminController::tkLabels() which lists
     * all tk_label records.
     */
    public function tkLabels(): View
    {
        $tkLabels = DB::table('tk_labels')
            ->orderBy('label_type')
            ->get();

        return view('rights::rightsAdmin.tk-labels', compact('tkLabels'));
    }
}
