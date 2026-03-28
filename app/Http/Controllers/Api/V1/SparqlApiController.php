<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * SPARQL API Controller — authenticated SPARQL passthrough.
 */
class SparqlApiController extends Controller
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    /**
     * POST /api/v1/sparql — Execute a SPARQL query.
     * Only SELECT and ASK queries allowed (no INSERT/DELETE).
     */
    public function query(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|max:10000',
        ]);

        $sparql = $request->input('query');

        // Security: only allow read queries
        $normalized = strtoupper(trim($sparql));
        if (!str_starts_with($normalized, 'SELECT') && !str_starts_with($normalized, 'ASK') && !str_starts_with($normalized, 'CONSTRUCT') && !str_starts_with($normalized, 'DESCRIBE') && !str_starts_with($normalized, 'PREFIX')) {
            return response()->json(['error' => 'Only SELECT, ASK, CONSTRUCT, and DESCRIBE queries are allowed.'], 403);
        }

        // Block write operations
        if (preg_match('/\b(INSERT|DELETE|DROP|CLEAR|LOAD|CREATE)\b/i', $sparql)) {
            return response()->json(['error' => 'Write operations are not allowed via the API.'], 403);
        }

        try {
            $results = $this->triplestore->select($sparql);

            return response()->json([
                'results' => $results,
                'count' => count($results),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'SPARQL execution failed: ' . $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/v1/sparql/prefixes — Get the canonical prefix map.
     */
    public function prefixes(): JsonResponse
    {
        return response()->json([
            'prefixes' => [
                'rico' => 'https://www.ica.org/standards/RiC/ontology#',
                'ricor' => 'https://www.ica.org/standards/RiC/vocabularies/',
                'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                'owl' => 'http://www.w3.org/2002/07/owl#',
                'xsd' => 'http://www.w3.org/2001/XMLSchema#',
                'skos' => 'http://www.w3.org/2004/02/skos/core#',
                'dc' => 'http://purl.org/dc/elements/1.1/',
                'dcterms' => 'http://purl.org/dc/terms/',
                'foaf' => 'http://xmlns.com/foaf/0.1/',
                'schema' => 'https://schema.org/',
                'prov' => 'http://www.w3.org/ns/prov#',
                'openric' => 'https://ric.theahg.co.za/ric/',
            ],
        ]);
    }
}
