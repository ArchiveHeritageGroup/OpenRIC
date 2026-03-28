<?php

declare(strict_types=1);

namespace OpenRiC\Authority\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use OpenRiC\Authority\Contracts\AuthorityServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Authority file search and linking service.
 *
 * PHP/Laravel adaptation of Heratio's ric_authority_linker.py.
 * Searches Wikidata, VIAF, and LCNAF for matching authority records
 * and manages owl:sameAs links in the triplestore.
 */
class AuthorityService implements AuthorityServiceInterface
{
    /**
     * Rate-limiting delays in seconds between requests to external APIs.
     */
    private const WIKIDATA_DELAY_SECONDS = 1.0;

    private const VIAF_DELAY_SECONDS = 0.5;

    private const LCNAF_DELAY_SECONDS = 0.5;

    /**
     * Wikidata entity type QIDs for type filtering.
     */
    private const WIKIDATA_HUMAN = 'wd:Q5';

    private const WIKIDATA_ORGANISATION_TYPES = [
        'wd:Q43229',   // organization
        'wd:Q4830453', // business
        'wd:Q327333',  // government agency
        'wd:Q6881511', // museum
    ];

    /**
     * Source identifiers for classifying external links.
     */
    private const SOURCE_WIKIDATA = 'wikidata';

    private const SOURCE_VIAF = 'viaf';

    private const SOURCE_LCNAF = 'lcnaf';

