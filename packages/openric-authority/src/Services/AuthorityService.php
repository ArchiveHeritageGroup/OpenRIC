<?php

declare(strict_types=1);

namespace OpenRiC\Authority\Services;

use GuzzleHttp\Client;
use OpenRiC\Authority\Contracts\AuthorityServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class AuthorityService implements AuthorityServiceInterface
{
    private Client $httpClient;

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {
        $this->httpClient = new Client(['timeout' => 10]);
    }

    public function searchWikidata(string $name, string $agentType = 'person'): array
    {
        try {
            $response = $this->httpClient->get('https://www.wikidata.org/w/api.php', [
                'query' => [
                    'action' => 'wbsearchentities',
                    'search' => $name,
                    'language' => 'en',
                    'format' => 'json',
                    'type' => 'item',
                    'limit' => 5,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return array_map(fn ($item) => [
                'uri' => "http://www.wikidata.org/entity/{$item['id']}",
                'label' => $item['label'] ?? '',
                'description' => $item['description'] ?? '',
                'source' => 'wikidata',
            ], $result['search'] ?? []);
        } catch (\Exception) {
            return [];
        }
    }

    public function searchViaf(string $name, string $agentType = 'person'): array
    {
        try {
            $cqlField = $agentType === 'corporate_body' ? 'local.corporateNames' : 'local.personalNames';
            $query = urlencode("{$cqlField} all \"{$name}\"");

            $response = $this->httpClient->get("https://viaf.org/viaf/search?query={$query}&sortKeys=holdingscount&maximumRecords=5&httpAccept=application/json");

            $result = json_decode($response->getBody()->getContents(), true);
            $records = $result['searchRetrieveResponse']['records'] ?? [];

            return array_map(fn ($record) => [
                'uri' => 'https://viaf.org/viaf/' . ($record['record']['recordData']['viafID'] ?? ''),
                'label' => $record['record']['recordData']['mainHeadings']['data'][0]['text'] ?? '',
                'description' => '',
                'source' => 'viaf',
            ], array_slice($records, 0, 5));
        } catch (\Exception) {
            return [];
        }
    }

    public function searchLcnaf(string $name): array
    {
        try {
            $response = $this->httpClient->get('https://id.loc.gov/authorities/names/suggest2', [
                'query' => ['q' => $name],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return array_map(fn ($item) => [
                'uri' => $item['uri'] ?? '',
                'label' => $item['aLabel'] ?? '',
                'description' => '',
                'source' => 'lcnaf',
            ], array_slice($result['hits'] ?? [], 0, 5));
        } catch (\Exception) {
            return [];
        }
    }

    public function linkAgent(string $agentIri, string $externalUri, string $source, string $userId, string $reason): bool
    {
        return $this->triplestore->createRelationship(
            $agentIri,
            'owl:sameAs',
            $externalUri,
            $userId,
            $reason . " (source: {$source})"
        );
    }

    public function getExternalLinks(string $iri): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?external WHERE {
                ?entityIri owl:sameAs ?external .
            }
            LIMIT 50
            SPARQL;

        return $this->triplestore->select($sparql, ['entityIri' => $iri]);
    }
}
