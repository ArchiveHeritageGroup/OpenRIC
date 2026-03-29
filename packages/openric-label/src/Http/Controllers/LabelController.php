<?php

declare(strict_types=1);

namespace OpenRiC\Label\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OpenRiC\Label\Contracts\LabelServiceInterface;

/**
 * Label controller — adapted from Heratio ahg-label LabelController (372 lines).
 *
 * All Heratio controller actions mapped:
 *   1.  index      — Show label printing page for a single entity (GET)
 *   2.  generate   — Generate label with selected options (POST → redirect)
 *   3.  batchPrint — Batch print labels for multiple entities (POST)
 *
 * Heratio differences:
 *   - Slug-based routing replaced with IRI-based routing (base64-encoded in URL)
 *   - MySQL slug/object/class_name resolution replaced with TriplestoreService::getEntity()
 *   - i18n tables replaced with rico:title / rico:hasAgentName properties
 *   - library_item lookups replaced with triplestore property queries
 *   - display_object_config sector detection replaced with RDF type inspection
 *   - All entity resolution delegated to LabelServiceInterface
 */
class LabelController extends Controller
{
    public function __construct(
        private readonly LabelServiceInterface $service,
    ) {}

    /**
     * #1 — Show label printing page for a single entity identified by IRI.
     *
     * Adapted from Heratio LabelController::index(string $slug) which:
     *   - Resolves slug → object_id → class_name
     *   - Branches on QubitInformationObject / QubitActor / QubitAccession
     *   - Loads i18n title, identifier, library_item fields
     *   - Detects sector from display_object_config
     *   - Builds barcode sources array with priority ordering
     *   - Walks hierarchy for repository name
     *   - Returns view with all label configuration data
     *
     * OpenRiC receives the IRI as a base64-encoded URL parameter, delegates all
     * entity resolution to LabelService, and passes the same data structure to the view.
     */
    public function index(Request $request): View
    {
        $iri = $this->decodeIri($request->route('iri'));

        $data = $this->service->resolveEntity($iri);
        if ($data === null) {
            abort(404, 'Entity not found.');
        }

        // Check for label_options flash data from generate() redirect (same as Heratio)
        $labelOptions = session('label_options', []);

        return view('openric-label::index', [
            'iri'                => $data['iri'],
            'title'              => $data['title'],
            'identifier'         => $data['identifier'],
            'entityType'         => $data['entity_type'],
            'barcodeSources'     => $data['barcode_sources'],
            'defaultBarcodeData' => $data['default_barcode_data'],
            'repositoryName'     => $data['repository_name'],
            'qrUrl'              => $data['qr_url'],
            'sector'             => $data['sector'],
            'sectorLabel'        => $data['sector_label'],
            'labelOptions'       => $labelOptions,
        ]);
    }

    /**
     * #2 — Generate a label with selected options (POST).
     *
     * Adapted from Heratio LabelController::generate() which validates barcode_source,
     * label_size, show_* toggles, then redirects back to the label page with options
     * stored in session flash data.
     */
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'iri'            => 'required|string|max:2048',
            'barcode_source' => 'required|string|max:500',
            'label_size'     => 'required|in:200,300,400',
            'show_barcode'   => 'sometimes|boolean',
            'show_qr'        => 'sometimes|boolean',
            'show_title'     => 'sometimes|boolean',
            'show_repo'      => 'sometimes|boolean',
        ]);

        $encodedIri = $this->encodeIri($validated['iri']);

        return redirect()->route('openric.label.index', ['iri' => $encodedIri])
            ->with('label_options', $validated);
    }

    /**
     * #3 — Batch print labels for multiple entities (POST).
     *
     * Adapted from Heratio LabelController::batchPrint() which:
     *   - Validates array of slugs + display options
     *   - Iterates each slug, resolves via slug → object → class → entity → i18n
     *   - Checks library_item for ISBN/barcode override
     *   - Looks up repository name for each entity
     *   - Builds array of label data with barcodeData, qrUrl, title, etc.
     *   - Returns batch view with label grid
     *
     * OpenRiC receives IRIs instead of slugs and delegates resolution to LabelService.
     */
    public function batchPrint(Request $request): View
    {
        $validated = $request->validate([
            'iris'           => 'required|array|min:1|max:100',
            'iris.*'         => 'required|string|max:2048',
            'barcode_source' => 'sometimes|string|max:50',
            'label_size'     => 'sometimes|in:200,300,400',
            'show_barcode'   => 'sometimes|boolean',
            'show_qr'        => 'sometimes|boolean',
            'show_title'     => 'sometimes|boolean',
            'show_repo'      => 'sometimes|boolean',
        ]);

        $barcodeSource = $validated['barcode_source'] ?? null;
        $labels = $this->service->prepareBatchLabels($validated['iris'], $barcodeSource);

        $labelSize   = $validated['label_size'] ?? '300';
        $showBarcode = $validated['show_barcode'] ?? true;
        $showQr      = $validated['show_qr'] ?? true;
        $showTitle   = $validated['show_title'] ?? true;
        $showRepo    = $validated['show_repo'] ?? true;

        return view('openric-label::print', compact(
            'labels',
            'labelSize',
            'showBarcode',
            'showQr',
            'showTitle',
            'showRepo'
        ));
    }

    /**
     * Decode a base64-encoded IRI from a URL parameter.
     *
     * OpenRiC uses base64-encoded IRIs in URL paths because IRIs contain
     * slashes and special characters that conflict with URL routing.
     */
    private function decodeIri(string $encoded): string
    {
        $decoded = base64_decode($encoded, true);
        if ($decoded === false || $decoded === '') {
            abort(400, 'Invalid entity IRI encoding.');
        }

        return $decoded;
    }

    /**
     * Encode an IRI for use in a URL parameter.
     */
    private function encodeIri(string $iri): string
    {
        return rtrim(base64_encode($iri), '=');
    }
}
