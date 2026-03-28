<?php

declare(strict_types=1);

namespace OpenRiC\InstantiationManage\Services;

use OpenRiC\InstantiationManage\Contracts\InstantiationServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class InstantiationService implements InstantiationServiceInterface
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?iri ?title ?identifier WHERE {
                ?iri a rico:Instantiation .
                OPTIONAL { ?iri rico:title ?title }
                OPTIONAL { ?iri rico:hasAgentName ?nameNode . ?nameNode rico:textualValue ?title }
                OPTIONAL { ?iri rico:identifier ?identifier }
            }
            ORDER BY ?title
            LIMIT ?limit OFFSET ?offset
            SPARQL;

        $items = $this->triplestore->select($sparql, [
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        ]);

        $countSparql = <<<'SPARQL'
            SELECT (COUNT(?iri) AS ?count) WHERE { ?iri a rico:Instantiation . }
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
        $properties = [];
        if (! empty($data['title'])) {
            $properties['rico:title'] = ['value' => $data['title'], 'datatype' => 'xsd:string'];
        }
        if (! empty($data['identifier'])) {
            $properties['rico:identifier'] = ['value' => $data['identifier'], 'datatype' => 'xsd:string'];
        }

        return $this->triplestore->createEntity('rico:Instantiation', $properties, $userId, $reason);
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

        return $this->triplestore->updateEntity($iri, $properties, $userId, $reason);
    }

    public function delete(string $iri, string $userId, string $reason): bool
    {
        return $this->triplestore->deleteEntity($iri, $userId, $reason);
    }
}
