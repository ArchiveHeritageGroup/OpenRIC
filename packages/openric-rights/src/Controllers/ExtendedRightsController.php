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
 * ExtendedRightsController — extended rights management (rights statements,
 * CC licenses, TK labels, embargoes, batch operations, export).
 *
 * Adapted from Heratio ExtendedRightsController (209 lines) which manages
 * extended_rights, rights_statement, creative_commons_license, tk_label,
 * and embargo tables via AtoM's object_id references.
 *
 * OpenRiC uses entity_iri references and PostgreSQL tables:
 * rights_statements, embargoes, tk_labels.
 */
class ExtendedRightsController extends Controller
{
    protected RightsServiceInterface $service;

    public function __construct(RightsServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Extended rights overview — rights statements, CC licenses, TK labels.
     *
     * Adapted from Heratio ExtendedRightsController::index() which loads
     * rights_statement, creative_commons_license, tk_label tables and stats.
     */
    public function index(): View
    {
        $stats = $this->service->getRightsStats();

        return view('rights::extendedRights.index', compact('stats'));
    }

    /**
     * Extended rights dashboard with statistics.
     *
     * Adapted from Heratio ExtendedRightsController::dashboard() which
     * computes coverage statistics across all objects.
     */
    public function dashboard(): View
    {
        $stats = $this->service->getRightsStats();

        return view('rights::extendedRights.dashboard', compact('stats'));
    }

    /**
     * View extended rights for a specific entity.
     *
     * Adapted from Heratio ExtendedRightsController::view() which fetches
     * primary extended_rights record for an object_id.
     */
    public function view(Request $request): View
    {
        $entityIri = $request->input('entity_iri', '');

        $statements = $entityIri ? $this->service->getRightsForEntity($entityIri) : [];
        $embargoes = $entityIri ? $this->service->getEmbargoes($entityIri) : [];
        $tkLabels = $entityIri ? $this->service->getTkLabels($entityIri) : [];

        return view('rights::extendedRights.view', compact(
            'entityIri', 'statements', 'embargoes', 'tkLabels'
        ));
    }

    /**
     * Batch assign rights to multiple entities.
     *
     * Adapted from Heratio ExtendedRightsController::batch() which loads
     * top-level records, rights statements, CC licenses, TK labels, and donors
     * for a batch assignment form.
     */
    public function batch(): View
    {
        $stats = $this->service->getRightsStats();

        return view('rights::extendedRights.batch', compact('stats'));
    }

    /**
     * Store batch rights assignment.
     *
     * Adapted from Heratio ExtendedRightsController::batchStore().
     */
    public function batchStore(Request $request): RedirectResponse
    {
        $request->validate([
            'entity_iris'  => 'required|array|min:1',
            'rights_basis' => 'required|string',
        ]);

        foreach ($request->input('entity_iris', []) as $entityIri) {
            $this->service->createRightsStatement([
                'entity_iri'  => $entityIri,
                'rights_basis' => $request->input('rights_basis'),
                'terms'        => $request->input('terms'),
                'notes'        => $request->input('notes'),
            ]);
        }

        return redirect()
            ->route('rights.extended.dashboard')
            ->with('success', 'Batch rights assignment completed.');
    }

    /**
     * Clear extended rights for a specific entity.
     *
     * Adapted from Heratio ExtendedRightsController::clear().
     */
    public function clear(Request $request): View
    {
        $entityIri = $request->input('entity_iri', '');

        $currentRights = $entityIri ? $this->service->getRightsForEntity($entityIri) : [];

        return view('rights::extendedRights.clear', compact('entityIri', 'currentRights'));
    }

    /**
     * Process clear extended rights.
     *
     * Adapted from Heratio ExtendedRightsController::clearStore().
     */
    public function clearStore(Request $request): RedirectResponse
    {
        $entityIri = $request->input('entity_iri', '');

        if ($entityIri !== '') {
            $statements = $this->service->getRightsForEntity($entityIri);
            foreach ($statements as $stmt) {
                $this->service->deleteRightsStatement($stmt->id);
            }
        }

        return redirect()
            ->back()
            ->with('success', 'Extended rights cleared.');
    }

    /**
     * Show embargo blocked page (public-safe).
     *
     * Adapted from Heratio ExtendedRightsController::embargoBlocked().
     */
    public function embargoBlocked(): View
    {
        return view('rights::extendedRights.embargo-blocked');
    }

    /**
     * Show embargo status for an entity.
     *
     * Adapted from Heratio ExtendedRightsController::embargoStatus().
     */
    public function embargoStatus(Request $request): View
    {
        $entityIri = $request->input('entity_iri', '');
        $embargo = $entityIri ? $this->service->getActiveEmbargo($entityIri) : null;

        return view('rights::extendedRights.embargo-status', compact('entityIri', 'embargo'));
    }

    /**
     * List active embargoes.
     *
     * Adapted from Heratio ExtendedRightsController::embargoes().
     */
    public function embargoes(): View
    {
        $embargoes = DB::table('embargoes')
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->get();

        return view('rights::extendedRights.embargoes', compact('embargoes'));
    }

    /**
     * List embargoes expiring within N days.
     *
     * Adapted from Heratio ExtendedRightsController::expiringEmbargoes().
     */
    public function expiringEmbargoes(Request $request): View
    {
        $days = max(1, (int) $request->input('days', 30));
        $embargoes = $this->service->getExpiringEmbargoes($days);

        return view('rights::extendedRights.expiring-embargoes', compact('embargoes', 'days'));
    }

    /**
     * Export rights data.
     *
     * Adapted from Heratio ExtendedRightsController::export().
     */
    public function export(): View
    {
        $stats = $this->service->getRightsStats();

        return view('rights::extendedRights.export', compact('stats'));
    }

    /**
     * Lift an embargo from the extended rights interface.
     *
     * Adapted from Heratio ExtendedRightsController::liftEmbargo().
     */
    public function liftEmbargo(int $id): RedirectResponse
    {
        $this->service->liftEmbargo($id, (int) auth()->id(), 'Lifted from extended rights interface');

        return redirect()
            ->route('rights.extended.embargoes')
            ->with('success', 'Embargo lifted successfully.');
    }
}
