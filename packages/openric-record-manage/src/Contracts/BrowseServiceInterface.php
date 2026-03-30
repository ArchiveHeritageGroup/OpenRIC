<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Contracts;

/**
 * Browse service contract for record browsing with facets, filters, and advanced search.
 * Adapted from Heratio InformationObjectBrowseService.
 */
interface BrowseServiceInterface
{
    /**
     * Browse records with pagination, sorting, full-text search, facets, and advanced criteria.
     *
     * @param  array  $params  Keys: page, limit, sort, sortDir, subquery, filters, advancedCriteria
     * @return array{hits: array, total: int, facets: array}
     */
    public function browse(array $params): array;
}
