<?php

declare(strict_types=1);

namespace OpenRiC\Graph\Contracts;

interface GraphServiceInterface
{
    /**
     * Get graph data centred on an entity — nodes + edges for Cytoscape.js.
     * @return array{nodes: array, edges: array}
     */
    public function getEntityGraph(string $iri, int $depth = 1, int $limit = 50): array;

    /**
     * Get agent network — who created what.
     * @return array{nodes: array, edges: array}
     */
    public function getAgentNetwork(int $limit = 100): array;

    /**
     * Get timeline data — entities with dates.
     * @return array<int, array{iri: string, title: string, type: string, date: string}>
     */
    public function getTimeline(array $filters = [], int $limit = 200): array;

    /**
     * Get overview graph — all RecordSets and their relationships.
     * @return array{nodes: array, edges: array}
     */
    public function getOverviewGraph(int $limit = 100): array;
}
