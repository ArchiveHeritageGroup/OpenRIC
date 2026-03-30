<?php

declare(strict_types=1);

namespace OpenRic\Api\Controllers\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends BaseApiController
{
    /**
     * List audit entries.
     */
    public function index(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);
        $entityIri = $request->get('entity');

        if ($entityIri) {
            // Get provenance for specific entity
            return $this->getEntityProvenance(urldecode($entityIri), $params);
        }

        // List recent audit entries (from RDF-Star annotations)
        $sparql = <<<SPARQL
SELECT ?subject ?predicate ?object ?modifiedBy ?modifiedAt ?changeReason
WHERE {
    << ?subject ?predicate ?object >>
        openric:modifiedBy ?modifiedBy ;
        openric:modifiedAt ?modifiedAt ;
        openric:changeReason ?changeReason .
}
ORDER BY DESC(?modifiedAt)
LIMIT {$params['limit']}
OFFSET {$params['offset']}
SPARQL;

        $results = $this->triplestore->select($sparql);

        return $this->success($results);
    }

    /**
     * Get provenance for a specific entity.
     */
    public function show(string $iri): JsonResponse
    {
        $entityIri = urldecode($iri);

        $sparql = <<<SPARQL
SELECT ?modifiedBy ?modifiedAt ?changeReason ?predicate ?object
WHERE {
    << <{$entityIri}> ?predicate ?object >>
        openric:modifiedBy ?modifiedBy ;
        openric:modifiedAt ?modifiedAt ;
        openric:changeReason ?changeReason .
}
ORDER BY DESC(?modifiedAt)
SPARQL;

        $results = $this->triplestore->select($sparql);

        if (empty($results)) {
            return $this->error('NOT_FOUND', 'No audit history found', 404);
        }

        return $this->success([
            'entity' => $entityIri,
            'history' => $results,
        ]);
    }

    private function getEntityProvenance(string $entityIri, array $params): JsonResponse
    {
        $sparql = <<<SPARQL
SELECT ?modifiedBy ?modifiedAt ?changeReason ?predicate ?object
WHERE {
    << <{$entityIri}> ?predicate ?object >>
        openric:modifiedBy ?modifiedBy ;
        openric:modifiedAt ?modifiedAt ;
        openric:changeReason ?changeReason .
}
ORDER BY DESC(?modifiedAt)
LIMIT {$params['limit']}
OFFSET {$params['offset']}
SPARQL;

        $results = $this->triplestore->select($sparql);

        return $this->success([
            'entity' => $entityIri,
            'history' => $results,
        ]);
    }
}
