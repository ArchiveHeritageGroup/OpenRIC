<?php

declare(strict_types=1);

namespace OpenRic\Api\Controllers\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorityController extends BaseApiController
{
    // Records (Record, RecordSet, RecordPart)
    
    public function listRecords(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);
        $type = $request->get('type', 'Record'); // Record, RecordSet, RecordPart

        $sparql = <<<SPARQL
SELECT ?entity ?title ?created ?modified
WHERE {
    ?entity a rico:{$type} .
    OPTIONAL { ?entity dcterms:title ?title }
    OPTIONAL { ?entity dcterms:created ?created }
    OPTIONAL { ?entity dcterms:modified ?modified }
}
ORDER BY DESC(?modified)
LIMIT {$params['limit']}
OFFSET {$params['offset']}
SPARQL;

        $results = $this->triplestore->select($sparql);
        $total = $this->countByType($type);

        return $this->paginated($results, $total, $params['page'], $params['limit'], '/api/v2/records');
    }

    public function getRecord(string $iri): JsonResponse
    {
        $entity = $this->getEntity(urldecode($iri));
        
        if (!$entity) {
            return $this->error('NOT_FOUND', 'Record not found', 404);
        }

        return $this->success($entity);
    }

    public function createRecord(Request $request): JsonResponse
    {
        $iri = $this->triplestore->generateIri('Record');
        $userId = $this->apiUserId($request) ?? 'system';

        $triples = [
            ['subject' => $iri, 'predicate' => 'a', 'object' => 'rico:Record'],
        ];

        // Add properties from request
        if ($title = $request->get('title')) {
            $triples[] = ['subject' => $iri, 'predicate' => 'dcterms:title', 'object' => $title];
        }
        if ($description = $request->get('description')) {
            $triples[] = ['subject' => $iri, 'predicate' => 'dcterms:description', 'object' => $description];
        }

        $this->triplestore->insert($triples, $userId, 'Created record via API');

        return $this->success(['iri' => $iri], 201);
    }

    public function updateRecord(Request $request, string $iri): JsonResponse
    {
        $entity = $this->getEntity(urldecode($iri));
        
        if (!$entity) {
            return $this->error('NOT_FOUND', 'Record not found', 404);
        }

        $userId = $this->apiUserId($request) ?? 'system';
        $newTriples = [];

        if ($title = $request->get('title')) {
            $newTriples['dcterms:title'] = $title;
        }
        if ($description = $request->get('description')) {
            $newTriples['dcterms:description'] = $description;
        }

        if (!empty($newTriples)) {
            $this->triplestore->updateEntity(urldecode($iri), $newTriples, $userId, 'Updated record via API');
        }

        return $this->success(['iri' => $iri]);
    }

    public function deleteRecord(Request $request, string $iri): JsonResponse
    {
        $entity = $this->getEntity(urldecode($iri));
        
        if (!$entity) {
            return $this->error('NOT_FOUND', 'Record not found', 404);
        }

        $userId = $this->apiUserId($request) ?? 'system';
        $this->triplestore->deleteEntity(urldecode($iri), $userId, 'Deleted record via API');

        return $this->success(['deleted' => true]);
    }

    // Agents (Person, Family, CorporateBody)
    
    public function listAgents(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);

        $sparql = <<<SPARQL
SELECT ?entity ?name ?type ?created
WHERE {
    ?entity a rico:Agent .
    OPTIONAL { ?entity rico:hasOrHadName ?name }
    OPTIONAL { ?entity a ?type }
    OPTIONAL { ?entity dcterms:created ?created }
}
ORDER BY ?name
LIMIT {$params['limit']}
OFFSET {$params['offset']}
SPARQL;

        $results = $this->triplestore->select($sparql);
        $total = $this->countByType('Agent');

        return $this->paginated($results, $total, $params['page'], $params['limit'], '/api/v2/agents');
    }

    public function getAgent(string $iri): JsonResponse
    {
        $entity = $this->getEntity(urldecode($iri));
        
        if (!$entity) {
            return $this->error('NOT_FOUND', 'Agent not found', 404);
        }

        return $this->success($entity);
    }

    public function createAgent(Request $request): JsonResponse
    {
        $type = $request->get('type', 'Agent'); // Person, Family, CorporateBody
        $iri = $this->triplestore->generateIri($type);
        $userId = $this->apiUserId($request) ?? 'system';

        $triples = [
            ['subject' => $iri, 'predicate' => 'a', 'object' => 'rico:' . $type],
        ];

        if ($name = $request->get('name')) {
            $triples[] = ['subject' => $iri, 'predicate' => 'rico:hasOrHadName', 'object' => $name];
        }

        $this->triplestore->insert($triples, $userId, 'Created agent via API');

        return $this->success(['iri' => $iri], 201);
    }

    public function updateAgent(Request $request, string $iri): JsonResponse
    {
        $entity = $this->getEntity(urldecode($iri));
        
        if (!$entity) {
            return $this->error('NOT_FOUND', 'Agent not found', 404);
        }

        $userId = $this->apiUserId($request) ?? 'system';
        $newTriples = [];

        if ($name = $request->get('name')) {
            $newTriples['rico:hasOrHadName'] = $name;
        }

        if (!empty($newTriples)) {
            $this->triplestore->updateEntity(urldecode($iri), $newTriples, $userId, 'Updated agent via API');
        }

        return $this->success(['iri' => $iri]);
    }

    public function deleteAgent(Request $request, string $iri): JsonResponse
    {
        $entity = $this->getEntity(urldecode($iri));
        
        if (!$entity) {
            return $this->error('NOT_FOUND', 'Agent not found', 404);
        }

        $userId = $this->apiUserId($request) ?? 'system';
        $this->triplestore->deleteEntity(urldecode($iri), $userId, 'Deleted agent via API');

        return $this->success(['deleted' => true]);
    }

    // Activities
    
    public function listActivities(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);

        $sparql = <<<SPARQL
