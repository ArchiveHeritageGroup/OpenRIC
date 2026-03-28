<?php

declare(strict_types=1);

namespace OpenRiC\Triplestore\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class SparqlEndpointController extends Controller
{
    private const FORBIDDEN_KEYWORDS = [
        'INSERT', 'DELETE', 'DROP', 'CLEAR', 'CREATE', 'LOAD', 'COPY', 'MOVE', 'ADD',
    ];

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function form(): \Illuminate\View\View
    {
        return view('triplestore::sparql', ['prefixes' => $this->triplestore->getPrefixes()]);
    }

    public function query(Request $request): Response
    {
        $query = $request->input('query') ?? $request->getContent();
        if (empty($query)) {
            return response('Missing query parameter', 400)->header('Content-Type', 'text/plain');
        }

        $upper = strtoupper($query);
        foreach (self::FORBIDDEN_KEYWORDS as $kw) {
            if (str_contains($upper, $kw . ' ') || str_contains($upper, $kw . '{')) {
                return response('Write operations not permitted', 403)->header('Content-Type', 'text/plain');
            }
        }

        $accept = $request->header('Accept', 'application/sparql-results+json');
        if (str_contains($upper, 'CONSTRUCT') || str_contains($upper, 'DESCRIBE')) {
            $accept = $request->header('Accept', 'text/turtle');
        }

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 30]);
            $response = $client->post(config('fuseki.endpoint') . '/query', [
                'headers' => ['Content-Type' => 'application/sparql-query', 'Accept' => $accept],
                'auth' => [config('fuseki.username'), config('fuseki.password')],
                'body' => $query,
            ]);

            return response($response->getBody()->getContents(), 200)
                ->header('Content-Type', $response->getHeaderLine('Content-Type') ?: 'application/json')
                ->header('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            return response(json_encode(['error' => $e->getMessage()]), 500)->header('Content-Type', 'application/json');
        }
    }

    public function prefixes(): \Illuminate\Http\JsonResponse
    {
        $file = __DIR__ . '/../../resources/prefixes.php';
        return response()->json(['prefixes' => file_exists($file) ? include $file : []]);
    }
}
