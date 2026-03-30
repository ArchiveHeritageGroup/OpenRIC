<?php

declare(strict_types=1);

namespace OpenRiC\Rights\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * PremisRightsController — PREMIS rights display for entities.
 *
 * Adapted from Heratio RightsController (59 lines) which displays PREMIS rights
 * records for an information object by resolving slug -> object_id -> rights
 * with basis/granted_right term name resolution.
 *
 * OpenRiC resolves entity_iri -> rights_statements with direct field lookups
 * in PostgreSQL (no term_id resolution needed).
 */
class PremisRightsController extends Controller
{
    /**
     * Show PREMIS rights for an entity.
     *
     * Adapted from Heratio RightsController::index() which resolves slug -> object_id,
     * then fetches rights + rights_i18n + granted_right + term names.
     */
    public function index(Request $request): View
    {
        $entityIri = $request->input('entity_iri', '');

        $rights = [];
        if ($entityIri !== '') {
            $rights = DB::table('rights_statements')
                ->where('entity_iri', $entityIri)
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($r) {
                    $row = (array) $r;
                    $row['basis_label'] = ucfirst($r->rights_basis ?? 'Rights record');
                    return $row;
                })
                ->toArray();
        }

        return view('rights::rights.index', compact('entityIri', 'rights'));
    }
}
