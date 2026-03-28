<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Services;

use OpenRiC\RecordManage\Contracts\RecordSetServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class RecordSetService implements RecordSetServiceInterface
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?iri ?title ?identifier ?level WHERE {
                ?iri a rico:RecordSet .
                ?iri rico:title ?title .
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:hasRecordSetType ?level }
            }
            ORDER BY ?title
            LIMIT ?limit OFFSET ?offset
            SPARQL;

        $items = $this->triplestore->select($sparql, [
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        ]);

        $countSparql = <<<'SPARQL'
            SELECT (COUNT(?iri) AS ?count) WHERE {
                ?iri a rico:RecordSet .
            }
            SPARQL;

        $countResult = $this->triplestore->select($countSparql);
        $total = (int) ($countResult[0]['count']['value'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    public function find(string $iri): ?array
    {
        return $this->triplestore->getEntity($iri);
    }

    public function create(array $data, string $userId, string $reason): string
    {
        $properties = [
            'rico:title' => ['value' => $data['title'], 'datatype' => 'xsd:string'],
        ];

        if (! empty($data['identifier'])) {
            $properties['rico:identifier'] = ['value' => $data['identifier'], 'datatype' => 'xsd:string'];
        }

        if (! empty($data['scope_and_content'])) {
            $properties['rico:scopeAndContent'] = ['value' => $data['scope_and_content'], 'datatype' => 'xsd:string'];
        }

        if (! empty($data['parent_iri'])) {
            $properties['rico:isOrWasIncludedIn'] = ['value' => $data['parent_iri'], 'type' => 'uri'];
        }

        return $this->triplestore->createEntity('rico:RecordSet', $properties, $userId, $reason);
    }

    public function update(string $iri, array $data, string $userId, string $reason): bool
    {
        $properties = [];

        if (isset($data['title'])) {
            $properties['rico:title'] = ['value' => $data['title'], 'datatype' => 'xsd:string'];
        }

        if (array_key_exists('identifier', $data)) {
            $properties['rico:identifier'] = ['value' => $data['identifier'] ?? '', 'datatype' => 'xsd:string'];
        }

        if (array_key_exists('scope_and_content', $data)) {
            $properties['rico:scopeAndContent'] = ['value' => $data['scope_and_content'] ?? '', 'datatype' => 'xsd:string'];
        }

        return $this->triplestore->updateEntity($iri, $properties, $userId, $reason);
    }

    public function delete(string $iri, string $userId, string $reason): bool
    {
        return $this->triplestore->deleteEntity($iri, $userId, $reason);
    }

    public function getChildren(string $iri, int $limit = 100): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?child ?title ?type WHERE {
                ?child rico:isOrWasIncludedIn ?parentIri .
                ?child rico:title ?title .
                OPTIONAL { ?child a ?type . FILTER(STRSTARTS(STR(?type), STR(rico:))) }
            }
            ORDER BY ?title
            LIMIT ?limit
            SPARQL;

        return $this->triplestore->select($sparql, [
            'parentIri' => $iri,
            'limit' => (string) $limit,
        ]);
    }

    public function getCreators(string $iri): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?agent ?name ?agentType WHERE {
                ?entityIri rico:hasOrHadCreator ?agent .
                OPTIONAL { ?agent rico:hasAgentName ?nameNode . ?nameNode rico:textualValue ?name . }
                OPTIONAL { ?agent a ?agentType . FILTER(STRSTARTS(STR(?agentType), STR(rico:))) }
            }
            LIMIT 50
            SPARQL;

        return $this->triplestore->select($sparql, ['entityIri' => $iri]);
    }
}
