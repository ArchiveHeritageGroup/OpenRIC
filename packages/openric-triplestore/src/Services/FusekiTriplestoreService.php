<?php

declare(strict_types=1);

namespace OpenRiC\Triplestore\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;
use OpenRiC\Triplestore\Exceptions\TriplestoreException;

/**
 * Apache Jena Fuseki implementation of the TriplestoreService.
 *
 * All SPARQL queries are parameterised via bindParams().
 * All write operations include RDF-Star provenance annotations.
 * All queries are prepended with canonical PREFIX declarations.
 */
class FusekiTriplestoreService implements TriplestoreServiceInterface
{
    private Client $client;

    private string $endpoint;

    private string $queryUrl;

    private string $updateUrl;

    private string $username;

    private string $password;

    private string $baseUri;

    private string $userBaseUri;

    private string $ontologyUri;

    /** @var array<string, string> */
    private array $prefixes;

    private ?string $cachedPrefixBlock = null;

    public function __construct()
    {
        $this->endpoint = rtrim((string) config('fuseki.endpoint', 'http://localhost:3030/openric'), '/');
        $this->queryUrl = $this->endpoint . '/query';
        $this->updateUrl = $this->endpoint . '/update';
        $this->username = (string) config('fuseki.username', 'admin');
        $this->password = (string) config('fuseki.password', '');
        $this->baseUri = rtrim((string) config('openric.base_uri', 'https://ric.theahg.co.za/entity'), '/');
        $this->userBaseUri = rtrim((string) config('openric.user_base_uri', 'https://ric.theahg.co.za/user'), '/');
        $this->ontologyUri = rtrim((string) config('openric.ontology_uri', 'https://ric.theahg.co.za/ontology#'), '#') . '#';

        $this->prefixes = require __DIR__ . '/../../resources/prefixes.php';

        $clientConfig = [
            'timeout' => (int) config('fuseki.timeout', 15),
            'connect_timeout' => (int) config('fuseki.connect_timeout', 10),
        ];

        if ($this->username && $this->password) {
            $clientConfig['auth'] = [$this->username, $this->password];
        }

        $this->client = new Client($clientConfig);
    }

    // =========================================================================
    // Query Methods
    // =========================================================================

