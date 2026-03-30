<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Services;

use OpenRiC\RecordManage\Contracts\RecordPartServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class RecordPartService implements RecordPartServiceInterface
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?iri ?title ?identifier WHERE {
                ?iri a rico:RecordPart .
                ?iri rico:title ?title .
                OPTIONAL { ?iri rico:identifier ?identifier }
            }
            ORDER BY ?title
            LIMIT {$limit} OFFSET {$offset}
            SPARQL;

        $items = $this->triplestore->select($sparql, [
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        ]);

        $countSparql = <<<'SPARQL'
            SELECT (COUNT(?iri) AS ?count) WHERE { ?iri a rico:RecordPart . }
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

        if (! empty($data['parent_iri'])) {
            $properties['rico:isOrWasPartOf'] = ['value' => $data['parent_iri'], 'type' => 'uri'];
        }

        return $this->triplestore->createEntity('rico:RecordPart', $properties, $userId, $reason);
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
