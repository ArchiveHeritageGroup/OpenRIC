<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Services;

use OpenRiC\RecordManage\Contracts\HierarchyServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class HierarchyService implements HierarchyServiceInterface
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function getRoots(int $limit = 100): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?iri ?title ?identifier ?childCount WHERE {
                ?iri a rico:RecordSet .
                ?iri rico:title ?title .
                FILTER NOT EXISTS { ?iri rico:isOrWasIncludedIn ?parent }
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL {
                    SELECT ?iri (COUNT(?child) AS ?childCount) WHERE {
                        ?child rico:isOrWasIncludedIn ?iri .
                    }
                    GROUP BY ?iri
                }
            }
            ORDER BY ?title
            LIMIT ?limit
            SPARQL;

        return $this->triplestore->select($sparql, ['limit' => (string) $limit]);
    }

    public function getChildren(string $parentIri, int $limit = 200): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?iri ?title ?identifier ?type ?childCount WHERE {
                ?iri rico:isOrWasIncludedIn ?parentIri .
                OPTIONAL { ?iri rico:title ?title }
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri a ?type . FILTER(STRSTARTS(STR(?type), STR(rico:))) }
                OPTIONAL {
                    SELECT ?iri (COUNT(?child) AS ?childCount) WHERE {
                        ?child rico:isOrWasIncludedIn ?iri .
                    }
                    GROUP BY ?iri
                }
            }
            ORDER BY ?title
            LIMIT ?limit
            SPARQL;

        return $this->triplestore->select($sparql, [
            'parentIri' => $parentIri,
            'limit' => (string) $limit,
        ]);
    }

    public function getAncestors(string $iri): array
    {
        $ancestors = [];
        $currentIri = $iri;
        $visited = [];
        $maxDepth = 20;

        while ($maxDepth > 0) {
            $sparql = <<<'SPARQL'
                SELECT ?parent ?parentTitle WHERE {
                    ?currentIri rico:isOrWasIncludedIn ?parent .
                    ?parent rico:title ?parentTitle .
                }
                LIMIT 1
                SPARQL;

            $result = $this->triplestore->select($sparql, ['currentIri' => $currentIri]);

            if (empty($result)) {
                break;
            }

            $parentIri = $result[0]['parent']['value'] ?? '';

            if ($parentIri === '' || in_array($parentIri, $visited, true)) {
                break;
            }

            $visited[] = $parentIri;
            array_unshift($ancestors, [
                'iri' => $parentIri,
                'title' => $result[0]['parentTitle']['value'] ?? 'Untitled',
            ]);

            $currentIri = $parentIri;
            $maxDepth--;
        }

        return $ancestors;
    }

    public function getTree(string $rootIri, int $maxDepth = 3): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?iri ?title WHERE {
                ?rootIri rico:title ?title .
                BIND(?rootIri AS ?iri)
            }
            LIMIT 1
            SPARQL;

        $rootResult = $this->triplestore->select($sparql, ['rootIri' => $rootIri]);

        if (empty($rootResult)) {
            return [];
        }

        $root = [
            'iri' => $rootIri,
            'title' => $rootResult[0]['title']['value'] ?? 'Untitled',
            'children' => [],
        ];

        if ($maxDepth > 0) {
            $root['children'] = $this->buildTreeLevel($rootIri, $maxDepth - 1);
        }

        return $root;
    }

    private function buildTreeLevel(string $parentIri, int $remainingDepth): array
    {
        $children = $this->getChildren($parentIri, 100);
        $result = [];

        foreach ($children as $child) {
            $childIri = $child['iri']['value'] ?? '';
            $node = [
                'iri' => $childIri,
                'title' => $child['title']['value'] ?? 'Untitled',
                'type' => $child['type']['value'] ?? '',
                'childCount' => (int) ($child['childCount']['value'] ?? 0),
                'children' => [],
            ];

            if ($remainingDepth > 0 && $node['childCount'] > 0) {
                $node['children'] = $this->buildTreeLevel($childIri, $remainingDepth - 1);
            }

            $result[] = $node;
        }

        return $result;
    }
}