    public function select(string $sparql, array $params = []): array
    {
        $boundQuery = $this->bindParams($sparql, $params);
        $fullQuery = $this->getPrefixes() . $boundQuery;

        $responseBody = $this->executeQuery($fullQuery);
        $decoded = json_decode($responseBody, true);

        if (! is_array($decoded) || ! isset($decoded['results']['bindings'])) {
            throw TriplestoreException::invalidResponse(
                'Missing results.bindings in SELECT response',
                $responseBody,
            );
        }

        $rows = [];
        foreach ($decoded['results']['bindings'] as $binding) {
            $row = [];
            foreach ($binding as $varName => $valueObj) {
                $row[$varName] = $this->extractBindingValue($valueObj);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function ask(string $sparql, array $params = []): bool
    {
        $boundQuery = $this->bindParams($sparql, $params);
        $fullQuery = $this->getPrefixes() . $boundQuery;

        $responseBody = $this->executeQuery($fullQuery);
        $decoded = json_decode($responseBody, true);

        if (! is_array($decoded) || ! isset($decoded['boolean'])) {
            throw TriplestoreException::invalidResponse(
                'Missing boolean in ASK response',
                $responseBody,
            );
        }

        return (bool) $decoded['boolean'];
    }

    public function construct(string $sparql, array $params = []): array
    {
        $boundQuery = $this->bindParams($sparql, $params);
        $fullQuery = $this->getPrefixes() . $boundQuery;

        $responseBody = $this->executeConstructOrDescribe($fullQuery);

        return $this->parseNTriplesResponse($responseBody);
    }

    public function describe(string $iri): array
    {
        $sparql = 'DESCRIBE <' . $this->escapeIri($iri) . '>';
        $fullQuery = $this->getPrefixes() . $sparql;

        $responseBody = $this->executeConstructOrDescribe($fullQuery);

        return $this->parseNTriplesResponse($responseBody);
    }

    // =========================================================================
    // Write Methods
    // =========================================================================

    public function insert(array $triples, string $userId, string $reason): bool
    {
        if (empty($triples)) {
            return true;
        }

        $tripleLines = $this->buildTripleLines($triples);
        $provenanceLines = $this->buildProvenanceAnnotations($triples, $userId, $reason);

        $sparql = $this->getPrefixes()
            . "INSERT DATA {\n"
            . $tripleLines
            . $provenanceLines
            . "}\n";

        $this->executeUpdate($sparql);

        return true;
    }

    public function update(string $subjectIri, array $oldTriples, array $newTriples, string $userId, string $reason): bool
    {
        $deleteLines = $this->buildTripleLines($oldTriples);
        $insertLines = $this->buildTripleLines($newTriples);
        $provenanceLines = $this->buildProvenanceAnnotations($newTriples, $userId, $reason);

        $sparql = $this->getPrefixes()
            . "DELETE DATA {\n"
            . $deleteLines
            . "} ;\n"
            . "INSERT DATA {\n"
            . $insertLines
            . $provenanceLines
            . "}\n";

        $this->executeUpdate($sparql);

        return true;
    }

    public function delete(string $subjectIri, string $userId, string $reason): bool
    {
        $escapedIri = $this->escapeIri($subjectIri);
        $now = Carbon::now()->toIso8601String();
        $userIri = $this->escapeIri($this->userBaseUri . '/' . $userId);

        $sparql = $this->getPrefixes()
            . "DELETE {\n"
            . "  <{$escapedIri}> ?p ?o .\n"
            . "}\n"
            . "WHERE {\n"
            . "  <{$escapedIri}> ?p ?o .\n"
            . "} ;\n"
            . "INSERT DATA {\n"
            . "  << <{$escapedIri}> openric:wasDeletedBy <{$userIri}> >>\n"
            . "    openric:modifiedBy <{$userIri}> ;\n"
            . "    openric:modifiedAt \"{$now}\"^^xsd:dateTime ;\n"
            . "    openric:changeReason \"" . $this->escapeLiteral($reason) . "\" .\n"
            . "}\n";

        $this->executeUpdate($sparql);

        return true;
    }

    public function deleteTriples(array $triples, string $userId, string $reason): bool
    {
        if (empty($triples)) {
            return true;
        }

        $tripleLines = $this->buildTripleLines($triples);
        $provenanceLines = $this->buildDeletionProvenanceAnnotations($triples, $userId, $reason);

        $sparql = $this->getPrefixes()
            . "DELETE DATA {\n"
            . $tripleLines
            . "} ;\n"
            . "INSERT DATA {\n"
            . $provenanceLines
            . "}\n";

        $this->executeUpdate($sparql);

        return true;
    }

    // =========================================================================
    // Entity Methods
    // =========================================================================

    public function createEntity(string $type, array $properties, string $userId, string $reason): string
    {
        $iri = $this->generateIri($type);
        $escapedIri = $this->escapeIri($iri);
        $now = Carbon::now()->toIso8601String();
        $userIri = $this->escapeIri($this->userBaseUri . '/' . $userId);

        $triples = [];
        $sparqlBody = "  <{$escapedIri}> a rico:" . $this->escapeLiteral($type) . " ;\n"
            . "    dcterms:created \"{$now}\"^^xsd:dateTime";

        foreach ($properties as $predicate => $value) {
            $objectStr = $this->formatObject($value);
            $sparqlBody .= " ;\n    {$predicate} {$objectStr}";

            $triples[] = [
                'subject' => $iri,
                'predicate' => $predicate,
                'object' => (string) $value,
            ];
        }

        $sparqlBody .= " .\n";

        // Build provenance for the type triple and all property triples
        $allTriples = array_merge(
            [['subject' => $iri, 'predicate' => 'rdf:type', 'object' => 'rico:' . $type]],
            $triples,
        );
        $provenanceLines = $this->buildProvenanceAnnotations($allTriples, $userId, $reason);

        $sparql = $this->getPrefixes()
            . "INSERT DATA {\n"
            . $sparqlBody
            . $provenanceLines
            . "}\n";

        $this->executeUpdate($sparql);

        return $iri;
    }

    public function getEntity(string $iri): ?array
    {
        $sparql = "SELECT ?predicate ?object WHERE {\n"
            . "  ?subject ?predicate ?object .\n"
            . "}\n"
            . "LIMIT 500\n";

        $results = $this->select($sparql, ['subject' => $iri]);

        if (empty($results)) {
            return null;
        }

        $entity = [
            'iri' => $iri,
            'properties' => [],
        ];

        foreach ($results as $row) {
            $predicate = is_array($row['predicate'] ?? '') ? ($row['predicate']['value'] ?? '') : ($row['predicate'] ?? '');
            $object = $row['object'] ?? ['value' => ''];

            if ($predicate === '') {
                continue;
            }

            if (! isset($entity['properties'][$predicate])) {
                $entity['properties'][$predicate] = [];
            }
            $entity['properties'][$predicate][] = $object;
        }

        return $entity;
    }

    public function updateEntity(string $iri, array $properties, string $userId, string $reason): bool
    {
        $escapedIri = $this->escapeIri($iri);

        // First, retrieve existing values for the properties being updated
        $predicateFilters = [];
        foreach (array_keys($properties) as $index => $predicate) {
            $predicateFilters[] = "{ <{$escapedIri}> {$predicate} ?o{$index} }";
        }

        if (empty($predicateFilters)) {
            return true;
        }

        // Build DELETE/INSERT for each property
        $deleteBody = '';
        $insertBody = '';
        $whereBody = '';

        $predicateKeys = array_keys($properties);
        foreach ($predicateKeys as $index => $predicate) {
            $varName = "?oldVal{$index}";
            $deleteBody .= "  <{$escapedIri}> {$predicate} {$varName} .\n";
            $whereBody .= "  OPTIONAL { <{$escapedIri}> {$predicate} {$varName} }\n";
            $insertBody .= "  <{$escapedIri}> {$predicate} " . $this->formatObject($properties[$predicate]) . " .\n";
        }

        $now = Carbon::now()->toIso8601String();
        $userIri = $this->escapeIri($this->userBaseUri . '/' . $userId);

        // Build provenance for the new triples
        $newTriples = [];
        foreach ($properties as $predicate => $value) {
            $newTriples[] = [
                'subject' => $iri,
                'predicate' => $predicate,
                'object' => (string) $value,
            ];
        }
        $provenanceLines = $this->buildProvenanceAnnotations($newTriples, $userId, $reason);

        $sparql = $this->getPrefixes()
            . "DELETE {\n"
            . $deleteBody
            . "}\n"
            . "INSERT {\n"
            . $insertBody
            . $provenanceLines
            . "}\n"
            . "WHERE {\n"
            . $whereBody
            . "}\n";

        $this->executeUpdate($sparql);

        return true;
    }

    public function deleteEntity(string $iri, string $userId, string $reason): bool
    {
        return $this->delete($iri, $userId, $reason);
    }

    // =========================================================================
    // Relationship Methods
    // =========================================================================

    public function createRelationship(string $subject, string $predicate, string $object, string $userId, string $reason): bool
    {
        $triples = [
            [
                'subject' => $subject,
                'predicate' => $predicate,
                'object' => $object,
            ],
        ];

        return $this->insert($triples, $userId, $reason);
    }

    public function deleteRelationship(string $subject, string $predicate, string $object, string $userId, string $reason): bool
    {
        $triples = [
            [
                'subject' => $subject,
                'predicate' => $predicate,
                'object' => $object,
            ],
        ];

        return $this->deleteTriples($triples, $userId, $reason);
    }

    public function getRelationships(string $iri, int $limit = 100): array
    {
        $sparql = "SELECT ?subject ?predicate ?object ?direction WHERE {\n"
            . "  {\n"
            . "    BIND(?entityIri AS ?subject)\n"
            . "    ?subject ?predicate ?object .\n"
            . "    BIND(\"outgoing\" AS ?direction)\n"
            . "    FILTER(isIRI(?object))\n"
            . "  }\n"
            . "  UNION\n"
            . "  {\n"
            . "    BIND(?entityIri AS ?object)\n"
            . "    ?subject ?predicate ?object .\n"
            . "    BIND(\"incoming\" AS ?direction)\n"
            . "    FILTER(isIRI(?subject))\n"
            . "  }\n"
            . "}\n"
            . "LIMIT " . (int) $limit . "\n";

        return $this->select($sparql, ['entityIri' => $iri]);
    }

    // =========================================================================
    // Health & Utility Methods
    // =========================================================================

    public function health(): array
    {
        $startTime = microtime(true);

        try {
            $sparql = $this->getPrefixes() . "ASK WHERE { ?s ?p ?o } LIMIT 1";
            $responseBody = $this->executeQuery($sparql);
            $elapsed = (microtime(true) - $startTime) * 1000;
            $decoded = json_decode($responseBody, true);

            return [
                'available' => is_array($decoded) && isset($decoded['boolean']),
                'endpoint' => $this->endpoint,
                'response_time_ms' => round($elapsed, 2),
            ];
        } catch (TriplestoreException $e) {
            $elapsed = (microtime(true) - $startTime) * 1000;

            return [
                'available' => false,
                'endpoint' => $this->endpoint,
                'response_time_ms' => round($elapsed, 2),
                'error' => $e->getMessage(),
            ];
        }
    }

    public function countTriples(): int
    {
        $sparql = "SELECT (COUNT(*) AS ?count) WHERE { ?s ?p ?o }";
        $results = $this->select($sparql);

        if (empty($results) || ! isset($results[0]['count'])) {
            return 0;
        }

        $countVal = $results[0]['count'];

        return (int) (is_array($countVal) ? ($countVal['value'] ?? 0) : $countVal);
    }

    public function generateIri(string $type): string
    {
        $uuid = (string) Str::uuid();
        $typeLower = Str::lower($type);

        return $this->baseUri . '/' . $typeLower . '/' . $uuid;
    }

    public function getPrefixes(): string
    {
        if ($this->cachedPrefixBlock !== null) {
            return $this->cachedPrefixBlock;
        }

        $lines = [];
        foreach ($this->prefixes as $prefix => $uri) {
            $lines[] = "PREFIX {$prefix}: <{$uri}>";
        }

        $this->cachedPrefixBlock = implode("\n", $lines) . "\n\n";

        return $this->cachedPrefixBlock;
    }

    // =========================================================================
    // Private: HTTP Communication
    // =========================================================================

    /**
     * Execute a SPARQL query (SELECT/ASK) against the Fuseki query endpoint.
     *
     * @throws TriplestoreException on HTTP or connection errors
     */
    private function executeQuery(string $sparql): string
    {
        $lastException = null;
        $maxRetries = 2;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = $this->client->post($this->queryUrl, [
                    'headers' => [
                        'Content-Type' => 'application/sparql-query',
                        'Accept' => 'application/json',
                    ],
                    'body' => $sparql,
                ]);

                $statusCode = $response->getStatusCode();
                $body = (string) $response->getBody();

                if ($statusCode < 200 || $statusCode >= 300) {
                    throw TriplestoreException::httpError($statusCode, $body, $sparql);
                }

                return $body;
            } catch (GuzzleException $e) {
                $lastException = $e;
                if ($attempt < $maxRetries) {
                    usleep(500000); // 500ms before retry
                }
            }
        }

        throw TriplestoreException::connectionFailed($this->queryUrl, $lastException);
    }

    /**
     * Execute a SPARQL CONSTRUCT or DESCRIBE query, accepting N-Triples.
     *
     * @throws TriplestoreException on HTTP or connection errors
     */
    private function executeConstructOrDescribe(string $sparql): string
    {
        try {
            $response = $this->client->post($this->queryUrl, [
                'headers' => [
                    'Content-Type' => 'application/sparql-query',
                    'Accept' => 'application/n-triples',
                ],
                'body' => $sparql,
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($statusCode < 200 || $statusCode >= 300) {
                throw TriplestoreException::httpError($statusCode, $body, $sparql);
            }

            return $body;
        } catch (GuzzleException $e) {
            throw TriplestoreException::connectionFailed($this->queryUrl, $e);
        }
    }

    /**
     * Execute a SPARQL UPDATE (INSERT/DELETE) against the Fuseki update endpoint.
     *
     * @throws TriplestoreException on HTTP or connection errors
     */
    private function executeUpdate(string $sparql): void
    {
        try {
            $response = $this->client->post($this->updateUrl, [
                'headers' => [
                    'Content-Type' => 'application/sparql-update',
                ],
                'body' => $sparql,
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($statusCode < 200 || $statusCode >= 300) {
                throw TriplestoreException::httpError($statusCode, $body, $sparql);
            }
        } catch (GuzzleException $e) {
            throw TriplestoreException::connectionFailed($this->updateUrl, $e);
        }
    }

    // =========================================================================
    // Private: Parameter Binding
    // =========================================================================

    /**
     * Bind parameters into a SPARQL query, replacing ?VAR placeholders.
     *
     * IRIs are wrapped in <>, string literals are escaped and quoted.
     * This ensures user input is never string-interpolated directly into SPARQL.
     *
     * @param  string               $sparql  the SPARQL template with ?VAR placeholders
     * @param  array<string, mixed> $params  variable name => value
     */
    private function bindParams(string $sparql, array $params): string
    {
        foreach ($params as $name => $value) {
            $cleanName = ltrim($name, '?');
            $replacement = $this->formatBindingValue($value);

            // Replace ?varName ensuring it is not part of a longer variable name
            // Uses word boundary to avoid partial matches
            $pattern = '/\?' . preg_quote($cleanName, '/') . '\b/';
            $sparql = preg_replace($pattern, $replacement, $sparql);
        }

        return $sparql;
    }

    /**
     * Format a parameter value for SPARQL binding.
     *
     * Strings starting with 'http://' or 'https://' are treated as IRIs.
     * Booleans produce xsd:boolean typed literals.
     * Integers produce xsd:integer typed literals.
     * Floats produce xsd:decimal typed literals.
     * All other strings are escaped and double-quoted.
     */
    private function formatBindingValue(mixed $value): string
    {
        if (is_bool($value)) {
            return '"' . ($value ? 'true' : 'false') . '"^^xsd:boolean';
        }

        if (is_int($value)) {
            return '"' . $value . '"^^xsd:integer';
        }

        if (is_float($value)) {
            return '"' . $value . '"^^xsd:decimal';
        }

        $stringValue = (string) $value;

        // Numeric strings (for LIMIT, OFFSET) — pass as bare integers
        if (ctype_digit($stringValue)) {
            return $stringValue;
        }

        // IRIs are wrapped in angle brackets
        if (str_starts_with($stringValue, 'http://') || str_starts_with($stringValue, 'https://')) {
            return '<' . $this->escapeIri($stringValue) . '>';
        }

        // Prefixed names (e.g. rico:RecordSet) are passed through as-is
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9]*:[a-zA-Z]/', $stringValue)) {
            return $stringValue;
        }

        return '"' . $this->escapeLiteral($stringValue) . '"';
    }

    // =========================================================================
    // Private: Triple Formatting
    // =========================================================================

    /**
     * Build SPARQL triple lines from an array of triple arrays.
     *
     * @param  array<int, array<string, string>> $triples
     */
    private function buildTripleLines(array $triples): string
    {
        $lines = '';
        foreach ($triples as $triple) {
            $subject = $this->formatTriplePart($triple['subject']);
            $predicate = $this->formatTriplePart($triple['predicate']);
            $object = $this->formatObject($triple['object']);
            $lines .= "  {$subject} {$predicate} {$object} .\n";
        }

        return $lines;
    }

    /**
     * Format a subject or predicate for a SPARQL triple.
     * IRIs are wrapped in <>, prefixed names are passed through.
     */
    private function formatTriplePart(string $value): string
    {
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return '<' . $this->escapeIri($value) . '>';
        }

        return $value;
    }

    /**
     * Format an object value for a SPARQL triple.
     * Handles IRIs, prefixed names, typed literals, and plain strings.
     */
    private function formatObject(mixed $value): string
    {
        if (is_bool($value)) {
            return '"' . ($value ? 'true' : 'false') . '"^^xsd:boolean';
        }

        if (is_int($value)) {
            return '"' . $value . '"^^xsd:integer';
        }

        if (is_float($value)) {
            return '"' . $value . '"^^xsd:decimal';
        }

        $stringValue = (string) $value;

        // IRIs
        if (str_starts_with($stringValue, 'http://') || str_starts_with($stringValue, 'https://')) {
            return '<' . $this->escapeIri($stringValue) . '>';
        }

        // Prefixed names (e.g. rico:RecordSet)
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9]*:[a-zA-Z]/', $stringValue)) {
            return $stringValue;
        }

        // Typed literals (e.g. "2024-01-01"^^xsd:date)
        if (preg_match('/^".*"\^\^/', $stringValue)) {
            return $stringValue;
        }

        return '"' . $this->escapeLiteral($stringValue) . '"';
    }

