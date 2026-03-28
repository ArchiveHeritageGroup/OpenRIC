<?php

declare(strict_types=1);

namespace OpenRiC\Search\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use OpenRiC\Search\Contracts\SearchServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class SearchService implements SearchServiceInterface
{
    private Client $esClient;

    private Client $qdrantClient;

    private string $indexPrefix;

    private string $qdrantCollection;

    /**
     * Minimal stop words list -- archival terms are deliberately kept.
     */
    private const STOP_WORDS = [
        'the', 'a', 'an', 'of', 'in', 'on', 'at', 'to', 'for', 'with', 'by',
        'from', 'and', 'or', 'is', 'are', 'was', 'were', 'be', 'been',
        'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
        'give', 'me', 'my', 'i', 'we', 'you', 'your', 'they', 'their',
        'this', 'that', 'all', 'any', 'some', 'no', 'not',
        'show', 'find', 'search', 'get', 'list', 'display',
    ];

    /**
     * Field boosting configuration for multi_match queries.
     * Mirrors the Python field weights: title^3, scopeAndContent^2, etc.
     */
    private const BOOSTED_FIELDS = [
        'title^3',
        'title.autocomplete^2',
        'authorized_form_of_name^3',
        'identifier^2',
        'reference_code^2',
        'scope_and_content',
        'description',
        'creator_name^2',
    ];

    /**
     * Fields used for wildcard / query_string partial matching.
     */
    private const WILDCARD_FIELDS = [
        'title',
        'identifier',
        'authorized_form_of_name',
    ];

    /**
     * Fields used for exact phrase boosting.
     */
    private const PHRASE_FIELDS = [
        'title',
        'authorized_form_of_name',
    ];

    /**
     * Fields returned in the _source projection.
     */
    private const SOURCE_FIELDS = [
        'iri',
        'title',
        'identifier',
        'reference_code',
        'scope_and_content',
        'entity_type',
        'creator_name',
        'date',
        'authorized_form_of_name',
        'level_of_description',
    ];