SELECT ?entity ?title ?type ?created
WHERE {
    ?entity a rico:Activity .
    OPTIONAL { ?entity dcterms:title ?title }
    OPTIONAL { ?entity rico:hasOrHadType ?type }
    OPTIONAL { ?entity dcterms:created ?created }
}
ORDER BY DESC(?created)
LIMIT {$params['limit']}
OFFSET {$params['offset']}
SPARQL;

        $results = $this->triplestore->select($sparql);
        $total = $this->countByType('Activity');

        return $this->paginated($results, $total, $params['page'], $params['limit'], '/api/v2/activities');
    }

    public function getActivity(string $iri): JsonResponse
    {
        $entity = $this->getEntity(urldecode($iri));
        
        if (!$entity) {
            return $this->error('NOT_FOUND', 'Activity not found', 404);
        }

        return $this->success($entity);
    }

    public function createActivity(Request $request): JsonResponse
    {
        $iri = $this->triplestore->generateIri('Activity');
        $userId = $this->apiUserId($request) ?? 'system';

        $triples = [
            ['subject' => $iri, 'predicate' => 'a', 'object' => 'rico:Activity'],
        ];

        if ($title = $request->get('title')) {
            $triples[] = ['subject' => $iri, 'predicate' => 'dcterms:title', 'object' => $title];
        }
        if ($type = $request->get('type')) {
            $triples[] = ['subject' => $iri, 'predicate' => 'rico:hasOrHadType', 'object' => $type . '@en'];
        }

        $this->triplestore->insert($triples, $userId, 'Created activity via API');

        return $this->success(['iri' => $iri], 201);
    }

    public function updateActivity(Request $request, string $iri): JsonResponse
    {
        $entity = $this->getEntity(urldecode($iri));
        
        if (!$entity) {
            return $this->error('NOT_FOUND', 'Activity not found', 404);
        }

        $userId = $this->apiUserId($request) ?? 'system';
        $newTriples = [];

        if ($title = $request->get('title')) {
            $newTriples['dcterms:title'] = $title;
        }

        if (!empty($newTriples)) {
            $this->triplestore->updateEntity(urldecode($iri), $newTriples, $userId, 'Updated activity via API');
        }

        return $this->success(['iri' => $iri]);
    }

    public function deleteActivity(Request $request, string $iri): JsonResponse
    {
        $entity = $this->getEntity(urldecode($iri));
        
        if (!$entity) {
            return $this->error('NOT_FOUND', 'Activity not found', 404);
        }

        $userId = $this->apiUserId($request) ?? 'system';
        $this->triplestore->deleteEntity(urldecode($iri), $userId, 'Deleted activity via API');

        return $this->success(['deleted' => true]);
    }

    // Places
    
    public function listPlaces(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);

        $sparql = <<<SPARQL
SELECT ?entity ?name ?type ?created
WHERE {
    ?entity a rico:Place .
    OPTIONAL { ?entity rico:hasOrHadName ?name }
    OPTIONAL { ?entity rico:hasOrHadType ?type }
    OPTIONAL { ?entity dcterms:created ?created }
}
ORDER BY ?name
LIMIT {$params['limit']}
OFFSET {$params['offset']}
SPARQL;

        $results = $this->triplestore->select($sparql);
        $total = $this->countByType('Place');

        return $this->paginated($results, $total, $params['page'], $params['limit'], '/api/v2/places');
    }

    public function getPlace(string $iri): JsonResponse
    {
        $entity = $this->getEntity(urldecode($iri));
        
        if (!$entity) {
            return $this->error('NOT_FOUND', 'Place not found', 404);
        }

        return $this->success($entity);
    }

    // Relationships
    
    public function createRelationship(Request $request): JsonResponse
    {
        $subject = $request->get('subject');
        $predicate = $request->get('predicate');
        $object = $request->get('object');

        if (!$subject || !$predicate || !$object) {
            return $this->error('INVALID_PARAMS', 'subject, predicate, and object are required', 400);
        }

        $userId = $this->apiUserId($request) ?? 'system';
        $this->triplestore->createRelationship($subject, $predicate, $object, $userId, 'Created relationship via API');

        return $this->success(['created' => true], 201);
    }

    public function deleteRelationship(Request $request): JsonResponse
    {
        $subject = $request->get('subject');
        $predicate = $request->get('predicate');
        $object = $request->get('object');

        if (!$subject || !$predicate || !$object) {
            return $this->error('INVALID_PARAMS', 'subject, predicate, and object are required', 400);
        }

        $userId = $this->apiUserId($request) ?? 'system';
        $this->triplestore->deleteRelationship($subject, $predicate, $object, $userId, 'Deleted relationship via API');

        return $this->success(['deleted' => true]);
    }
}
