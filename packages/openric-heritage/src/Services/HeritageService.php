<?php

declare(strict_types=1);

namespace OpenRiC\Heritage\Services;

use OpenRiC\Heritage\Contracts\HeritageServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * HeritageService — heritage object CRUD via RiC-O SPARQL.
 *
 * Adapted from Heratio ahg-heritage-manage HeritageController + HeritageSearchService.
 * Heritage objects are modelled as rico:Record with rico:hasRecordSetType indicating
 * the heritage classification (artefact, artwork, photograph, manuscript, etc.).
 */
class HeritageService implements HeritageServiceInterface
{
    protected TriplestoreServiceInterface $triplestore;

    public function __construct(TriplestoreServiceInterface $triplestore)
    {
        $this->triplestore = $triplestore;
    }

    public function browse(array $params = []): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = min(100, max(1, (int) ($params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $type = $params['type'] ?? null;
        $query = $params['query'] ?? null;
        $sort = $params['sort'] ?? 'title';

        $prefixes = $this->triplestore->getPrefixes();

        // Count query
        $countSparql = $prefixes . '
            SELECT (COUNT(DISTINCT ?s) AS ?count) WHERE {
                ?s a rico:Record .
                ?s rico:hasRecordSetType ?heritageType .
                ?s rico:title ?title .
                ' . ($type ? 'FILTER(STR(?heritageType) = ?typeFilter)' : '') . '
                ' . ($query ? 'FILTER(CONTAINS(LCASE(?title), LCASE(?queryFilter)))' : '') . '
            }
        ';

        $countParams = [];
        if ($type) {
            $countParams['typeFilter'] = $type;
        }
        if ($query) {
            $countParams['queryFilter'] = $query;
        }

        $countResult = $this->triplestore->select($countSparql, $countParams);
        $total = (int) ($countResult[0]['count'] ?? 0);
        $totalPages = $total > 0 ? (int) ceil($total / $limit) : 0;

        // Order clause
        $orderClause = match ($sort) {
            'date'  => 'ORDER BY DESC(?created)',
            'type'  => 'ORDER BY ?heritageType ?title',
            default => 'ORDER BY ?title',
        };

        // Results query
        $resultSparql = $prefixes . '
            SELECT ?s ?title ?heritageType ?description ?created WHERE {
                ?s a rico:Record .
                ?s rico:hasRecordSetType ?heritageType .
                ?s rico:title ?title .
                OPTIONAL { ?s rico:scopeAndContent ?description }
                OPTIONAL { ?s rico:dateCreated ?created }
                ' . ($type ? 'FILTER(STR(?heritageType) = ?typeFilter)' : '') . '
                ' . ($query ? 'FILTER(CONTAINS(LCASE(?title), LCASE(?queryFilter)))' : '') . '
            }
            ' . $orderClause . '
            LIMIT ' . $limit . ' OFFSET ' . $offset;

        $results = $this->triplestore->select($resultSparql, $countParams);

        $formatted = array_map(fn (array $row) => [
            'iri'          => $row['s'] ?? '',
            'title'        => $row['title'] ?? '[Untitled]',
            'heritage_type' => $row['heritageType'] ?? '',
            'description'  => $row['description'] ?? '',
            'created'      => $row['created'] ?? '',
        ], $results);

        return [
            'total'   => $total,
            'page'    => $page,
            'pages'   => $totalPages,
            'results' => $formatted,
        ];
    }

    public function find(string $iri): ?array
    {
        $entity = $this->triplestore->getEntity($iri);
        if (!$entity) {
            return null;
        }

        // Enrich with relationships
        $relationships = $this->triplestore->getRelationships($iri, 50);

        return array_merge($entity, [
            'iri'           => $iri,
            'relationships' => $relationships,
        ]);
    }

    public function create(array $data, string $userId): string
    {
        $properties = [
            'rico:title'            => $data['title'],
            'rico:hasRecordSetType' => $data['heritage_type'] ?? 'heritage:generic',
            'rico:scopeAndContent'  => $data['description'] ?? '',
            'rico:dateCreated'      => $data['date_created'] ?? now()->toDateString(),
        ];

        if (!empty($data['identifier'])) {
            $properties['rico:identifier'] = $data['identifier'];
        }
        if (!empty($data['custodian_iri'])) {
            $properties['rico:heldBy'] = $data['custodian_iri'];
        }
        if (!empty($data['extent'])) {
            $properties['rico:physicalCharacteristics'] = $data['extent'];
        }
        if (!empty($data['location'])) {
            $properties['rico:hasOrHadLocation'] = $data['location'];
        }
        if (!empty($data['condition'])) {
            $properties['rico:conditionOfItem'] = $data['condition'];
        }

        return $this->triplestore->createEntity('Record', $properties, $userId, 'Heritage object created');
    }

    public function update(string $iri, array $data, string $userId): bool
    {
        $properties = [];

        if (isset($data['title'])) {
            $properties['rico:title'] = $data['title'];
        }
        if (isset($data['heritage_type'])) {
            $properties['rico:hasRecordSetType'] = $data['heritage_type'];
        }
        if (isset($data['description'])) {
            $properties['rico:scopeAndContent'] = $data['description'];
        }
        if (isset($data['identifier'])) {
            $properties['rico:identifier'] = $data['identifier'];
        }
        if (isset($data['custodian_iri'])) {
            $properties['rico:heldBy'] = $data['custodian_iri'];
        }
        if (isset($data['extent'])) {
            $properties['rico:physicalCharacteristics'] = $data['extent'];
        }
        if (isset($data['location'])) {
            $properties['rico:hasOrHadLocation'] = $data['location'];
        }
        if (isset($data['condition'])) {
            $properties['rico:conditionOfItem'] = $data['condition'];
        }

        if (empty($properties)) {
            return false;
        }

        return $this->triplestore->updateEntity($iri, $properties, $userId, 'Heritage object updated');
    }

    public function delete(string $iri, string $userId): bool
    {
        return $this->triplestore->deleteEntity($iri, $userId, 'Heritage object deleted');
    }

    public function getByType(string $heritageType, int $limit = 50): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
            SELECT ?s ?title ?description WHERE {
                ?s a rico:Record .
                ?s rico:hasRecordSetType ?type .
                ?s rico:title ?title .
                OPTIONAL { ?s rico:scopeAndContent ?description }
                FILTER(STR(?type) = ?typeFilter)
            }
            ORDER BY ?title
            LIMIT ' . max(1, min(200, $limit));

        $results = $this->triplestore->select($sparql, ['typeFilter' => $heritageType]);

        return array_map(fn (array $row) => [
            'iri'         => $row['s'] ?? '',
            'title'       => $row['title'] ?? '[Untitled]',
            'description' => $row['description'] ?? '',
        ], $results);
    }

    public function getStats(): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
            SELECT ?heritageType (COUNT(?s) AS ?count) WHERE {
                ?s a rico:Record .
                ?s rico:hasRecordSetType ?heritageType .
            }
            GROUP BY ?heritageType
            ORDER BY DESC(?count)
            LIMIT 100
        ';

        $results = $this->triplestore->select($sparql);

        $stats = [];
        foreach ($results as $row) {
            $type = $row['heritageType'] ?? 'unknown';
            $stats[$type] = (int) ($row['count'] ?? 0);
        }

        return $stats;
    }

    public function getCustodians(): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
            SELECT DISTINCT ?custodian ?name (COUNT(?record) AS ?holdings) WHERE {
                ?record a rico:Record .
                ?record rico:hasRecordSetType ?ht .
                ?record rico:heldBy ?custodian .
                ?custodian rico:name ?name .
            }
            GROUP BY ?custodian ?name
            ORDER BY DESC(?holdings)
            LIMIT 100
        ';

        $results = $this->triplestore->select($sparql);

        return array_map(fn (array $row) => [
            'iri'      => $row['custodian'] ?? '',
            'name'     => $row['name'] ?? '',
            'holdings' => (int) ($row['holdings'] ?? 0),
        ], $results);
    }

    public function getAnalytics(): array
    {
        $stats = $this->getStats();
        $custodians = $this->getCustodians();

        $totalRecords = array_sum($stats);

        return [
            'total_records'       => $totalRecords,
            'by_type'             => $stats,
            'custodians'          => $custodians,
            'custodian_count'     => count($custodians),
        ];
    }
}