    /**
     * Highlight configuration.
     */
    private const HIGHLIGHT_FIELDS = [
        'title' => [],
        'scope_and_content' => ['fragment_size' => 150],
        'authorized_form_of_name' => [],
    ];

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {
        $esHost = config('openric.elasticsearch.host', 'localhost');
        $esPort = config('openric.elasticsearch.port', 9200);
        $esScheme = config('openric.elasticsearch.scheme', 'http');

        $this->esClient = new Client([
            'base_uri' => "{$esScheme}://{$esHost}:{$esPort}/",
            'timeout' => (int) config('openric.elasticsearch.timeout', 10),
            'connect_timeout' => 5,
        ]);

        $qdrantHost = config('openric.qdrant.host', 'localhost');
        $qdrantPort = config('openric.qdrant.port', 6333);

        $this->qdrantClient = new Client([
            'base_uri' => "http://{$qdrantHost}:{$qdrantPort}/",
            'timeout' => (int) config('openric.qdrant.timeout', 10),
            'connect_timeout' => 5,
        ]);

        $this->indexPrefix = config('openric.elasticsearch.index_prefix', 'openric_');
        $this->qdrantCollection = config('openric.qdrant.collection', 'openric_entities');
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $query, array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = trim($query);
        if ($query === '') {
            return $this->emptyResult();
        }

        $queryType = $this->parseQueryType($query);
        $searchTerms = $this->extractTerms($query);

        $esResults = $this->elasticsearchSearch($query, $queryType, $searchTerms, $filters, $limit, $offset);

        if ($esResults !== null && count($esResults['items']) > 0) {
            return array_merge($esResults, [
                'query_type' => $queryType,
                'search_terms' => $searchTerms,
            ]);
        }

        Log::info('SearchService: ES returned no results, falling back to SPARQL', [
            'query' => $query,
            'query_type' => $queryType,
        ]);

        $sparqlResults = $this->sparqlFallbackSearch($query, $searchTerms, $limit, $offset);

        return array_merge($sparqlResults, [
            'query_type' => $queryType,
            'search_terms' => $searchTerms,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function similarTo(string $iri, int $limit = 10): array
    {
        try {
            $response = $this->qdrantClient->post(
                "collections/{$this->qdrantCollection}/points/recommend",
                [
                    'json' => [
                        'positive' => [$iri],
                        'limit' => $limit,
                        'with_payload' => true,
                    ],
                ]
            );

            $result = json_decode($response->getBody()->getContents(), true);
            $points = $result['result'] ?? [];

            return array_map(static fn (array $point): array => [
                'iri' => $point['payload']['iri'] ?? $point['id'],
                'score' => (float) ($point['score'] ?? 0.0),
                'payload' => $point['payload'] ?? [],
            ], $points);
        } catch (GuzzleException $e) {
            Log::warning('SearchService: Qdrant similarTo failed', [
                'iri' => $iri,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * {@inheritDoc}
     *
     * Uses Elasticsearch match_phrase_prefix for fast autocomplete, with a
     * SPARQL fallback when ES is unavailable.
     */
    public function suggest(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $suggestions = $this->elasticsearchSuggest($query, $limit);
        if ($suggestions !== null) {
            return $suggestions;
        }

        return $this->sparqlSuggest($query, $limit);
    }

    /**
     * {@inheritDoc}
     */
    public function indexEntity(string $iri, array $properties): bool
    {
        $doc = [
            'iri' => $iri,
            'title' => $properties['rico:title'] ?? '',
            'identifier' => $properties['rico:identifier'] ?? '',
            'reference_code' => $properties['rico:referenceCode'] ?? '',
            'scope_and_content' => $properties['rico:scopeAndContent'] ?? '',
            'entity_type' => $properties['rdf:type'] ?? '',
            'authorized_form_of_name' => $properties['rico:hasAgentName'] ?? '',
            'creator_name' => $properties['rico:creatorName'] ?? '',
            'date' => $properties['rico:date'] ?? '',
            'level_of_description' => $properties['rico:levelOfDescription'] ?? '',
            'indexed_at' => now()->toIso8601String(),
        ];

        $esSuccess = $this->indexInElasticsearch($iri, $doc);
        $qdrantSuccess = $this->indexInQdrant($iri, $doc);

        if (! $esSuccess) {
            Log::error('SearchService: Failed to index entity in Elasticsearch', ['iri' => $iri]);
        }
        if (! $qdrantSuccess) {
            Log::warning('SearchService: Failed to index entity in Qdrant', ['iri' => $iri]);
        }

        return $esSuccess;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteFromIndex(string $iri): bool
    {
        $esSuccess = $this->deleteFromElasticsearch($iri);
        $qdrantSuccess = $this->deleteFromQdrant($iri);

        if (! $esSuccess) {
            Log::error('SearchService: Failed to delete entity from Elasticsearch', ['iri' => $iri]);
        }
        if (! $qdrantSuccess) {
            Log::warning('SearchService: Failed to delete entity from Qdrant', ['iri' => $iri]);
        }

        return $esSuccess;
    }

    // -------------------------------------------------------------------------
    // Query parsing and term extraction
    // -------------------------------------------------------------------------

    /**
     * Parse the query to determine its intent type.
     *
     * Returns one of: 'level', 'date', 'fuzzy'.
     */
    private function parseQueryType(string $query): string
    {
        $normalised = mb_strtolower(trim($query));
        $normalised = preg_replace(
            '/^(give\s+me|show\s+me|find|search\s+for|get|list|display)\s+/',
            '',
            $normalised
        );

        if (preg_match('/^(all\s+)?(fonds?|series|collections?)$/', $normalised)) {
            return 'level';
        }

        if (preg_match('/(?:from|between|dated?)\s+(\d{4})(?:\s*[-\x{2013}to]+\s*(\d{4}))?/u', $normalised)) {
            return 'date';
        }

        return 'fuzzy';
    }

    /**
     * Extract meaningful search terms, removing stop words and short tokens.
     *
     * @return array<int, string>
     */
    private function extractTerms(string $query): array
    {
        preg_match_all('/\b[a-zA-Z]+\b/', mb_strtolower($query), $matches);
        $words = $matches[0] ?? [];

        return array_values(array_filter(
            $words,
            static fn (string $word): bool => ! in_array($word, self::STOP_WORDS, true) && mb_strlen($word) > 1
        ));
    }

    // -------------------------------------------------------------------------
    // Elasticsearch operations
    // -------------------------------------------------------------------------

    /**
     * Execute the primary Elasticsearch search with fuzzy, wildcard, and phrase matching.
     *
     * @return array{items: array, total: int, source: string}|null  Null on failure.
     */
    private function elasticsearchSearch(
        string $query,
        string $queryType,
        array $searchTerms,
        array $filters,
        int $limit,
        int $offset,
    ): ?array {
        try {
            $body = $this->buildEsQueryBody($query, $queryType, $searchTerms, $filters, $limit, $offset);
            $indices = $this->getSearchIndices($filters);

            $response = $this->esClient->post("{$indices}/_search", [
                'json' => $body,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return $this->formatEsResults($result);
        } catch (GuzzleException $e) {
            Log::warning('SearchService: Elasticsearch search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build the comprehensive ES query body with three "should" clauses:
     *   1. Fuzzy multi_match with field boosting
     *   2. Wildcard query_string for partial matching
     *   3. Phrase multi_match for exact sequence boosting
     */
    private function buildEsQueryBody(
        string $query,
        string $queryType,
        array $searchTerms,
        array $filters,
        int $limit,
        int $offset,
    ): array {
        if ($queryType === 'level') {
            return [
                'from' => $offset,
                'size' => $limit,
                'query' => ['match_all' => (object) []],
                '_source' => self::SOURCE_FIELDS,
            ];
        }

        $searchString = count($searchTerms) > 0
            ? implode(' ', $searchTerms)
            : trim($query);

        $wildcardQuery = count($searchTerms) > 0
            ? implode(' OR ', array_map(static fn (string $t): string => "*{$t}*", $searchTerms))
            : "*{$query}*";

        $shouldClauses = [
            // 1. Fuzzy multi_match across boosted fields
            [
                'multi_match' => [
                    'query' => $searchString,
                    'fields' => self::BOOSTED_FIELDS,
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                    'prefix_length' => 1,
                    'operator' => 'or',
                ],
            ],
            // 2. Wildcard query_string for partial matching
            [
                'query_string' => [
                    'query' => $wildcardQuery,
                    'fields' => self::WILDCARD_FIELDS,
                    'analyze_wildcard' => true,
                ],
            ],
            // 3. Phrase match for exact sequences with extra boost
            [
                'multi_match' => [
                    'query' => $searchString,
                    'fields' => self::PHRASE_FIELDS,
                    'type' => 'phrase',
                    'boost' => 2,
                ],
            ],
        ];

        $boolQuery = [
            'should' => $shouldClauses,
            'minimum_should_match' => 1,
        ];

        $filterClauses = $this->buildFilterClauses($filters);
        if (count($filterClauses) > 0) {
            $boolQuery['filter'] = $filterClauses;
        }

        return [
            'from' => $offset,
            'size' => $limit,
            'query' => ['bool' => $boolQuery],
            'highlight' => ['fields' => self::HIGHLIGHT_FIELDS],
            '_source' => self::SOURCE_FIELDS,
        ];
    }

    /**
     * Build Elasticsearch filter clauses from user-provided filters.
     *
     * @return array<int, array>
     */
    private function buildFilterClauses(array $filters): array
    {
        $clauses = [];

        if (! empty($filters['entity_type'])) {
            $clauses[] = ['term' => ['entity_type' => $filters['entity_type']]];
        }

        if (! empty($filters['level_of_description'])) {
            $clauses[] = ['term' => ['level_of_description' => $filters['level_of_description']]];
        }

        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $range = [];
            if (! empty($filters['date_from'])) {
                $range['gte'] = $filters['date_from'];
            }
            if (! empty($filters['date_to'])) {
                $range['lte'] = $filters['date_to'];
            }
            $clauses[] = ['range' => ['date' => $range]];
        }

        return $clauses;
    }

    /**
     * Determine which ES indices to query based on entity_type filter.
     */
    private function getSearchIndices(array $filters): string
    {
        $recordIndex = "{$this->indexPrefix}records";
        $actorIndex = "{$this->indexPrefix}actors";

        if (! empty($filters['entity_type'])) {
            $type = mb_strtolower($filters['entity_type']);
            if (in_array($type, ['person', 'corporatebody', 'family', 'actor'], true)) {
                return $actorIndex;
            }
            if (in_array($type, ['record', 'recordset', 'recordpart'], true)) {
                return $recordIndex;
            }
        }

        return "{$recordIndex},{$actorIndex}";
    }

    /**
     * Format raw Elasticsearch response into a normalised result array.
     *
     * Deduplicates by IRI, extracts highlights, and sorts by score descending.
     *
     * @return array{items: array, total: int, source: string}
     */
    private function formatEsResults(array $esResult): array
    {
        $hits = $esResult['hits']['hits'] ?? [];
        if (count($hits) === 0) {
            return ['items' => [], 'total' => 0, 'source' => 'elasticsearch'];
        }

        $items = [];
        $seen = [];

        foreach ($hits as $hit) {
            $source = $hit['_source'] ?? [];
            $iri = $source['iri'] ?? $hit['_id'];

            if (isset($seen[$iri])) {
                continue;
            }
            $seen[$iri] = true;

            $highlight = $this->extractHighlight($hit);
            $entityType = $this->resolveEntityType($hit);

            $items[] = [
                'iri' => $iri,
                'type' => $entityType,
                'title' => $source['title']
                    ?? $source['authorized_form_of_name']
                    ?? 'Untitled',
                'identifier' => $source['identifier'] ?? $source['reference_code'] ?? null,
                'creator_name' => $source['creator_name'] ?? null,
                'date' => $source['date'] ?? null,
                'scope_and_content' => $source['scope_and_content'] ?? null,
                'level_of_description' => $source['level_of_description'] ?? null,
                'score' => (float) ($hit['_score'] ?? 0.0),
                'highlight' => $highlight,
                'source' => 'elasticsearch',
            ];
        }

        usort($items, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $total = $esResult['hits']['total'] ?? 0;
        if (is_array($total)) {
            $total = $total['value'] ?? count($items);
        }

        return [
            'items' => $items,
            'total' => (int) $total,
            'source' => 'elasticsearch',
        ];
    }

    /**
     * Extract the first highlight fragment from an ES hit.
     */
    private function extractHighlight(array $hit): ?string
    {
        $highlights = $hit['highlight'] ?? [];
        foreach ($highlights as $fragments) {
            if (is_array($fragments) && count($fragments) > 0) {
                return $fragments[0];
            }
        }

        return null;
    }

    /**
     * Resolve entity type from the ES index name or source data.
     */
    private function resolveEntityType(array $hit): string
    {
        $source = $hit['_source'] ?? [];
        if (! empty($source['entity_type'])) {
            return $source['entity_type'];
        }

        $index = $hit['_index'] ?? '';
        if (str_contains($index, 'actor')) {
            return 'Agent';
        }

        return 'Record';
    }

    /**
     * Autocomplete via Elasticsearch match_phrase_prefix.
     *
     * @return array<int, array{text: string, iri: string, type: string}>|null  Null on failure.
     */
    private function elasticsearchSuggest(string $query, int $limit): ?array
    {
        try {
            $indices = "{$this->indexPrefix}records,{$this->indexPrefix}actors";

            $body = [
                'size' => $limit,
                'query' => [
                    'bool' => [
                        'should' => [
                            ['match_phrase_prefix' => ['title' => ['query' => $query, 'max_expansions' => 20]]],
                            ['match_phrase_prefix' => ['authorized_form_of_name' => ['query' => $query, 'max_expansions' => 20]]],
                            ['match_phrase_prefix' => ['identifier' => ['query' => $query, 'max_expansions' => 20]]],
                        ],
                    ],
                ],
                '_source' => ['iri', 'title', 'authorized_form_of_name', 'entity_type'],
            ];

            $response = $this->esClient->post("{$indices}/_search", ['json' => $body]);
            $result = json_decode($response->getBody()->getContents(), true);

            $suggestions = [];
            $seen = [];

            foreach (($result['hits']['hits'] ?? []) as $hit) {
                $source = $hit['_source'] ?? [];
                $text = $source['title'] ?? $source['authorized_form_of_name'] ?? '';
                if ($text === '' || isset($seen[$text])) {
                    continue;
                }
                $seen[$text] = true;

                $suggestions[] = [
                    'text' => $text,
                    'iri' => $source['iri'] ?? $hit['_id'],
                    'type' => $source['entity_type'] ?? $this->resolveEntityType($hit),
                ];
            }

            return $suggestions;
        } catch (GuzzleException $e) {
            Log::warning('SearchService: Elasticsearch suggest failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Index a document into Elasticsearch.
     */
    private function indexInElasticsearch(string $iri, array $doc): bool
    {
        try {
            $index = $this->resolveIndexForEntityType($doc['entity_type'] ?? '');
            $this->esClient->put(
                "{$index}/_doc/" . urlencode($iri),
                ['json' => $doc]
            );

            return true;
        } catch (GuzzleException $e) {
            Log::error('SearchService: ES index failed', [
                'iri' => $iri,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete a document from Elasticsearch.
     */
    private function deleteFromElasticsearch(string $iri): bool
    {
        try {
            $indices = [
                "{$this->indexPrefix}records",
                "{$this->indexPrefix}actors",
            ];

            foreach ($indices as $index) {
                try {
                    $this->esClient->delete("{$index}/_doc/" . urlencode($iri));

                    return true;
                } catch (GuzzleException) {
                    // Entity may not exist in this index; try the next one.
                }
            }

            return false;
        } catch (GuzzleException $e) {
            Log::error('SearchService: ES delete failed', [
                'iri' => $iri,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Determine the correct ES index for a given entity type.
     */
    private function resolveIndexForEntityType(string $entityType): string
    {
        $type = mb_strtolower($entityType);
        $agentTypes = ['person', 'corporatebody', 'family', 'agent',
            'rico:person', 'rico:corporatebody', 'rico:family'];

        if (in_array($type, $agentTypes, true)) {
            return "{$this->indexPrefix}actors";
        }

        return "{$this->indexPrefix}records";
    }

    // -------------------------------------------------------------------------
    // Qdrant operations
    // -------------------------------------------------------------------------

    /**
     * Index an entity's vector representation in Qdrant.
     */
    private function indexInQdrant(string $iri, array $doc): bool
    {
        try {
            $textForEmbedding = implode(' ', array_filter([
                $doc['title'] ?? '',
                $doc['scope_and_content'] ?? '',
                $doc['authorized_form_of_name'] ?? '',
            ]));

            if (trim($textForEmbedding) === '') {
                return true;
            }

            $this->qdrantClient->put(
                "collections/{$this->qdrantCollection}/points",
                [
                    'json' => [
                        'points' => [
                            [
                                'id' => crc32($iri),
                                'payload' => [
                                    'iri' => $iri,
                                    'title' => $doc['title'] ?? '',
                                    'entity_type' => $doc['entity_type'] ?? '',
                                    'text' => $textForEmbedding,
                                ],
                            ],
                        ],
                    ],
                ]
            );

            return true;
        } catch (GuzzleException $e) {
            Log::warning('SearchService: Qdrant index failed', [
                'iri' => $iri,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete an entity from Qdrant by its IRI hash.
     */
    private function deleteFromQdrant(string $iri): bool
    {
        try {
            $this->qdrantClient->post(
                "collections/{$this->qdrantCollection}/points/delete",
                [
                    'json' => [
                        'points' => [crc32($iri)],
                    ],
                ]
            );

            return true;
        } catch (GuzzleException $e) {
            Log::warning('SearchService: Qdrant delete failed', [
                'iri' => $iri,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // -------------------------------------------------------------------------
    // SPARQL fallbacks
    // -------------------------------------------------------------------------

    /**
     * SPARQL-based full-text fallback when Elasticsearch is unavailable.
     *
     * Queries both rico:title and rico:hasAgentName paths, matching against
     * extracted search terms with CONTAINS filters.
     *
     * @return array{items: array, total: int, source: string}
     */
    private function sparqlFallbackSearch(string $query, array $searchTerms, int $limit, int $offset): array
    {
        $terms = count($searchTerms) > 0 ? $searchTerms : [trim($query)];

        $filterParts = array_map(
            static fn (string $term): string => 'CONTAINS(LCASE(?text), LCASE(?term_' . md5($term) . '))',
            $terms
        );
        $filterExpr = implode(' || ', $filterParts);

        $sparql = <<<SPARQL
            SELECT DISTINCT ?entity ?title ?type ?identifier WHERE {
                ?entity a ?type .
                FILTER(?type IN (rico:RecordSet, rico:Record, rico:Person, rico:CorporateBody))
                {
                    ?entity rico:title ?title .
                    BIND(?title AS ?text)
                }
                UNION
                {
                    ?entity rico:hasAgentName/rico:textualValue ?title .
                    BIND(?title AS ?text)
                }
                FILTER({$filterExpr})
                OPTIONAL { ?entity rico:identifier ?identifier }
            }
            ORDER BY ?title
            LIMIT ?limit
            OFFSET ?offset
            SPARQL;

        $bindings = [
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        ];
        foreach ($terms as $term) {
            $bindings['term_' . md5($term)] = $term;
        }

        $rows = $this->triplestore->select($sparql, $bindings);

        $items = array_map(static function (array $row): array {
            $rawType = $row['type'] ?? '';
            $shortType = str_contains($rawType, '#')
                ? substr($rawType, strrpos($rawType, '#') + 1)
                : $rawType;

            return [
                'iri' => $row['entity'] ?? '',
                'type' => $shortType ?: 'Record',
                'title' => $row['title'] ?? 'Untitled',
                'identifier' => $row['identifier'] ?? null,
                'creator_name' => null,
                'date' => null,
                'scope_and_content' => null,
                'level_of_description' => null,
                'score' => 0.0,
                'highlight' => null,
                'source' => 'triplestore',
            ];
        }, $rows);

        return [
            'items' => $items,
            'total' => count($items),
            'source' => 'triplestore',
        ];
    }

    /**
     * SPARQL-based autocomplete fallback.
     *
     * @return array<int, array{text: string, iri: string, type: string}>
     */
    private function sparqlSuggest(string $query, int $limit): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?iri ?title ?type WHERE {
                ?iri a ?type .
                FILTER(?type IN (rico:RecordSet, rico:Record, rico:Person, rico:CorporateBody))
                {
                    ?iri rico:title ?title .
                }
                UNION
                {
                    ?iri rico:hasAgentName/rico:textualValue ?title .
                }
                FILTER(CONTAINS(LCASE(?title), LCASE(?query)))
            }
            LIMIT ?limit
            SPARQL;

        $rows = $this->triplestore->select($sparql, [
            'query' => $query,
            'limit' => (string) $limit,
        ]);

        return array_map(static function (array $row): array {
            $rawType = $row['type'] ?? '';
            $shortType = str_contains($rawType, '#')
                ? substr($rawType, strrpos($rawType, '#') + 1)
                : $rawType;

            return [
                'text' => $row['title'] ?? '',
                'iri' => $row['iri'] ?? '',
                'type' => $shortType ?: 'Record',
            ];
        }, $rows);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return an empty result set.
     *
     * @return array{items: array, total: int, source: string, query_type: string, search_terms: array}
     */
    private function emptyResult(): array
    {
        return [
            'items' => [],
            'total' => 0,
            'source' => 'none',
            'query_type' => 'fuzzy',
            'search_terms' => [],
        ];
    }
}
