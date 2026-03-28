<?php

declare(strict_types=1);

namespace OpenRiC\Export\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use OpenRiC\Export\Contracts\ExportServiceInterface;
use OpenRiC\Export\Contracts\IiifServiceInterface;

/**
 * Controller for export endpoints — JSON-LD, Turtle, RDF/XML, EAD3, EAC-CPF,
 * Dublin Core, bulk export, and IIIF manifest/collection generation.
 *
 * All routes are public (no auth) to support linked data consumers and IIIF clients.
 * Business logic is delegated entirely to ExportServiceInterface and IiifServiceInterface.
 */
class ExportController extends Controller
{
    public function __construct(
        private readonly ExportServiceInterface $exportService,
        private readonly IiifServiceInterface $iiifService,
    ) {}

    // =========================================================================
    // Single Entity Exports
    // =========================================================================

    /**
     * Export entity as JSON-LD.
     *
     * GET /export/{iri}/jsonld
     */
    public function jsonLd(string $iri): JsonResponse
    {
        $decodedIri = urldecode($iri);
        $data = $this->exportService->exportJsonLd($decodedIri);

        return response()->json($data, 200, [
            'Content-Type' => 'application/ld+json; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Export entity as Turtle.
     *
     * GET /export/{iri}/turtle
     */
    public function turtle(string $iri): Response
    {
        $decodedIri = urldecode($iri);
        $content = $this->exportService->exportTurtle($decodedIri);

        return response($content, 200, [
            'Content-Type' => 'text/turtle; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Export entity as RDF/XML.
     *
     * GET /export/{iri}/rdfxml
     */
    public function rdfXml(string $iri): Response
    {
        $decodedIri = urldecode($iri);
        $content = $this->exportService->exportRdfXml($decodedIri);

        return response($content, 200, [
            'Content-Type' => 'application/rdf+xml; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Export entity as EAD3 XML.
     *
     * GET /export/{iri}/ead3
     */
    public function ead3(string $iri): Response
    {
        $decodedIri = urldecode($iri);
        $content = $this->exportService->exportEad3($decodedIri);

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
            'Content-Disposition' => 'inline; filename="ead3.xml"',
        ]);
    }

    /**
     * Export agent entity as EAC-CPF XML.
     *
     * GET /export/{iri}/eaccpf
     */
    public function eacCpf(string $iri): Response
    {
        $decodedIri = urldecode($iri);
        $content = $this->exportService->exportEacCpf($decodedIri);

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
            'Content-Disposition' => 'inline; filename="eac-cpf.xml"',
        ]);
    }

    /**
     * Export entity as Dublin Core XML.
     *
     * GET /export/{iri}/dc
     */
    public function dublinCore(string $iri): Response
    {
        $decodedIri = urldecode($iri);
        $content = $this->exportService->exportDublinCore($decodedIri);

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    // =========================================================================
    // Bulk Export
    // =========================================================================

    /**
     * Bulk export multiple entities in a chosen format.
     *
     * GET /export/bulk?iri[]=...&iri[]=...&format=turtle
     */
    public function bulk(Request $request): Response
    {
        $iris = $request->input('iri', []);
        $format = $request->input('format', 'jsonld');

        if (!is_array($iris) || empty($iris)) {
            return response('Parameter "iri" must be a non-empty array.', 400, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $formats = $this->exportService->getAvailableFormats();

        if (!isset($formats[$format])) {
            $validFormats = implode(', ', array_keys($formats));

            return response('Invalid format. Valid formats: ' . $validFormats, 400, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $decodedIris = array_map('urldecode', $iris);
        $content = $this->exportService->bulkExport($decodedIris, $format);

        return response($content, 200, [
            'Content-Type' => $formats[$format]['mimeType'] . '; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    // =========================================================================
    // Available Formats
    // =========================================================================

    /**
     * List all available export formats.
     *
     * GET /export/formats
     */
    public function formats(): JsonResponse
    {
        return response()->json($this->exportService->getAvailableFormats(), 200, [
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    // =========================================================================
    // IIIF Endpoints
    // =========================================================================

    /**
     * Generate a IIIF Presentation API 3.0 manifest for an Instantiation.
     *
     * GET /iiif/{iri}/manifest
     */
    public function iiifManifest(string $iri): JsonResponse
    {
        $decodedIri = urldecode($iri);
        $manifest = $this->iiifService->generateManifest($decodedIri);

        return response()->json($manifest, 200, [
            'Content-Type' => 'application/ld+json; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate a IIIF Presentation API 3.0 collection for a RecordSet.
     *
     * GET /iiif/{iri}/collection
     */
    public function iiifCollection(string $iri): JsonResponse
    {
        $decodedIri = urldecode($iri);
        $collection = $this->iiifService->generateCollection($decodedIri);

        return response()->json($collection, 200, [
            'Content-Type' => 'application/ld+json; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
