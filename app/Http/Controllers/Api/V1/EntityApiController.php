<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Generic Entity API Controller — handles all RiC-O entity types.
 * Maps URL entity types to RDF types and provides unified CRUD.
 */
class EntityApiController extends Controller
{
    private const JSONLD_CONTEXT = 'https://www.ica.org/standards/RiC/ontology';

    private const TYPE_MAP = [
        'activities' => 'rico:Activity',
        'places' => 'rico:Place',
        'dates' => 'rico:Date',
        'mandates' => 'rico:Mandate',
        'functions' => 'rico:Function',
        'instantiations' => 'rico:Instantiation',
        'record-sets' => 'rico:RecordSet',
        'record-parts' => 'rico:RecordPart',
    ];

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function index(Request $request, string $entityType): JsonResponse
    {
        $rdfType = self::TYPE_MAP[$entityType] ?? null;
        if (!$rdfType) {
            return response()->json(['error' => 'Unknown entity type'], 404);
        }

        $limit = min(100, max(1, (int) $request->input('limit', 25)));
        $offset = max(0, (int) $request->input('offset', 0));

        $sparql = "SELECT ?iri ?title ?identifier WHERE { ?iri a {$rdfType} . OPTIONAL { ?iri rico:title ?title } OPTIONAL { ?iri rico:identifier ?identifier } } ORDER BY ?title LIMIT {$limit} OFFSET {$offset}";
        $countSparql = "SELECT (COUNT(?iri) AS ?count) WHERE { ?iri a {$rdfType} . }";

        $items = $this->triplestore->select($sparql);
        $countResult = $this->triplestore->select($countSparql);
        $total = (int) ($countResult[0]['count']['value'] ?? 0);

        return response()->json([
            '@context' => self::JSONLD_CONTEXT,
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => $total,
            'hydra:member' => array_map(fn ($item) => [
                '@id' => $item['iri']['value'] ?? null,
                '@type' => $rdfType,
                'rico:title' => $item['title']['value'] ?? null,
                'rico:identifier' => $item['identifier']['value'] ?? null,
            ], $items),
        ], 200, ['Content-Type' => 'application/ld+json']);
    }

    public function show(string $entityType, string $iri): JsonResponse
    {
        $rdfType = self::TYPE_MAP[$entityType] ?? null;
        if (!$rdfType) {
            return response()->json(['error' => 'Unknown entity type'], 404);
        }

        $entity = $this->triplestore->getEntity(urldecode($iri));
        if ($entity === null) {
            return response()->json(['error' => 'Entity not found'], 404, ['Content-Type' => 'application/ld+json']);
        }

        $result = ['@context' => self::JSONLD_CONTEXT, '@type' => $rdfType, '@id' => $entity['iri'] ?? urldecode($iri)];
        foreach ($entity['properties'] ?? [] as $prop => $val) {
            $result[$prop] = is_array($val) ? ($val['value'] ?? $val) : $val;
        }

        return response()->json($result, 200, ['Content-Type' => 'application/ld+json']);
    }

    public function store(Request $request, string $entityType): JsonResponse
    {
        $rdfType = self::TYPE_MAP[$entityType] ?? null;
        if (!$rdfType) {
            return response()->json(['error' => 'Unknown entity type'], 404);
        }

        $data = $request->validate(['title' => 'required|string|max:1000', 'identifier' => 'nullable|string|max:255']);
        $user = $request->user();

        $properties = ['rico:title' => ['value' => $data['title'], 'datatype' => 'xsd:string']];
        if (!empty($data['identifier'])) {
            $properties['rico:identifier'] = ['value' => $data['identifier'], 'datatype' => 'xsd:string'];
        }

        $iri = $this->triplestore->createEntity($rdfType, $properties, $user->getIri(), 'Created via API');

        return response()->json([
            '@context' => self::JSONLD_CONTEXT, '@type' => $rdfType, '@id' => $iri,
        ], 201, ['Content-Type' => 'application/ld+json']);
    }

    public function update(Request $request, string $entityType, string $iri): JsonResponse
    {
        $iri = urldecode($iri);
        $data = $request->validate(['title' => 'sometimes|string|max:1000', 'identifier' => 'nullable|string|max:255']);
        $user = $request->user();

        $properties = [];
        if (isset($data['title'])) {
            $properties['rico:title'] = ['value' => $data['title'], 'datatype' => 'xsd:string'];
        }
        if (isset($data['identifier'])) {
            $properties['rico:identifier'] = ['value' => $data['identifier'], 'datatype' => 'xsd:string'];
        }

        $this->triplestore->updateEntity($iri, $properties, $user->getIri(), 'Updated via API');

        return response()->json(['@context' => self::JSONLD_CONTEXT, '@id' => $iri], 200, ['Content-Type' => 'application/ld+json']);
    }

    public function destroy(Request $request, string $entityType, string $iri): JsonResponse
    {
        $iri = urldecode($iri);
        $user = $request->user();
        $this->triplestore->deleteEntity($iri, $user->getIri(), 'Deleted via API');

        return response()->json(null, 204);
    }
}
