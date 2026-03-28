<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Export API Controller — export entities in various RDF and archival formats.
 */
class ExportApiController extends Controller
{
    private const FORMAT_MAP = [
        'jsonld' => ['mime' => 'application/ld+json', 'accept' => 'application/ld+json'],
        'turtle' => ['mime' => 'text/turtle', 'accept' => 'text/turtle'],
        'rdfxml' => ['mime' => 'application/rdf+xml', 'accept' => 'application/rdf+xml'],
        'ntriples' => ['mime' => 'application/n-triples', 'accept' => 'application/n-triples'],
        'ead3' => ['mime' => 'application/xml', 'accept' => 'application/xml'],
        'eac-cpf' => ['mime' => 'application/xml', 'accept' => 'application/xml'],
        'dc' => ['mime' => 'application/xml', 'accept' => 'application/xml'],
    ];

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    /**
     * GET /api/v1/{entityType}/{iri}/export/{format}
     */
    public function export(string $entityType, string $iri, string $format): Response|JsonResponse
    {
        $iri = urldecode($iri);
        $formatConfig = self::FORMAT_MAP[$format] ?? null;

        if (!$formatConfig) {
            return response()->json(['error' => 'Unsupported export format: ' . $format], 400);
        }

        // For RDF formats, use CONSTRUCT/DESCRIBE from triplestore
        if (in_array($format, ['jsonld', 'turtle', 'rdfxml', 'ntriples'])) {
            try {
                $sparql = "DESCRIBE <{$iri}>";
                $result = $this->triplestore->describe($sparql, $formatConfig['accept']);

                return response($result, 200, [
                    'Content-Type' => $formatConfig['mime'],
                    'Content-Disposition' => 'attachment; filename="export.' . $this->getExtension($format) . '"',
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
            }
        }

        // For archival formats (EAD3, EAC-CPF, DC), use the export service
        // These are handled by the openric-export package
        $entity = $this->triplestore->getEntity($iri);
        if ($entity === null) {
            return response()->json(['error' => 'Entity not found'], 404);
        }

        return response()->json([
            'error' => 'Format ' . $format . ' export is handled by the openric-export package. Use the /export route instead.',
            'entity_iri' => $iri,
        ], 501);
    }

    private function getExtension(string $format): string
    {
        return match ($format) {
            'jsonld' => 'jsonld',
            'turtle' => 'ttl',
            'rdfxml' => 'rdf',
            'ntriples' => 'nt',
            'ead3' => 'xml',
            'eac-cpf' => 'xml',
            'dc' => 'xml',
            default => 'txt',
        };
    }
}