    // =========================================================================
    // Private: RDF-Star Provenance
    // =========================================================================

    /**
     * Build RDF-Star provenance annotations for inserted triples.
     *
     * Each triple gets annotated with:
     *   << <subject> <predicate> <object> >>
     *     openric:modifiedBy <user-iri> ;
     *     openric:modifiedAt "timestamp"^^xsd:dateTime ;
     *     openric:changeReason "reason" .
     *
     * @param  array<int, array<string, string>> $triples
     */
    private function buildProvenanceAnnotations(array $triples, string $userId, string $reason): string
    {
        $now = Carbon::now()->toIso8601String();
        $userIri = $this->escapeIri($this->userBaseUri . '/' . $userId);
        $escapedReason = $this->escapeLiteral($reason);

        $lines = '';
        foreach ($triples as $triple) {
            $subject = $this->formatTriplePart($triple['subject']);
            $predicate = $this->formatTriplePart($triple['predicate']);
            $object = $this->formatObject($triple['object']);

            $lines .= "  << {$subject} {$predicate} {$object} >>\n"
                . "    openric:modifiedBy <{$userIri}> ;\n"
                . "    openric:modifiedAt \"{$now}\"^^xsd:dateTime ;\n"
                . "    openric:changeReason \"{$escapedReason}\" .\n";
        }

        return $lines;
    }

