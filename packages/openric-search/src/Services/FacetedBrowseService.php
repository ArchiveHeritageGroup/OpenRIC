<?php

declare(strict_types=1);

namespace OpenRiC\Search\Services;

use OpenRiC\Search\Contracts\FacetedBrowseServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * SPARQL-driven faceted browse.
 * Adapted from Heratio BrowseService (143 lines) but uses triplestore instead of MySQL.
 */
class FacetedBrowseService implements FacetedBrowseServiceInterface
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function browse(array $params = []): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;
        $sort = $params['sort'] ?? 'title';
        $sortDir = strtoupper($params['sortDir'] ?? 'ASC');
        if (! in_array($sortDir, ['ASC', 'DESC'], true)) {
            $sortDir = 'ASC';
        }
        $subquery = trim($params['subquery'] ?? '');

        // Build SPARQL with filters
        $filterClauses = [];
        $filterParams = ['limit' => (string) $limit, 'offset' => (string) $offset];

        if ($subquery !== '') {
            $filterClauses[] = 'FILTER(CONTAINS(LCASE(?title), LCASE(?subquery)))';
            $filterParams['subquery'] = $subquery;
        }

        if (! empty($params['entity_type'])) {
            $type = $params['entity_type'];
            $filterClauses[] = "?iri a rico:{$type} .";
        }

        if (! empty($params['creator'])) {
            $filterClauses[] = '?iri rico:hasOrHadCreator ?filterCreator .';
            $filterParams['filterCreator'] = $params['creator'];
        }

        if (! empty($params['date_from'])) {
            $filterClauses[] = '?iri rico:isAssociatedWithDate ?dateNode . ?dateNode rico:hasBeginningDate ?dateVal . FILTER(?dateVal >= ?dateFrom)';
            $filterParams['dateFrom'] = $params['date_from'];
        }

        $filterBlock = implode("\n", $filterClauses);
        $orderBy = $sort === 'date' ? 'DESC(?dateVal)' : ($sortDir === 'DESC' ? 'DESC(?title)' : '?title');

        $sparql = <<<SPARQL
            SELECT ?iri ?title ?identifier ?type WHERE {
                ?iri rico:title ?title .
                ?iri a ?type .
                FILTER(STRSTARTS(STR(?type), STR(rico:)))
                OPTIONAL { ?iri rico:identifier ?identifier }
                {$filterBlock}
            }
            ORDER BY {$orderBy}
            LIMIT {$limit} OFFSET {$offset}
            SPARQL;

        $items = $this->triplestore->select($sparql, $filterParams);

        // Count query
        $countSparql = <<<SPARQL
            SELECT (COUNT(DISTINCT ?iri) AS ?count) WHERE {
                ?iri rico:title ?title .
                ?iri a ?type .
                FILTER(STRSTARTS(STR(?type), STR(rico:)))
                {$filterBlock}
            }
            SPARQL;

        $countParams = array_diff_key($filterParams, ['limit' => '', 'offset' => '']);
        $countResult = $this->triplestore->select($countSparql, $countParams);
        $total = (int) ($countResult[0]['count']['value'] ?? 0);

        $facets = $this->getFacets($params);

        return [
            'items' => $items,
            'total' => $total,
            'facets' => $facets,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function getFacets(array $filters = []): array
    {
        // Entity type facet
        $typeSparql = <<<'SPARQL'
            SELECT ?type (COUNT(?iri) AS ?count) WHERE {
                ?iri a ?type .
                FILTER(STRSTARTS(STR(?type), STR(rico:)))
            }
            GROUP BY ?type
            ORDER BY DESC(?count)
            LIMIT 20
            SPARQL;

        $typeResults = $this->triplestore->select($typeSparql);
        $entityTypes = array_map(fn ($r) => [
            'value' => str_replace('https://www.ica.org/standards/RiC/ontology#', '', $r['type']['value'] ?? ''),
            'count' => (int) ($r['count']['value'] ?? 0),
        ], $typeResults);

        // Creator facet
        $creatorSparql = <<<'SPARQL'
            SELECT ?creator ?creatorName (COUNT(?record) AS ?count) WHERE {
                ?record rico:hasOrHadCreator ?creator .
                OPTIONAL { ?creator rico:title ?creatorName }
                OPTIONAL { ?creator rico:hasAgentName ?nameNode . ?nameNode rico:textualValue ?creatorName }
            }
            GROUP BY ?creator ?creatorName
            ORDER BY DESC(?count)
            LIMIT 20
            SPARQL;

        $creatorResults = $this->triplestore->select($creatorSparql);
        $creators = array_map(fn ($r) => [
            'iri' => $r['creator']['value'] ?? '',
            'name' => $r['creatorName']['value'] ?? 'Unknown',
            'count' => (int) ($r['count']['value'] ?? 0),
        ], $creatorResults);

        return [
            'entity_types' => $entityTypes,
            'creators' => $creators,
        ];
    }
}
