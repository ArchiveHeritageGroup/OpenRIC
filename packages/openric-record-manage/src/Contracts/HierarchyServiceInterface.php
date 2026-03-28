<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Contracts;

interface HierarchyServiceInterface
{
    /**
     * Get top-level record sets (fonds with no parent).
     */
    public function getRoots(int $limit = 100): array;

    /**
     * Get the direct children of a record set.
     */
    public function getChildren(string $parentIri, int $limit = 200): array;

    /**
     * Get the full ancestry path from an entity to the root.
     */
    public function getAncestors(string $iri): array;

    /**
     * Get a recursive tree structure (limited depth) for a given root.
     */
    public function getTree(string $rootIri, int $maxDepth = 3): array;
}
