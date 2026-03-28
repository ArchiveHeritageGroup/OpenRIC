<?php

declare(strict_types=1);

namespace OpenRiC\Core\Contracts;

/**
 * Interface for paginated browse operations with filtering.
 *
 * Implementations provide entity-specific browse logic while
 * conforming to a consistent return structure.
 */
interface BrowseServiceInterface
{
    /**
     * Execute a paginated browse query with optional filters.
     *
     * @param  array<string, mixed>  $filters  Associative array of filter criteria.
     *                                          Common keys include:
     *                                          - 'search'   (string)  Free-text search term.
     *                                          - 'sort'     (string)  Sort field name.
     *                                          - 'sort_dir' (string)  'asc' or 'desc'.
     *                                          Implementations may accept additional keys.
     * @param  int  $limit   Maximum number of items to return (clamped 1-100).
     * @param  int  $offset  Zero-based offset for pagination.
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array;
}
