<?php

declare(strict_types=1);

namespace OpenRiC\Search\Services;

use GuzzleHttp\Client;
use OpenRiC\Search\Contracts\SearchServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class SearchService implements SearchServiceInterface
{
    private Client $esClient;

    private Client $qdrantClient;

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {
        $esHost = config('openric.elasticsearch.host', 'localhost');
        $esPort = config('openric.elasticsearch.port', 9200);
        $esScheme = config('openric.elasticsearch.scheme', 'http');

        $this->esClient = new Client([
            'base_uri' => "{$esScheme}://{$esHost}:{$esPort}/",
            'timeout' => 10,
        ]);

        $qdrantHost = config('openric.qdrant.host', 'localhost');
        $qdrantPort = config('openric.qdrant.port', 6333);

        $this->qdrantClient = new Client([
            'base_uri' => "http://{$qdrantHost}:{$qdrantPort}/",
            'timeout' => 10,
        ]);
    }

    public function search(string $query, array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $indexPrefix = config('openric.elasticsearch.index_prefix', 'openric_');

        try {
            $body = [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['title^3', 'scope_and_content^2', 'identifier', 'description'],
                        'fuzziness' => 'AUTO',
                        'prefix_length' => 1,
                    ],
                ],
                'from' => $offset,
                'size' => $limit,
            ];

            if (! empty($filters['entity_type'])) {
                $body['query'] = [
                    'bool' => [
                        'must' => [$body['query']],
                        'filter' => [
                            ['term' => ['entity_type' => $filters['entity_type']]],
                        ],
                    ],
                ];
            }

            $response = $this->esClient->post("{$indexPrefix}entities/_search", [
                'json' => $body,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $hits = $result['hits']['hits'] ?? [];
            $total = $result['hits']['total']['value'] ?? 0;

            $items = array_map(fn ($hit) => array_merge(
                $hit['_source'],
                ['_score' => $hit['_score'], '_id' => $hit['_id']]
            ), $hits);

            return ['items' => $items, 'total' => $total, 'facets' => []];
        } catch (\Exception $e) {
            return $this->sparqlFallbackSearch($query, $limit, $offset);
        }
    }

    public function similarTo(string $iri, int $limit = 10): array
    {
        $collection = config('openric.qdrant.collection', 'openric_entities');

        try {
            $response = $this->qdrantClient->post("collections/{$collection}/points/recommend", [
                'json' => [
                    'positive' => [$iri],
                    'limit' => $limit,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return $result['result'] ?? [];
        } catch (\Exception) {
            return [];
        }
    }

    public function suggest(string $query, int $limit = 5): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?iri ?title ?type WHERE {
                ?iri rico:title ?title .
                ?iri a ?type .
                FILTER(CONTAINS(LCASE(?title), LCASE(?query)))
                FILTER(STRSTARTS(STR(?type), STR(rico:)))
            }
            LIMIT ?limit
            SPARQL;

        return $this->triplestore->select($sparql, [
            'query' => $query,
            'limit' => (string) $limit,
        ]);
    }

    public function indexEntity(string $iri, array $properties): bool
    {
        $indexPrefix = config('openric.elasticsearch.index_prefix', 'openric_');

        try {
            $doc = [
                'iri' => $iri,
                'title' => $properties['rico:title'] ?? '',
                'identifier' => $properties['rico:identifier'] ?? '',
                'scope_and_content' => $properties['rico:scopeAndContent'] ?? '',
                'entity_type' => $properties['rdf:type'] ?? '',
                'indexed_at' => now()->toISOString(),
            ];

            $this->esClient->put("{$indexPrefix}entities/_doc/" . urlencode($iri), [
                'json' => $doc,
            ]);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function deleteFromIndex(string $iri): bool
    {
        $indexPrefix = config('openric.elasticsearch.index_prefix', 'openric_');

        try {
            $this->esClient->delete("{$indexPrefix}entities/_doc/" . urlencode($iri));

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function sparqlFallbackSearch(string $query, int $limit, int $offset): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?iri ?title ?type WHERE {
                ?iri rico:title ?title .
                ?iri a ?type .
                FILTER(CONTAINS(LCASE(?title), LCASE(?query)))
                FILTER(STRSTARTS(STR(?type), STR(rico:)))
            }
            ORDER BY ?title
            LIMIT ?limit OFFSET ?offset
            SPARQL;

        $items = $this->triplestore->select($sparql, [
            'query' => $query,
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        ]);

        return ['items' => $items, 'total' => count($items), 'facets' => []];
    }
}