    private Client $httpClient;

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {
        $this->httpClient = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'OpenRiC-Authority-Linker/1.0 (archives@theahg.co.za)',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function searchWikidata(string $name, string $agentType = 'Person', ?string $dates = null): array
    {
        $typeFilter = $this->buildWikidataTypeFilter($agentType);
        $searchName = $this->sanitiseSearchName($name);

        $sparql = <<<SPARQL
SELECT ?item ?itemLabel ?itemDescription ?viaf WHERE {
    SERVICE wikibase:mwapi {
        bd:serviceParam wikibase:api "EntitySearch" ;
                        wikibase:endpoint "www.wikidata.org" ;
                        mwapi:search "{$searchName}" ;
                        mwapi:language "en" .
        ?item wikibase:apiOutputItem mwapi:item .
    }

    {$typeFilter}

    OPTIONAL { ?item wdt:P214 ?viaf }

    SERVICE wikibase:label { bd:serviceParam wikibase:language "en" . }
}
LIMIT 5
SPARQL;

        try {
            $response = $this->httpClient->post('https://query.wikidata.org/sparql', [
                'form_params' => [
                    'query' => $sparql,
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $bindings = $result['results']['bindings'] ?? [];

            $matches = [];
            foreach ($bindings as $binding) {
                $matches[] = [
                    'source' => self::SOURCE_WIKIDATA,
                    'uri' => $binding['item']['value'] ?? '',
                    'label' => $binding['itemLabel']['value'] ?? '',
                    'description' => $binding['itemDescription']['value'] ?? '',
                    'viaf' => $binding['viaf']['value'] ?? null,
                ];
            }

            return $matches;
        } catch (GuzzleException $e) {
            Log::warning('Wikidata search failed', [
                'name' => $name,
                'agentType' => $agentType,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function searchViaf(string $name, string $agentType = 'Person'): array
    {
        $cqlField = $agentType === 'CorporateBody'
            ? 'local.corporateNames'
            : 'local.personalNames';

        $encodedName = urlencode($name);
        $url = "https://viaf.org/viaf/search?query={$cqlField}+all+%22{$encodedName}%22"
            . '&sortKeys=holdingscount&maximumRecords=5&httpAccept=application/json';

        try {
            $response = $this->httpClient->get($url);
            $result = json_decode($response->getBody()->getContents(), true);

            $records = $result['searchRetrieveResponse']['records'] ?? [];
            $matches = [];

            foreach ($records as $record) {
                $recordData = $record['record']['recordData'] ?? [];
                $viafId = $recordData['viafID'] ?? null;

                if ($viafId === null) {
                    continue;
                }

                $mainHeading = $this->extractViafMainHeading($recordData);

                $matches[] = [
                    'source' => self::SOURCE_VIAF,
                    'uri' => "https://viaf.org/viaf/{$viafId}",
                    'viaf_id' => $viafId,
                    'label' => $mainHeading,
                ];
            }

            return $matches;
        } catch (GuzzleException $e) {
            Log::warning('VIAF search failed', [
                'name' => $name,
                'agentType' => $agentType,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function searchLcnaf(string $name, string $agentType = 'Person'): array
    {
        try {
            $response = $this->httpClient->get('https://id.loc.gov/authorities/names/suggest2', [
                'query' => [
                    'q' => $name,
                    'count' => 5,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $hits = $result['hits'] ?? [];

            $matches = [];
            foreach ($hits as $hit) {
                $matches[] = [
                    'source' => self::SOURCE_LCNAF,
                    'uri' => $hit['uri'] ?? '',
                    'label' => $hit['aLabel'] ?? '',
                    'lccn' => $hit['token'] ?? '',
                ];
            }

            return $matches;
        } catch (GuzzleException $e) {
            Log::warning('LCNAF search failed', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function linkAgent(string $agentIri, string $externalUri, string $source, string $userId, string $reason): bool
    {
        $fullReason = "{$reason} (source: {$source})";

        return $this->triplestore->createRelationship(
            $agentIri,
            'owl:sameAs',
            $externalUri,
            $userId,
            $fullReason
        );
    }

    /**
     * {@inheritDoc}
     */
    public function unlinkAgent(string $agentIri, string $externalUri, string $userId, string $reason): bool
    {
        return $this->triplestore->deleteRelationship(
            $agentIri,
            'owl:sameAs',
            $externalUri,
            $userId,
            $reason
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getExternalLinks(string $iri): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = <<<SPARQL
{$prefixes}

SELECT ?external ?label WHERE {
    ?agentIri owl:sameAs ?external .
    OPTIONAL { ?external rdfs:label ?label }
}
LIMIT 50
SPARQL;

        $rows = $this->triplestore->select($sparql, ['agentIri' => $iri]);

        $links = [];
        foreach ($rows as $row) {
            $uri = $row['external'] ?? '';
            $links[] = [
                'uri' => $uri,
                'source' => $this->detectSourceFromUri($uri),
                'label' => $row['label'] ?? '',
            ];
        }

        return $links;
    }

    /**
     * {@inheritDoc}
     */
    public function getUnlinkedAgents(int $limit = 50): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = <<<SPARQL
{$prefixes}

SELECT ?agent ?name ?type ?dates WHERE {
    ?agent a ?type .
    FILTER(?type IN (rico:Person, rico:CorporateBody, rico:Family))

    ?agent rico:hasAgentName/rico:textualValue ?name .

    OPTIONAL { ?agent rico:hasBeginningDate ?dates }

    FILTER NOT EXISTS { ?agent owl:sameAs ?external }
}
ORDER BY ?name
LIMIT {$limit}
SPARQL;

        $rows = $this->triplestore->select($sparql);

        $agents = [];
        foreach ($rows as $row) {
            $typeUri = $row['type'] ?? '';
            $typeParts = explode('#', $typeUri);
            $shortType = end($typeParts);

            $agents[] = [
                'uri' => $row['agent'] ?? '',
                'name' => $row['name'] ?? '',
                'type' => $shortType,
                'dates' => $row['dates'] ?? null,
            ];
        }

        return $agents;
    }

    /**
     * {@inheritDoc}
     */
    public function autoSearch(string $name, string $agentType = 'Person', ?string $dates = null): array
    {
        $allMatches = [];

        // Search Wikidata
        $wikidataResults = $this->searchWikidata($name, $agentType, $dates);
        foreach ($wikidataResults as $result) {
            $confidence = $this->computeConfidence($name, $result['label'], $dates, $result['description'] ?? '');
            $allMatches[] = [
                'source' => $result['source'],
                'uri' => $result['uri'],
                'label' => $result['label'],
                'description' => $result['description'] ?? '',
                'confidence' => $confidence,
            ];
        }

        // Rate limit before VIAF
        usleep((int) (self::VIAF_DELAY_SECONDS * 1_000_000));

        // Search VIAF
        $viafResults = $this->searchViaf($name, $agentType);
        foreach ($viafResults as $result) {
            $confidence = $this->computeConfidence($name, $result['label'], $dates);
            $allMatches[] = [
                'source' => $result['source'],
                'uri' => $result['uri'],
                'label' => $result['label'],
                'confidence' => $confidence,
            ];
        }

        // Rate limit before LCNAF
        usleep((int) (self::LCNAF_DELAY_SECONDS * 1_000_000));

        // Search LCNAF
        $lcnafResults = $this->searchLcnaf($name, $agentType);
        foreach ($lcnafResults as $result) {
            $confidence = $this->computeConfidence($name, $result['label'], $dates);
            $allMatches[] = [
                'source' => $result['source'],
                'uri' => $result['uri'],
                'label' => $result['label'],
                'confidence' => $confidence,
            ];
        }

        // Sort by confidence descending
        usort($allMatches, fn (array $a, array $b): int => $b['confidence'] <=> $a['confidence']);

        return $allMatches;
    }

    /**
     * {@inheritDoc}
     */
    public function autoLinkAgent(string $agentIri, string $userId, float $confidenceThreshold = 0.7): array
    {
        $entity = $this->triplestore->getEntity($agentIri);

        if ($entity === null) {
            Log::warning('Auto-link failed: agent not found', ['agentIri' => $agentIri]);

            return [
                'agent_iri' => $agentIri,
                'links_added' => 0,
                'matches' => [],
            ];
        }

        $name = $this->extractAgentName($entity);
        $agentType = $this->extractAgentType($entity);
        $dates = $entity['rico:hasBeginningDate'] ?? null;

        $matches = $this->autoSearch($name, $agentType, $dates);

        $linksAdded = 0;
        $linkedSources = [];
        $linkedMatches = [];

        foreach ($matches as $match) {
            // Only link the best match per source
            if (in_array($match['source'], $linkedSources, true)) {
                continue;
            }

            if ($match['confidence'] < $confidenceThreshold) {
                continue;
            }

            $linked = $this->linkAgent(
                $agentIri,
                $match['uri'],
                $match['source'],
                $userId,
                "Auto-linked with confidence {$match['confidence']}"
            );

            if ($linked) {
                $linksAdded++;
                $linkedSources[] = $match['source'];
                $match['linked'] = true;
            } else {
                $match['linked'] = false;
            }

            $linkedMatches[] = $match;
        }

        return [
            'agent_iri' => $agentIri,
            'links_added' => $linksAdded,
            'matches' => $linkedMatches,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function batchLink(string $userId, int $limit = 50, float $confidenceThreshold = 0.7, bool $dryRun = false): array
    {
        $agents = $this->getUnlinkedAgents($limit);

        $stats = [
            'agents_checked' => 0,
            'links_added' => 0,
            'errors' => 0,
            'matches' => [],
        ];

        foreach ($agents as $agent) {
            $stats['agents_checked']++;

            try {
                // Rate limit between agents (Wikidata is the bottleneck)
                if ($stats['agents_checked'] > 1) {
                    usleep((int) (self::WIKIDATA_DELAY_SECONDS * 1_000_000));
                }

                $searchResults = $this->autoSearch($agent['name'], $agent['type'], $agent['dates']);

                $agentMatches = [
                    'agent' => $agent,
                    'links' => [],
                ];

                $linkedSources = [];

                foreach ($searchResults as $match) {
                    // Only the best match per source
                    if (in_array($match['source'], $linkedSources, true)) {
                        continue;
                    }

                    if ($match['confidence'] < $confidenceThreshold) {
                        continue;
                    }

                    $linkedSources[] = $match['source'];

                    if ($dryRun) {
                        $match['would_link'] = true;
                        $agentMatches['links'][] = $match;
                    } else {
                        $linked = $this->linkAgent(
                            $agent['uri'],
                            $match['uri'],
                            $match['source'],
                            $userId,
                            "Batch auto-linked with confidence {$match['confidence']}"
                        );

                        $match['linked'] = $linked;
                        $agentMatches['links'][] = $match;

                        if ($linked) {
                            $stats['links_added']++;
                        }
                    }
                }

                if (count($agentMatches['links']) > 0) {
                    $stats['matches'][] = $agentMatches;
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::warning('Batch link error for agent', [
                    'agent_uri' => $agent['uri'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatistics(): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        // Count total agents
        $totalSparql = <<<SPARQL
{$prefixes}

SELECT (COUNT(DISTINCT ?agent) AS ?count) WHERE {
    ?agent a ?type .
    FILTER(?type IN (rico:Person, rico:CorporateBody, rico:Family))
}
LIMIT 1
SPARQL;

        $totalRows = $this->triplestore->select($totalSparql);
        $totalAgents = (int) ($totalRows[0]['count'] ?? 0);

        // Count linked agents
        $linkedSparql = <<<SPARQL
{$prefixes}

SELECT (COUNT(DISTINCT ?agent) AS ?count) WHERE {
    ?agent a ?type .
    FILTER(?type IN (rico:Person, rico:CorporateBody, rico:Family))
    ?agent owl:sameAs ?external .
}
LIMIT 1
SPARQL;

        $linkedRows = $this->triplestore->select($linkedSparql);
        $linkedAgents = (int) ($linkedRows[0]['count'] ?? 0);

        // Count links by source
        $bySourceSparql = <<<SPARQL
{$prefixes}

SELECT ?external (COUNT(?external) AS ?count) WHERE {
    ?agent a ?type .
    FILTER(?type IN (rico:Person, rico:CorporateBody, rico:Family))
    ?agent owl:sameAs ?external .
}
GROUP BY ?external
LIMIT 1000
SPARQL;

        $sourceRows = $this->triplestore->select($bySourceSparql);

        $linksBySource = [
            self::SOURCE_WIKIDATA => 0,
            self::SOURCE_VIAF => 0,
            self::SOURCE_LCNAF => 0,
        ];

        foreach ($sourceRows as $row) {
            $uri = $row['external'] ?? '';
            $source = $this->detectSourceFromUri($uri);

            if (isset($linksBySource[$source])) {
                $linksBySource[$source]++;
            }
        }

        return [
            'total_agents' => $totalAgents,
            'linked_agents' => $linkedAgents,
            'unlinked_agents' => $totalAgents - $linkedAgents,
            'links_by_source' => $linksBySource,
        ];
    }

    // =========================================================================
    // Private Helper Methods
    // =========================================================================

    /**
     * Build the SPARQL type filter clause for Wikidata queries.
     *
     * Maps RiC-O agent types to Wikidata QID filters:
     * - Person  -> Q5 (human)
     * - CorporateBody -> Q43229, Q4830453, Q327333, Q6881511
     * - Family  -> no type filter (Wikidata has no direct family QID)
     */
    private function buildWikidataTypeFilter(string $agentType): string
    {
        if ($agentType === 'Person') {
            return '?item wdt:P31 ' . self::WIKIDATA_HUMAN . ' .';
        }

        if ($agentType === 'CorporateBody') {
            $types = implode(', ', self::WIKIDATA_ORGANISATION_TYPES);

            return <<<SPARQL
?item wdt:P31 ?orgType .
    FILTER(?orgType IN ({$types}))
SPARQL;
        }

        // Family or unknown type: no type filter
        return '';
    }

    /**
     * Sanitise a name string for safe inclusion in a SPARQL query.
     *
     * Collapses whitespace and escapes double quotes.
     */
    private function sanitiseSearchName(string $name): string
    {
        $cleaned = preg_replace('/\s+/', ' ', $name);
        $cleaned = trim($cleaned ?? $name);

        return str_replace('"', '\\"', $cleaned);
    }

    /**
     * Extract the main heading text from a VIAF record data structure.
     *
     * VIAF returns mainHeadings.data as either an array of headings
     * or a single heading object.
     */
    private function extractViafMainHeading(array $recordData): string
    {
        $headings = $recordData['mainHeadings']['data'] ?? [];

        if (is_array($headings) && ! empty($headings)) {
            // Could be a list of headings or a single heading object
            if (isset($headings['text'])) {
                // Single heading object
                return $headings['text'];
            }

            // List of heading objects
            return $headings[0]['text'] ?? '';
        }

        return '';
    }

    /**
     * Detect the authority source from a URI string.
     */
    private function detectSourceFromUri(string $uri): string
    {
        if (str_contains($uri, 'wikidata.org')) {
            return self::SOURCE_WIKIDATA;
        }

        if (str_contains($uri, 'viaf.org')) {
            return self::SOURCE_VIAF;
        }

        if (str_contains($uri, 'id.loc.gov')) {
            return self::SOURCE_LCNAF;
        }

        return 'unknown';
    }

    /**
     * Compute a confidence score for an authority match.
     *
     * Scoring factors:
     * - Name similarity (Levenshtein-based, normalised)  : 0.0 - 0.6
     * - Exact name match bonus                           : +0.2
     * - Date mention in description                      : +0.1
     * - Description present                              : +0.1
     *
     * @param  string      $queryName        the name we searched for
     * @param  string      $matchLabel       the label returned by the authority
     * @param  string|null $dates            optional dates for the agent
     * @param  string      $matchDescription optional description from the authority
     * @return float confidence score between 0.0 and 1.0
     */
    private function computeConfidence(
        string $queryName,
        string $matchLabel,
        ?string $dates = null,
        string $matchDescription = '',
    ): float {
        $score = 0.0;

        $normQuery = mb_strtolower(trim($queryName));
        $normLabel = mb_strtolower(trim($matchLabel));

        if ($normQuery === '' || $normLabel === '') {
            return 0.0;
        }

        // Name similarity via Levenshtein (capped at 255 chars for PHP's levenshtein)
        $truncQuery = mb_substr($normQuery, 0, 255);
        $truncLabel = mb_substr($normLabel, 0, 255);
        $maxLen = max(mb_strlen($truncQuery), mb_strlen($truncLabel));

        if ($maxLen > 0) {
            $distance = levenshtein($truncQuery, $truncLabel);
            $similarity = 1.0 - ($distance / $maxLen);
            $score += $similarity * 0.6;
        }

        // Exact match bonus
        if ($normQuery === $normLabel) {
            $score += 0.2;
        }

        // Date corroboration: if the agent's dates appear in the description
        if ($dates !== null && $dates !== '' && $matchDescription !== '') {
            // Extract year from dates (e.g., "1900-01-01" -> "1900")
            $yearMatch = [];
            if (preg_match('/(\d{4})/', $dates, $yearMatch)) {
                if (str_contains($matchDescription, $yearMatch[1])) {
                    $score += 0.1;
                }
            }
        }

        // Description present bonus
        if ($matchDescription !== '') {
            $score += 0.1;
        }

        return round(min($score, 1.0), 2);
    }

    /**
     * Extract the agent name from a triplestore entity property map.
     */
    private function extractAgentName(array $entity): string
    {
        // Try the direct textualValue first
        if (isset($entity['rico:hasAgentName'])) {
            $agentName = $entity['rico:hasAgentName'];

            // Could be a nested structure with textualValue
            if (is_array($agentName) && isset($agentName['rico:textualValue'])) {
                return (string) $agentName['rico:textualValue'];
            }

            return (string) $agentName;
        }

        return '';
    }

    /**
     * Extract the short RiC-O type name from a triplestore entity property map.
     */
    private function extractAgentType(array $entity): string
    {
        $type = $entity['rdf:type'] ?? $entity['type'] ?? '';

        if (is_string($type)) {
            $parts = explode('#', $type);
            $shortType = end($parts);

            // Also handle prefix:Type notation
            if (str_contains($shortType, ':')) {
                $prefixParts = explode(':', $shortType);

                return end($prefixParts);
            }

            return $shortType;
        }

        return 'Person';
    }
}
