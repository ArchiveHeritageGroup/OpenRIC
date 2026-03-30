<?php

declare(strict_types=1);

namespace OpenRic\Api\Controllers\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxonomyController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);

        $sparql = <<<SPARQL
SELECT ?taxonomy ?name ?description
WHERE {
    ?taxonomy a rico:Taxonomy .
    OPTIONAL { ?taxonomy dcterms:title ?name }
    OPTIONAL { ?taxonomy dcterms:description ?description }
}
ORDER BY ?name
LIMIT {$params['limit']}
OFFSET {$params['offset']}
SPARQL;

        $results = $this->triplestore->select($sparql);

        return $this->success($results);
    }

    public function show(string $id): JsonResponse
    {
        $iri = urldecode($id);
        
        $sparql = "SELECT ?predicate ?object WHERE { <{$iri}> ?predicate ?object } LIMIT 500";
        $results = $this->triplestore->select($sparql);

        if (empty($results)) {
            return $this->error('NOT_FOUND', 'Taxonomy not found', 404);
        }

        return $this->success(['iri' => $iri, 'properties' => $results]);
    }

    public function terms(Request $request, string $id): JsonResponse
    {
        $params = $this->paginationParams($request);
        $taxonomyIri = urldecode($id);

        $sparql = <<<SPARQL
SELECT ?term ?name ?code
WHERE {
    ?term a rico:Term .
    ?term rico:isOrWasRelatedTo <{$taxonomyIri}> .
    OPTIONAL { ?term dcterms:title ?name }
    OPTIONAL { ?term rico:hasIdentifier ?code }
}
ORDER BY ?name
LIMIT {$params['limit']}
OFFSET {$params['offset']}
SPARQL;

        $results = $this->triplestore->select($sparql);

        return $this->success($results);
    }

    public function store(Request $request): JsonResponse
    {
        $iri = $this->triplestore->generateIri('Taxonomy');
        $userId = $this->apiUserId($request) ?? 'system';

        $triples = [
            ['subject' => $iri, 'predicate' => 'a', 'object' => 'rico:Taxonomy'],
        ];

        if ($title = $request->get('title')) {
            $triples[] = ['subject' => $iri, 'predicate' => 'dcterms:title', 'object' => $title];
        }
        if ($description = $request->get('description')) {
            $triples[] = ['subject' => $iri, 'predicate' => 'dcterms:description', 'object' => $description];
        }

        $this->triplestore->insert($triples, $userId, 'Created taxonomy via API');

        return $this->success(['iri' => $iri], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $iri = urldecode($id);
        $userId = $this->apiUserId($request) ?? 'system';
        $newTriples = [];

        if ($title = $request->get('title')) {
            $newTriples['dcterms:title'] = $title;
        }
        if ($description = $request->get('description')) {
            $newTriples['dcterms:description'] = $description;
        }

        if (!empty($newTriples)) {
            $this->triplestore->updateEntity($iri, $newTriples, $userId, 'Updated taxonomy via API');
        }

        return $this->success(['iri' => $iri]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $iri = urldecode($id);
        $userId = $this->apiUserId($request) ?? 'system';

        $this->triplestore->deleteEntity($iri, $userId, 'Deleted taxonomy via API');

        return $this->success(['deleted' => true]);
    }

    public function addTerm(Request $request, string $id): JsonResponse
    {
        $taxonomyIri = urldecode($id);
        $termIri = $this->triplestore->generateIri('Term');
        $userId = $this->apiUserId($request) ?? 'system';

        $triples = [
            ['subject' => $termIri, 'predicate' => 'a', 'object' => 'rico:Term'],
            ['subject' => $termIri, 'predicate' => 'rico:isOrWasRelatedTo', 'object' => $taxonomyIri],
        ];

        if ($name = $request->get('name')) {
            $triples[] = ['subject' => $termIri, 'predicate' => 'dcterms:title', 'object' => $name];
        }
        if ($code = $request->get('code')) {
            $triples[] = ['subject' => $termIri, 'predicate' => 'rico:hasIdentifier', 'object' => $code];
        }

        $this->triplestore->insert($triples, $userId, 'Added term to taxonomy via API');

        return $this->success(['iri' => $termIri], 201);
    }

    public function removeTerm(string $taxonomyId, string $termId): JsonResponse
    {
        $termIri = urldecode($termId);
        $userId = 'system';

        $this->triplestore->deleteEntity($termIri, $userId, 'Removed term from taxonomy via API');

        return $this->success(['deleted' => true]);
    }
}
