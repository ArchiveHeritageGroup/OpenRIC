<?php

declare(strict_types=1);

namespace OpenRiC\Search\Contracts;

interface FacetedBrowseServiceInterface
{
    /**
     * Browse entities with facet filtering and sorting.
     * @return array{items: array, total: int, facets: array, page: int, limit: int}
     */
    public function browse(array $params = []): array;

    /**
     * Get facet counts for a given set of filters.
     * @return array{entity_types: array, levels: array, creators: array, date_ranges: array}
     */
    public function getFacets(array $filters = []): array;
}
