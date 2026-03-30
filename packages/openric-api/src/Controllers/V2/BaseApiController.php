<?php

declare(strict_types=1);

namespace OpenRic\Api\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Base API controller for OpenRiC v2 API.
 * Uses TriplestoreService for RiC-O data operations.
 */
abstract class BaseApiController extends Controller
{
    protected string $culture = 'en';

    public function __construct(
        protected readonly TriplestoreServiceInterface $triplestore
    ) {
        $this->culture = app()->getLocale();
    }

    /**
     * Standard v2 success response.
     */
    protected function success($data, int $status = 200, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => true,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ], $extra), $status);
    }

    /**
     * Standard v2 error response.
     */
    protected function error(string $error, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $error,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }

    /**
     * Paginated v2 response.
     */
    protected function paginated($data, int $total, int $page, int $limit, string $path): JsonResponse
    {
        $lastPage = max(1, (int) ceil($total / $limit));
        $baseUrl = url($path);

        $links = ['self' => "{$baseUrl}?page={$page}&limit={$limit}"];
        if ($page < $lastPage) {
            $links['next'] = "{$baseUrl}?page=" . ($page + 1) . "&limit={$limit}";
        }
        if ($page > 1) {
            $links['prev'] = "{$baseUrl}?page=" . ($page - 1) . "&limit={$limit}";
        }

        return response()->json([
            'success' => true,
            'data' => is_array($data) ? $data : $data->values(),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'last_page' => $lastPage,
            ],
            'links' => $links,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Parse pagination params from request.
     */
    protected function paginationParams(Request $request): array
    {
        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 10)));
        $offset = (int) $request->get('offset', ($page - 1) * $limit);
        $sort = $request->get('sort', 'modified');
        $sortDir = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'ASC' : 'DESC';

        return compact('page', 'limit', 'offset', 'sort', 'sortDir');
    }

    /**
     * Get authenticated user ID from request attributes.
     */
    protected function apiUserId(Request $request): ?int
    {
        return $request->attributes->get('api_user_id');
    }

    /**
     * Get API key ID from request attributes.
     */
    protected function apiKeyId(Request $request): ?int
    {
        return $request->attributes->get('api_key_id');
    }

    /**
     * Check if request has a specific scope.
     */
    protected function hasScope(Request $request, string $scope): bool
    {
        $scopes = $request->attributes->get('api_scopes', []);
        return in_array($scope, $scopes);
    }

    /**
     * Resolve entity by IRI.
     */
    protected function getEntity(string $iri): ?array
    {
        return $this->triplestore->getEntity($iri);
    }

    /**
     * Query entities by type.
     */
    protected function queryByType(string $type, int $limit = 100, int $offset = 0): array
    {
        $sparql = <<<SPARQL
SELECT ?entity ?type ?title ?modified
WHERE {
    ?entity a rico:{$type} .
    OPTIONAL { ?entity dcterms:title ?title }
    OPTIONAL { ?entity dcterms:modified ?modified }
}
LIMIT {$limit}
OFFSET {$offset}
SPARQL;

        return $this->triplestore->select($sparql);
    }

    /**
     * Count entities by type.
     */
    protected function countByType(string $type): int
    {
        $sparql = "SELECT (COUNT(?entity) AS ?count) WHERE { ?entity a rico:{$type} }";
        $results = $this->triplestore->select($sparql);
        
        return $results[0]['count']['value'] ?? $results[0]['count'] ?? 0;
    }
}
