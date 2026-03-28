<?php

declare(strict_types=1);

namespace OpenRiC\PlaceManage\Services;

use OpenRiC\PlaceManage\Contracts\PlaceServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class PlaceService implements PlaceServiceInterface
{
    private const RDF_TYPE = 'rico:Place';

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $filterClauses = '';
        $bindings = [
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        ];

        if (!empty($filters['q'])) {
            $filterClauses .= 'FILTER(CONTAINS(LCASE(?title), LCASE(?searchTerm)))' . "\n";
            $bindings['searchTerm'] = $filters['q'];
        }

        if (!empty($filters['place_type'])) {
            $filterClauses .= '?iri rico:hasPlaceType ?filterPlaceType .' . "\n";
            $bindings['filterPlaceType'] = $filters['place_type'];
        }

        if (!empty($filters['country_code'])) {
            $filterClauses .= '?iri rico:countryCode ?filterCountryCode .' . "\n";
            $bindings['filterCountryCode'] = $filters['country_code'];
        }

        if (!empty($filters['parent_iri'])) {
            $filterClauses .= '?iri rico:hasOrHadLocation ?filterParentIri .' . "\n";
            $bindings['filterParentIri'] = $filters['parent_iri'];
        }

        $sparql = <<<SPARQL
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>
            PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

            SELECT ?iri ?title ?identifier ?placeType ?latitude ?longitude ?parentPlace ?countryCode WHERE {
                ?iri a rico:Place .
                OPTIONAL { ?iri rico:title ?title }
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:hasPlaceType ?placeType }
                OPTIONAL { ?iri rico:latitude ?latitude }
                OPTIONAL { ?iri rico:longitude ?longitude }
                OPTIONAL { ?iri rico:hasOrHadLocation ?parentPlace }
                OPTIONAL { ?iri rico:countryCode ?countryCode }
                {$filterClauses}
            }
            ORDER BY ?title
            LIMIT ?limit OFFSET ?offset
            SPARQL;

        $items = $this->triplestore->select($sparql, $bindings);

        $countFilterClauses = $filterClauses;
        $countBindings = $bindings;
        unset($countBindings['limit'], $countBindings['offset']);

        $countSparql = <<<SPARQL
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT (COUNT(DISTINCT ?iri) AS ?count) WHERE {
                ?iri a rico:Place .
                OPTIONAL { ?iri rico:title ?title }
                {$countFilterClauses}
            }
            SPARQL;

        $countResult = $this->triplestore->select($countSparql, $countBindings);
        $total = (int) ($countResult[0]['count']['value'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    public function find(string $iri): ?array
    {
        $sparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT ?predicate ?object WHERE {
                ?iri ?predicate ?object .
            }
            SPARQL;

        $triples = $this->triplestore->select($sparql, ['iri' => $iri]);

        if (empty($triples)) {
            return null;
        }

        $entity = ['iri' => $iri, 'properties' => []];
        foreach ($triples as $triple) {
            $pred = $triple['predicate']['value'] ?? '';
            $obj = $triple['object']['value'] ?? '';
            $entity['properties'][$pred][] = $obj;
        }

        // Fetch linked records (places where records are located)
        $recordsSparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT ?recordIri ?title ?identifier WHERE {
                ?iri rico:isOrWasLocationOf ?recordIri .
                ?recordIri a rico:Record .
                OPTIONAL { ?recordIri rico:title ?title }
                OPTIONAL { ?recordIri rico:identifier ?identifier }
            }
            ORDER BY ?title
            SPARQL;

        $entity['linkedRecords'] = $this->triplestore->select($recordsSparql, ['iri' => $iri]);

        // Fetch linked agents (jurisdictions)
        $agentsSparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT ?agentIri ?title ?identifier WHERE {
                ?iri rico:isOrWasJurisdictionOf ?agentIri .
                OPTIONAL { ?agentIri rico:title ?title }
                OPTIONAL { ?agentIri rico:identifier ?identifier }
            }
            ORDER BY ?title
            SPARQL;

        $entity['linkedAgents'] = $this->triplestore->select($agentsSparql, ['iri' => $iri]);

        // Fetch child places
        $entity['children'] = $this->getChildren($iri);

        // Fetch ancestors breadcrumb
        $entity['ancestors'] = $this->getAncestors($iri);

        return $entity;
    }

    public function create(array $data, string $userId, string $reason): string
    {
        $properties = $this->buildProperties($data);

        return $this->triplestore->createEntity(self::RDF_TYPE, $properties, $userId, $reason);
    }

    public function update(string $iri, array $data, string $userId, string $reason): bool
    {
        $properties = $this->buildProperties($data);

        return $this->triplestore->updateEntity($iri, $properties, $userId, $reason);
    }

    public function delete(string $iri, string $userId, string $reason): bool
    {
        return $this->triplestore->deleteEntity($iri, $userId, $reason);
    }

    public function getChildren(string $parentIri): array
    {
        $sparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT ?iri ?title ?identifier ?placeType WHERE {
                ?iri a rico:Place .
                ?iri rico:hasOrHadLocation ?parentIri .
                OPTIONAL { ?iri rico:title ?title }
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:hasPlaceType ?placeType }
            }
            ORDER BY ?title
            SPARQL;

        return $this->triplestore->select($sparql, ['parentIri' => $parentIri]);
    }

    public function getAncestors(string $iri): array
    {
        $ancestors = [];
        $currentIri = $iri;
        $visited = [];
        $maxDepth = 20;

        while ($maxDepth > 0) {
            $maxDepth--;

            $sparql = <<<'SPARQL'
                PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

                SELECT ?parentIri ?title WHERE {
                    ?currentIri rico:hasOrHadLocation ?parentIri .
                    OPTIONAL { ?parentIri rico:title ?title }
                }
                LIMIT 1
                SPARQL;

            $result = $this->triplestore->select($sparql, ['currentIri' => $currentIri]);

            if (empty($result)) {
                break;
            }

            $parentIri = $result[0]['parentIri']['value'] ?? '';
            if ($parentIri === '' || isset($visited[$parentIri])) {
                break;
            }

            $visited[$parentIri] = true;
            $ancestors[] = [
                'iri' => $parentIri,
                'title' => $result[0]['title']['value'] ?? $parentIri,
            ];
            $currentIri = $parentIri;
        }

        return array_reverse($ancestors);
    }

    public function getForAgent(string $agentIri, int $limit = 25, int $offset = 0): array
    {
        $sparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT ?iri ?title ?identifier ?placeType WHERE {
                ?iri a rico:Place .
                ?iri rico:isOrWasJurisdictionOf ?agentIri .
                OPTIONAL { ?iri rico:title ?title }
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:hasPlaceType ?placeType }
            }
            ORDER BY ?title
            LIMIT ?limit OFFSET ?offset
            SPARQL;

        $items = $this->triplestore->select($sparql, [
            'agentIri' => $agentIri,
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        ]);

        $countSparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT (COUNT(?iri) AS ?count) WHERE {
                ?iri a rico:Place .
                ?iri rico:isOrWasJurisdictionOf ?agentIri .
            }
            SPARQL;

        $countResult = $this->triplestore->select($countSparql, ['agentIri' => $agentIri]);
        $total = (int) ($countResult[0]['count']['value'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    public function autocomplete(string $query, int $limit = 10): array
    {
        $sparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT ?iri ?title ?placeType ?countryCode WHERE {
                ?iri a rico:Place .
                ?iri rico:title ?title .
                FILTER(CONTAINS(LCASE(?title), LCASE(?query)))
                OPTIONAL { ?iri rico:hasPlaceType ?placeType }
                OPTIONAL { ?iri rico:countryCode ?countryCode }
            }
            ORDER BY ?title
            LIMIT ?limit
            SPARQL;

        return $this->triplestore->select($sparql, [
            'query' => $query,
            'limit' => (string) $limit,
        ]);
    }

    public function getPlaceTypes(): array
    {
        $sparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT ?iri (SAMPLE(?label) AS ?label) (COUNT(?place) AS ?count) WHERE {
                ?place a rico:Place .
                ?place rico:hasPlaceType ?iri .
                OPTIONAL { ?iri rico:title ?label }
            }
            GROUP BY ?iri
            ORDER BY DESC(?count)
            SPARQL;

        return $this->triplestore->select($sparql);
    }

    /**
     * Build the RiC-O property map from incoming form data.
     *
     * @param array<string, mixed> $data
     * @return array<string, array{value: string, datatype: string}|array{value: string, type: string}>
     */
    private function buildProperties(array $data): array
    {
        $properties = [];

        $literalFields = [
            'title'            => 'rico:title',
            'identifier'       => 'rico:identifier',
            'latitude'         => 'rico:latitude',
            'longitude'        => 'rico:longitude',
            'descriptive_note' => 'rico:descriptiveNote',
            'country_code'     => 'rico:countryCode',
            'postal_code'      => 'rico:postalCode',
        ];

        foreach ($literalFields as $formKey => $ricoProperty) {
            if (isset($data[$formKey]) && $data[$formKey] !== '') {
                $properties[$ricoProperty] = ['value' => (string) $data[$formKey], 'datatype' => 'xsd:string'];
            }
        }

        $iriFields = [
            'place_type'       => 'rico:hasPlaceType',
            'parent_place'     => 'rico:hasOrHadLocation',
        ];

        foreach ($iriFields as $formKey => $ricoProperty) {
            if (isset($data[$formKey]) && $data[$formKey] !== '') {
                $properties[$ricoProperty] = ['value' => (string) $data[$formKey], 'type' => 'uri'];
            }
        }

        // Handle alternate names as multiple values
        if (!empty($data['alternate_names']) && is_array($data['alternate_names'])) {
            $names = array_filter($data['alternate_names'], fn (string $name): bool => $name !== '');
            if (!empty($names)) {
                $properties['rico:hasOrHadName'] = array_map(
                    fn (string $name): array => ['value' => $name, 'datatype' => 'xsd:string'],
                    array_values($names),
                );
            }
        }

        // Handle linked agent jurisdictions
        if (!empty($data['jurisdiction_of']) && is_array($data['jurisdiction_of'])) {
            $agentIris = array_filter($data['jurisdiction_of'], fn (string $v): bool => $v !== '');
            if (!empty($agentIris)) {
                $properties['rico:isOrWasJurisdictionOf'] = array_map(
                    fn (string $agentIri): array => ['value' => $agentIri, 'type' => 'uri'],
                    array_values($agentIris),
                );
            }
        }

        // Handle linked record locations
        if (!empty($data['location_of']) && is_array($data['location_of'])) {
            $recordIris = array_filter($data['location_of'], fn (string $v): bool => $v !== '');
            if (!empty($recordIris)) {
                $properties['rico:isOrWasLocationOf'] = array_map(
                    fn (string $recordIri): array => ['value' => $recordIri, 'type' => 'uri'],
                    array_values($recordIris),
                );
            }
        }

        return $properties;
    }
}
