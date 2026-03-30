<?php

declare(strict_types=1);

namespace OpenRic\Api\Controllers\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends BaseApiController
{
    /**
     * Search across all entities.
     */
    public function search(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);
        $query = $request->get('q', '');
        $type = $request->get('type'); // records, agents, activities, places

        if (empty($query)) {
            return $this->error('INVALID_QUERY', 'Search query (q) is required', 400);
        }

        $escapedQuery = addslashes($query);
        
        // Build SPARQL based on type filter
        $typeFilter = '';
        if ($type) {
            $ricType = match ($type) {
                'records' => 'Record',
                'agents' => 'Agent',
                'activities' => 'Activity',
                'places' => 'Place',
                default => null,
            };
            if ($ricType) {
                $typeFilter = "?entity a rico:{$ricType} .";
            }
        }

        $sparql = <<<SPARQL
SELECT ?entity ?type ?title ?description
WHERE {
    ?entity a ?type .
    {$typeFilter}
    OPTIONAL { ?entity dcterms:title ?title }
    OPTIONAL { ?entity dcterms:description ?description }
    FILTER(
        CONTAINS(LCASE(?title), LCASE("{$escapedQuery}")) ||
        CONTAINS(LCASE(?description), LCASE("{$escapedQuery}"))
    )
}
LIMIT {$params['limit']}
OFFSET {$params['offset']}
SPARQL;

        $results = $this->triplestore->select($sparql);
        $total = count($results); // Simplified - would need subquery for accurate count

        return $this->paginated($results, $total, $params['page'], $params['limit'], '/api/v2/search');
    }

    /**
     * Autocomplete suggestions.
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = min(20, max(1, (int) $request->get('limit', 5)));

        if (strlen($query) < 2) {
            return $this->success([]);
        }

        $escapedQuery = addslashes($query);

        $sparql = <<<SPARQL
SELECT DISTINCT ?label ?type ?entity
WHERE {
    ?entity a ?type .
    ?entity dcterms:title ?label .
    FILTER(CONTAINS(LCASE(?label), LCASE("{$escapedQuery}")))
}
LIMIT {$limit}
SPARQL;

        $results = $this->triplestore->select($sparql);

        return $this->success($results);
    }
}