    /**
     * Build RDF-Star provenance annotations for deleted triples.
     * Records that the triples were deleted, by whom, and why.
     *
     * @param  array<int, array<string, string>> $triples
     */
    private function buildDeletionProvenanceAnnotations(array $triples, string $userId, string $reason): string
    {
        $now = Carbon::now()->toIso8601String();
        $userIri = $this->escapeIri($this->userBaseUri . '/' . $userId);
        $escapedReason = $this->escapeLiteral($reason);

        $lines = '';
        foreach ($triples as $triple) {
            $subject = $this->formatTriplePart($triple['subject']);
            $predicate = $this->formatTriplePart($triple['predicate']);
            $object = $this->formatObject($triple['object']);

            $lines .= "  << {$subject} {$predicate} {$object} >>\n"
                . "    openric:deletedBy <{$userIri}> ;\n"
                . "    openric:deletedAt \"{$now}\"^^xsd:dateTime ;\n"
                . "    openric:changeReason \"{$escapedReason}\" .\n";
        }

        return $lines;
    }

    // =========================================================================
    // Private: Escaping & Parsing
    // =========================================================================

    /**
     * Escape a string literal for safe inclusion in SPARQL.
     * Handles quotes, backslashes, newlines, and other control characters.
     */
    private function escapeLiteral(string $value): string
    {
        return str_replace(
            ['\\', '"', "\n", "\r", "\t"],
            ['\\\\', '\\"', '\\n', '\\r', '\\t'],
            $value,
        );
    }

