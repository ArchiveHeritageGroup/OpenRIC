<?php

declare(strict_types=1);

namespace OpenRiC\ActivityManage\Services;

use OpenRiC\ActivityManage\Contracts\MandateServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class MandateService implements MandateServiceInterface
{
    private const RDF_TYPE = 'rico:Mandate';

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

        if (!empty($filters['mandate_type'])) {
            $filterClauses .= '?iri rico:hasMandateType ?filterMandateType .' . "\n";
            $bindings['filterMandateType'] = $filters['mandate_type'];
        }

        if (!empty($filters['agent_iri'])) {
            $filterClauses .= '?iri rico:isOrWasRegulatedBy ?filterAgentIri .' . "\n";
            $bindings['filterAgentIri'] = $filters['agent_iri'];
        }

        $sparql = <<<SPARQL
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>
            PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

            SELECT ?iri ?title ?identifier ?mandateType ?beginningDate ?endDate WHERE {
                ?iri a rico:Mandate .
                OPTIONAL { ?iri rico:title ?title }
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:hasMandateType ?mandateType }
                OPTIONAL { ?iri rico:beginningDate ?beginningDate }
                OPTIONAL { ?iri rico:endDate ?endDate }
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
                ?iri a rico:Mandate .
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

        // Fetch linked agents (regulated by)
        $agentsSparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT ?agentIri ?title ?identifier WHERE {
                ?iri rico:isOrWasRegulatedBy ?agentIri .
                OPTIONAL { ?agentIri rico:title ?title }
                OPTIONAL { ?agentIri rico:identifier ?identifier }
            }
            ORDER BY ?title
            SPARQL;

        $entity['linkedAgents'] = $this->triplestore->select($agentsSparql, ['iri' => $iri]);

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

    public function getForAgent(string $agentIri, int $limit = 25, int $offset = 0): array
    {
        $sparql = <<<'SPARQL'
            PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

            SELECT ?iri ?title ?identifier ?mandateType ?beginningDate ?endDate WHERE {
                ?iri a rico:Mandate .
                ?iri rico:isOrWasRegulatedBy ?agentIri .
                OPTIONAL { ?iri rico:title ?title }
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:hasMandateType ?mandateType }
                OPTIONAL { ?iri rico:beginningDate ?beginningDate }
                OPTIONAL { ?iri rico:endDate ?endDate }
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
                ?iri a rico:Mandate .
                ?iri rico:isOrWasRegulatedBy ?agentIri .
            }
            SPARQL;

        $countResult = $this->triplestore->select($countSparql, ['agentIri' => $agentIri]);
        $total = (int) ($countResult[0]['count']['value'] ?? 0);

        return ['items' => $items, 'total' => $total];
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
            'descriptive_note' => 'rico:descriptiveNote',
            'beginning_date'   => 'rico:beginningDate',
            'end_date'         => 'rico:endDate',
        ];

        foreach ($literalFields as $formKey => $ricoProperty) {
            if (isset($data[$formKey]) && $data[$formKey] !== '') {
                $properties[$ricoProperty] = ['value' => (string) $data[$formKey], 'datatype' => 'xsd:string'];
            }
        }

        $iriFields = [
            'mandate_type' => 'rico:hasMandateType',
            'date_iri'     => 'rico:hasOrHadDate',
        ];

        foreach ($iriFields as $formKey => $ricoProperty) {
            if (isset($data[$formKey]) && $data[$formKey] !== '') {
                $properties[$ricoProperty] = ['value' => (string) $data[$formKey], 'type' => 'uri'];
            }
        }

        // Handle multiple regulated-by agents
        if (!empty($data['regulated_by_iris']) && is_array($data['regulated_by_iris'])) {
            $iris = array_filter($data['regulated_by_iris'], fn (string $v): bool => $v !== '');
            if (!empty($iris)) {
                $properties['rico:isOrWasRegulatedBy'] = array_map(
                    fn (string $agentIri): array => ['value' => $agentIri, 'type' => 'uri'],
                    array_values($iris),
                );
            }
        }

        return $properties;
    }
}
