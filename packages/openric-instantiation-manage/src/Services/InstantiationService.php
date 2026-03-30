<?php

declare(strict_types=1);

namespace OpenRiC\InstantiationManage\Services;

use OpenRiC\InstantiationManage\Contracts\InstantiationServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class InstantiationService implements InstantiationServiceInterface
{
    private const RDF_TYPE = 'rico:Instantiation';

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

        if (!empty($filters['carrier_type'])) {
            $filterClauses .= '?iri rico:hasCarrierType ?filterCarrierType .' . "\n";
            $bindings['filterCarrierType'] = $filters['carrier_type'];
        }

        if (!empty($filters['representation_type'])) {
            $filterClauses .= '?iri rico:hasRepresentationType ?filterRepType .' . "\n";
            $bindings['filterRepType'] = $filters['representation_type'];
        }

        if (!empty($filters['record_iri'])) {
            $filterClauses .= '?iri rico:isInstantiationOf ?filterRecordIri .' . "\n";
            $bindings['filterRecordIri'] = $filters['record_iri'];
        }

        $sparql = <<<SPARQL
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>
            PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

            SELECT ?iri ?title ?identifier ?carrierType ?representationType ?recordIri ?mimeType ?dateOfInstantiation WHERE {
                ?iri a rico:Instantiation .
                OPTIONAL { ?iri rico:title ?title }
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:hasCarrierType ?carrierType }
                OPTIONAL { ?iri rico:hasRepresentationType ?representationType }
                OPTIONAL { ?iri rico:isInstantiationOf ?recordIri }
                OPTIONAL { ?iri rico:hasMimeType ?mimeType }
                OPTIONAL { ?iri rico:dateOfInstantiation ?dateOfInstantiation }
                {$filterClauses}
            }
            ORDER BY ?title
            LIMIT {$limit} OFFSET {$offset}
            SPARQL;

        $items = $this->triplestore->select($sparql, $bindings);

        $countFilterClauses = $filterClauses;
        $countBindings = $bindings;
        unset($countBindings['limit'], $countBindings['offset']);

        $countSparql = <<<SPARQL
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT (COUNT(DISTINCT ?iri) AS ?count) WHERE {
                ?iri a rico:Instantiation .
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

        $recordIris = $entity['properties']['https://www.ica.org/standards/RiC/ontology#isInstantiationOf'] ?? [];
        $entity['linkedRecords'] = [];

        foreach ($recordIris as $recordIri) {
            $recSparql = <<<'SPARQL'
                PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

                SELECT ?title ?identifier WHERE {
                    ?recordIri rico:title ?title .
                    OPTIONAL { ?recordIri rico:identifier ?identifier }
                }
                SPARQL;

            $recResult = $this->triplestore->select($recSparql, ['recordIri' => $recordIri]);

            $entity['linkedRecords'][] = [
                'iri' => $recordIri,
                'title' => $recResult[0]['title']['value'] ?? $recordIri,
                'identifier' => $recResult[0]['identifier']['value'] ?? '',
            ];
        }

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

    public function getForRecord(string $recordIri, int $limit = 25, int $offset = 0): array
    {
        $sparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT ?iri ?title ?identifier ?carrierType ?representationType ?mimeType ?dateOfInstantiation WHERE {
                ?iri a rico:Instantiation .
                ?iri rico:isInstantiationOf ?recordIri .
                OPTIONAL { ?iri rico:title ?title }
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:hasCarrierType ?carrierType }
                OPTIONAL { ?iri rico:hasRepresentationType ?representationType }
                OPTIONAL { ?iri rico:hasMimeType ?mimeType }
                OPTIONAL { ?iri rico:dateOfInstantiation ?dateOfInstantiation }
            }
            ORDER BY ?title
            LIMIT {$limit} OFFSET {$offset}
            SPARQL;

        $items = $this->triplestore->select($sparql, [
            'recordIri' => $recordIri,
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        ]);

        $countSparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT (COUNT(?iri) AS ?count) WHERE {
                ?iri a rico:Instantiation .
                ?iri rico:isInstantiationOf ?recordIri .
            }
            SPARQL;

        $countResult = $this->triplestore->select($countSparql, ['recordIri' => $recordIri]);
        $total = (int) ($countResult[0]['count']['value'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    public function getCarrierTypes(): array
    {
        $sparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT ?iri (SAMPLE(?label) AS ?label) (COUNT(?inst) AS ?count) WHERE {
                ?inst a rico:Instantiation .
                ?inst rico:hasCarrierType ?iri .
                OPTIONAL { ?iri rico:title ?label }
            }
            GROUP BY ?iri
            ORDER BY DESC(?count)
            SPARQL;

        return $this->triplestore->select($sparql);
    }

    public function getRepresentationTypes(): array
    {
        $sparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT ?iri (SAMPLE(?label) AS ?label) (COUNT(?inst) AS ?count) WHERE {
                ?inst a rico:Instantiation .
                ?inst rico:hasRepresentationType ?iri .
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
            'title'                    => 'rico:title',
            'identifier'               => 'rico:identifier',
            'extent'                   => 'rico:hasExtent',
            'mime_type'                => 'rico:hasMimeType',
            'quantity'                 => 'rico:hasQuantity',
            'condition'                => 'rico:conditionOfInstantiation',
            'date_of_instantiation'    => 'rico:dateOfInstantiation',
            'descriptive_note'         => 'rico:descriptiveNote',
        ];

        foreach ($literalFields as $formKey => $ricoProperty) {
            if (isset($data[$formKey]) && $data[$formKey] !== '') {
                $properties[$ricoProperty] = ['value' => (string) $data[$formKey], 'datatype' => 'xsd:string'];
            }
        }

        $iriFields = [
            'production_technique_type' => 'rico:hasProductionTechniqueType',
            'representation_type'       => 'rico:hasRepresentationType',
            'carrier_type'              => 'rico:hasCarrierType',
            'physical_location'         => 'rico:hasOrHadPhysicalLocation',
            'record_iri'                => 'rico:isInstantiationOf',
        ];

        foreach ($iriFields as $formKey => $ricoProperty) {
            if (isset($data[$formKey]) && $data[$formKey] !== '') {
                $properties[$ricoProperty] = ['value' => (string) $data[$formKey], 'type' => 'uri'];
            }
        }

        return $properties;
    }
}