    /**
     * Escape an IRI for safe inclusion in SPARQL angle brackets.
     * Removes characters that are invalid in IRIs.
     */
    private function escapeIri(string $iri): string
    {
        // Remove angle brackets if already present
        $iri = trim($iri, '<>');

        // Remove characters that are illegal in IRIs within SPARQL
        return str_replace(
            ['<', '>', '"', '{', '}', '|', '\\', '^', '`'],
            '',
            $iri,
        );
    }

    /**
     * Extract a usable value from a SPARQL JSON binding object.
     *
     * @param  array<string, string> $valueObj  e.g. {'type': 'uri', 'value': '...'}
     */
    private function extractBindingValue(array $valueObj): array
    {
        return $valueObj;
    }

    /**
     * Parse an N-Triples response into an array of triple arrays.
     *
     * Each line in N-Triples format:
     *   <subject> <predicate> <object> .
     *
     * @return array<int, array<string, string>>
     */
    private function parseNTriplesResponse(string $body): array
    {
        $triples = [];
        $lines = explode("\n", trim($body));

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Match N-Triples pattern: <subject> <predicate> <object> .
            // Also handles literal objects: <s> <p> "value"^^<type> .
            if (preg_match('/^<([^>]+)>\s+<([^>]+)>\s+(.+)\s+\.\s*$/', $line, $matches)) {
                $object = $matches[3];

                // Clean up object: remove surrounding <> for IRIs
                if (str_starts_with($object, '<') && str_ends_with($object, '>')) {
                    $object = substr($object, 1, -1);
                }
                // Remove surrounding quotes for literals, preserving datatype
                if (str_starts_with($object, '"')) {
                    // Extract value from quoted literal
                    if (preg_match('/^"((?:[^"\\\\]|\\\\.)*)"(?:@([a-zA-Z-]+)|\^\^<([^>]+)>)?$/', $object, $litMatches)) {
                        $object = stripslashes($litMatches[1]);
                    }
                }

                $triples[] = [
                    'subject' => $matches[1],
                    'predicate' => $matches[2],
                    'object' => $object,
                ];
            }
        }

        return $triples;
    }
}
