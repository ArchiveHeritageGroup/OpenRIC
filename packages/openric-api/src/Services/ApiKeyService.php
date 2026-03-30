<?php

declare(strict_types=1);

namespace OpenRic\Api\Services;

use OpenRic\Api\Contracts\ApiKeyServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;
use Illuminate\Support\Str;

/**
 * API Key Service for managing API authentication.
 * Implements the ApiKeyServiceInterface for proper dependency injection.
 */
class ApiKeyService implements ApiKeyServiceInterface
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore
    ) {}

    /**
     * Create a new API key.
     */
    public function createKey(array $data): string
    {
        $key = Str::random(64);
        $keyIri = $this->triplestore->generateIri('ApiKey');

        $triples = [
            ['subject' => $keyIri, 'predicate' => 'a', 'object' => 'rico:ApiKey'],
            ['subject' => $keyIri, 'predicate' => 'rico:hasValue', 'object' => hash('sha256', $key)],
            ['subject' => $keyIri, 'predicate' => 'dcterms:title', 'object' => $data['name'] ?? 'API Key'],
            ['subject' => $keyIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'active@en'],
            ['subject' => $keyIri, 'predicate' => 'dcterms:created', 'object' => '"' . now()->toIso8601String() . '"^^xsd:dateTime'],
        ];

        // Add scopes
        if (!empty($data['scopes'])) {
            foreach ((array) $data['scopes'] as $scope) {
                $triples[] = ['subject' => $keyIri, 'predicate' => 'rico:hasOrHadScope', 'object' => $scope . '@en'];
            }
        }

        $this->triplestore->insert($triples, $data['user_id'] ?? 'system', 'Created API key');

        // Return the actual key (only time it's shown)
        return $key;
    }

    /**
     * Validate an API key.
     */
    public function validateKey(string $key): ?array
    {
        $keyHash = hash('sha256', $key);
        $escapedHash = '<' . $keyHash . '>';

        $sparql = <<<SPARQL
SELECT ?key ?user ?scopes
WHERE {
    ?key a rico:ApiKey .
    ?key rico:hasValue {$escapedHash} .
    ?key rico:hasOrHadStatus "active"@en .
    OPTIONAL { ?key rico:isOrWasRelatedTo ?user }
    OPTIONAL { ?key rico:hasOrHadScope ?scope }
}
LIMIT 1
SPARQL;

        $results = $this->triplestore->select($sparql);

        if (empty($results)) {
            return null;
        }

        return [
            'iri' => $results[0]['key']['value'] ?? $results[0]['key'],
            'user' => $results[0]['user']['value'] ?? $results[0]['user'] ?? null,
        ];
    }

    /**
     * Revoke an API key.
     */
    public function revokeKey(string $keyIri): bool
    {
        $oldTriples = [
            ['subject' => $keyIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'active@en'],
        ];

        $newTriples = [
            ['subject' => $keyIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'revoked@en'],
        ];

        return $this->triplestore->update($keyIri, $oldTriples, $newTriples, 'system', 'Revoked API key');
    }
}
