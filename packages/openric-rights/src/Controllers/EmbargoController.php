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
 * EmbargoController — standalone embargo management UI.
 *
 * Adapted from Heratio EmbargoController (143 lines) which manages embargoes
 * on AtoM information objects via the `embargo` table with object_id references.
 *
 * OpenRiC embargoes use entity_iri references to Fuseki entities and are stored
 * in the PostgreSQL `embargoes` table.
 */
class EmbargoController extends Controller
{
    protected RightsServiceInterface $service;

    public function __construct(RightsServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * List active and expiring embargoes.
     *
     * Adapted from Heratio EmbargoController::index() which queries the embargo
     * table for active and expiring-within-30-days records.
     */
    public function index(): View
    {
        $activeEmbargoes = DB::table('embargoes')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('embargo_end')
                  ->orWhere('embargo_end', '>=', now()->toDateString());
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $expiringEmbargoes = $this->service->getExpiringEmbargoes(30);

        return view('rights::embargo.index', compact('activeEmbargoes', 'expiringEmbargoes'));
    }

    /**
     * Show the add embargo form.
     *
     * Adapted from Heratio EmbargoController::create() which fetches the
     * information object and descendant count for hierarchy propagation.
     */
    public function add(Request $request): View
    {
        $entityIri = $request->input('entity_iri', '');

        return view('rights::embargo.add', compact('entityIri'));
    }

    /**
     * Store a new embargo.
     *
     * Adapted from Heratio EmbargoController::store() which validates embargo_type
     * and start_date, then inserts into the embargo table.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'entity_iri'    => 'required|string|max:2048',
            'embargo_start' => 'required|date',
            'reason'        => 'nullable|string',
            'public_message' => 'nullable|string',
            'notes'         => 'nullable|string',
        ]);

        $this->service->createEmbargo([
            'entity_iri'    => $request->input('entity_iri'),
            'embargo_start' => $request->input('embargo_start'),
            'embargo_end'   => $request->boolean('is_perpetual') ? null : $request->input('embargo_end'),
            'reason'        => $request->input('reason'),
        ]);

        return redirect()
            ->route('rights.embargo.index')
            ->with('success', 'Embargo created successfully.');
    }

    /**
     * Show embargo details.
     *
     * Adapted from Heratio EmbargoController::show() which fetches a single
     * embargo with status, exceptions, and audit log.
     */
    public function view(int $id): View
    {
        $embargo = DB::table('embargoes')->where('id', $id)->first();

        if (!$embargo) {
            abort(404, 'Embargo not found.');
        }

        $auditLog = DB::table('audit_log')
            ->where('entity_type', 'Embargo')
            ->where('entity_id', (string) $id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->toArray();

        return view('rights::embargo.view', [
            'embargo'  => (array) $embargo,
            'auditLog' => $auditLog,
        ]);
    }

    /**
     * Show the lift embargo form.
     *
     * Adapted from Heratio EmbargoController::liftForm() which fetches the
     * embargo and its related resource for confirmation.
     */
    public function liftForm(int $id): View
    {
        $embargo = DB::table('embargoes')->where('id', $id)->first();

        if (!$embargo) {
            abort(404, 'Embargo not found.');
        }

        return view('rights::embargo.lift', compact('embargo'));
    }

    /**
     * Lift an embargo.
     *
     * Adapted from Heratio EmbargoController::lift() which sets is_active=false,
     * records lift_reason, lifted_at, and lifted_by.
     */
    public function lift(Request $request, int $id): RedirectResponse
    {
        $this->service->liftEmbargo(
            $id,
            (int) auth()->id(),
            $request->input('lift_reason')
        );

        return redirect()
            ->route('rights.embargo.index')
            ->with('success', 'Embargo lifted successfully.');
    }
}
