<?php

declare(strict_types=1);

namespace OpenRiC\Graph\Services;

use OpenRiC\Graph\Contracts\GraphServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class GraphService implements GraphServiceInterface
{
    private const TYPE_COLORS = [
        'rico:RecordSet' => '#0d6efd',
        'rico:Record' => '#0dcaf0',
        'rico:RecordPart' => '#6610f2',
        'rico:Person' => '#198754',
        'rico:CorporateBody' => '#20c997',
        'rico:Family' => '#6ea8fe',
        'rico:Activity' => '#ffc107',
        'rico:Place' => '#fd7e14',
        'rico:Date' => '#adb5bd',
        'rico:DateRange' => '#adb5bd',
        'rico:Mandate' => '#dc3545',
        'rico:Function' => '#d63384',
        'rico:Instantiation' => '#6f42c1',
    ];

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function getEntityGraph(string $iri, int $depth = 1, int $limit = 50): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?subject ?predicate ?object ?subjectTitle ?objectTitle ?subjectType ?objectType WHERE {
                {
                    ?subject ?predicate ?object .
                    FILTER(?subject = ?entityIri)
                    FILTER(isURI(?object))
                    FILTER(?predicate != rdf:type)
                }
                UNION
                {
                    ?subject ?predicate ?object .
                    FILTER(?object = ?entityIri)
                    FILTER(isURI(?subject))
                    FILTER(?predicate != rdf:type)
                }
                OPTIONAL { ?subject rico:title ?subjectTitle }
                OPTIONAL { ?object rico:title ?objectTitle }
                OPTIONAL { ?subject a ?subjectType . FILTER(STRSTARTS(STR(?subjectType), STR(rico:))) }
                OPTIONAL { ?object a ?objectType . FILTER(STRSTARTS(STR(?objectType), STR(rico:))) }
            }
            LIMIT ?limit
            SPARQL;

        $results = $this->triplestore->select($sparql, [
            'entityIri' => $iri,
            'limit' => (string) $limit,
        ]);

        return $this->buildCytoscapeData($results, $iri);
    }

    public function getAgentNetwork(int $limit = 100): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?record ?recordTitle ?agent ?agentName ?agentType WHERE {
                ?record rico:hasOrHadCreator ?agent .
                ?record rico:title ?recordTitle .
                OPTIONAL { ?agent rico:title ?agentName }
                OPTIONAL { ?agent rico:hasAgentName ?nameNode . ?nameNode rico:textualValue ?agentName }
                OPTIONAL { ?agent a ?agentType . FILTER(STRSTARTS(STR(?agentType), STR(rico:))) }
            }
            LIMIT ?limit
            SPARQL;

        $results = $this->triplestore->select($sparql, ['limit' => (string) $limit]);

        $nodes = [];
        $edges = [];

        foreach ($results as $row) {
            $recordIri = $row['record']['value'] ?? '';
            $agentIri = $row['agent']['value'] ?? '';

            if ($recordIri !== '' && ! isset($nodes[$recordIri])) {
                $nodes[$recordIri] = [
                    'data' => [
                        'id' => $recordIri,
                        'label' => $row['recordTitle']['value'] ?? 'Untitled',
                        'type' => 'Record',
                        'color' => self::TYPE_COLORS['rico:Record'] ?? '#666',
                    ],
                ];
            }

            if ($agentIri !== '' && ! isset($nodes[$agentIri])) {
                $typeStr = $row['agentType']['value'] ?? '';
                $shortType = str_replace('https://www.ica.org/standards/RiC/ontology#', 'rico:', $typeStr);
                $nodes[$agentIri] = [
                    'data' => [
                        'id' => $agentIri,
                        'label' => $row['agentName']['value'] ?? 'Unknown Agent',
                        'type' => str_replace('rico:', '', $shortType) ?: 'Agent',
                        'color' => self::TYPE_COLORS[$shortType] ?? '#198754',
                    ],
                ];
            }

            if ($recordIri !== '' && $agentIri !== '') {
                $edges[] = [
                    'data' => [
                        'source' => $recordIri,
                        'target' => $agentIri,
                        'label' => 'hasOrHadCreator',
                    ],
                ];
            }
        }

        return ['nodes' => array_values($nodes), 'edges' => $edges];
    }

    public function getTimeline(array $filters = [], int $limit = 200): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?iri ?title ?type ?date WHERE {
                ?iri rico:isAssociatedWithDate ?dateNode .
                ?dateNode rico:expressedDate ?date .
                ?iri rico:title ?title .
                ?iri a ?type .
                FILTER(STRSTARTS(STR(?type), STR(rico:)))
            }
            ORDER BY ?date
            LIMIT ?limit
            SPARQL;

        $results = $this->triplestore->select($sparql, ['limit' => (string) $limit]);

        return array_map(fn ($row) => [
            'iri' => $row['iri']['value'] ?? '',
            'title' => $row['title']['value'] ?? 'Untitled',
            'type' => str_replace('https://www.ica.org/standards/RiC/ontology#', '', $row['type']['value'] ?? ''),
            'date' => $row['date']['value'] ?? '',
        ], $results);
    }

    public function getOverviewGraph(int $limit = 100): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?s ?sTitle ?sType ?related ?relTitle ?relType ?pred WHERE {
                ?s a rico:RecordSet .
                ?s rico:title ?sTitle .
                OPTIONAL { ?s a ?sType . FILTER(STRSTARTS(STR(?sType), STR(rico:))) }
                OPTIONAL {
                    ?s ?pred ?related .
                    FILTER(isURI(?related) && ?pred != rdf:type)
                    OPTIONAL { ?related rico:title ?relTitle }
                    OPTIONAL { ?related a ?relType . FILTER(STRSTARTS(STR(?relType), STR(rico:))) }
                }
            }
            LIMIT ?limit
            SPARQL;

        $results = $this->triplestore->select($sparql, ['limit' => (string) $limit]);

        return $this->buildCytoscapeData($results);
    }

    private function buildCytoscapeData(array $results, ?string $centreIri = null): array
    {
        $nodes = [];
        $edges = [];

        foreach ($results as $row) {
            $subjectIri = $row['subject']['value'] ?? $row['s']['value'] ?? '';
            $objectIri = $row['object']['value'] ?? $row['related']['value'] ?? '';
            $predicate = $row['predicate']['value'] ?? $row['pred']['value'] ?? '';

            if ($subjectIri !== '' && ! isset($nodes[$subjectIri])) {
                $typeStr = $row['subjectType']['value'] ?? $row['sType']['value'] ?? '';
                $shortType = str_replace('https://www.ica.org/standards/RiC/ontology#', 'rico:', $typeStr);
                $nodes[$subjectIri] = [
                    'data' => [
                        'id' => $subjectIri,
                        'label' => $row['subjectTitle']['value'] ?? $row['sTitle']['value'] ?? $this->shortenIri($subjectIri),
                        'type' => str_replace('rico:', '', $shortType) ?: 'Entity',
                        'color' => self::TYPE_COLORS[$shortType] ?? '#666',
                        'isCentre' => $subjectIri === $centreIri,
                    ],
                ];
            }

            if ($objectIri !== '' && ! isset($nodes[$objectIri])) {
                $typeStr = $row['objectType']['value'] ?? $row['relType']['value'] ?? '';
                $shortType = str_replace('https://www.ica.org/standards/RiC/ontology#', 'rico:', $typeStr);
                $nodes[$objectIri] = [
                    'data' => [
                        'id' => $objectIri,
                        'label' => $row['objectTitle']['value'] ?? $row['relTitle']['value'] ?? $this->shortenIri($objectIri),
                        'type' => str_replace('rico:', '', $shortType) ?: 'Entity',
                        'color' => self::TYPE_COLORS[$shortType] ?? '#666',
                        'isCentre' => $objectIri === $centreIri,
                    ],
                ];
            }

            if ($subjectIri !== '' && $objectIri !== '' && $predicate !== '') {
                $shortPred = str_replace('https://www.ica.org/standards/RiC/ontology#', '', $predicate);
                $edges[] = [
                    'data' => [
                        'source' => $subjectIri,
                        'target' => $objectIri,
                        'label' => $shortPred,
                    ],
                ];
            }
        }

        return ['nodes' => array_values($nodes), 'edges' => $edges];
    }

    private function shortenIri(string $iri): string
    {
        $parts = explode('/', $iri);

        return end($parts) ?: $iri;
    }
}
