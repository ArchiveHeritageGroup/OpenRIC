<?php

declare(strict_types=1);

namespace OpenRic\Api\Controllers\V2;

use Illuminate\Http\JsonResponse;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class ApiRootController extends BaseApiController
{
    public function __construct(TriplestoreServiceInterface $triplestore)
    {
        parent::__construct($triplestore);
    }

    /**
     * API root information.
     */
    public function index(): JsonResponse
    {
        return $this->success([
            'name' => 'OpenRiC API',
            'version' => '2.0',
            'description' => 'REST API for OpenRiC archival platform with RiC-O data model',
            'endpoints' => [
                'v2' => [
                    'records' => '/api/v2/records',
                    'agents' => '/api/v2/agents',
                    'activities' => '/api/v2/activities',
                    'places' => '/api/v2/places',
                    'taxonomies' => '/api/v2/taxonomies',
                    'search' => '/api/v2/search',
                ],
            ],
            'documentation' => '/api/v2/docs',
        ]);
    }

    /**
     * Health check endpoint.
     */
    public function health(): JsonResponse
    {
        $triplestoreHealth = $this->triplestore->health();
        $totalTriples = $this->triplestore->countTriples();

        return $this->success([
            'status' => 'healthy',
            'services' => [
                'triplestore' => $triplestoreHealth,
            ],
            'stats' => [
                'total_triples' => $totalTriples,
            ],
        ]);
    }
}
