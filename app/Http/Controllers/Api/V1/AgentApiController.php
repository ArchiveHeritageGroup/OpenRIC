<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenRiC\AgentManage\Services\AgentService;

/**
 * Agent API Controller — REST + JSON-LD.
 * Handles all agent types (Person, CorporateBody, Family) via a unified endpoint.
 */
class AgentApiController extends Controller
{
    private const JSONLD_CONTEXT = 'https://www.ica.org/standards/RiC/ontology';

    private const TYPE_MAP = [
        'person' => 'rico:Person',
        'corporate_body' => 'rico:CorporateBody',
        'family' => 'rico:Family',
    ];

    public function __construct(
        private readonly AgentService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $limit = min(100, max(1, (int) $request->input('limit', 25)));
        $offset = ($page - 1) * $limit;

        $agentType = $request->input('type', '');
        $rdfType = self::TYPE_MAP[$agentType] ?? 'rico:Agent';

        $filters = $request->only(['q', 'sort', 'direction', 'entity_type']);
        $result = $this->service->browseByType($rdfType, $filters, $limit, $offset);

        return response()->json([
            '@context' => self::JSONLD_CONTEXT,
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => $result['total'],
            'hydra:member' => array_map(fn ($item) => [
                '@id' => $item['iri']['value'] ?? null,
                '@type' => $rdfType,
                'rico:hasOrHadAgentName' => $item['name']['value'] ?? null,
                'rico:identifier' => $item['identifier']['value'] ?? null,
                'rico:hasOrHadDemographicGroup' => $item['datesOfExistence']['value'] ?? null,
            ], $result['items']),
        ], 200, ['Content-Type' => 'application/ld+json']);
    }

    public function show(string $iri): JsonResponse
    {
        $entity = $this->service->findAgent(urldecode($iri));
        if ($entity === null) {
            return response()->json(['error' => 'Agent not found'], 404, ['Content-Type' => 'application/ld+json']);
        }

        $result = ['@context' => self::JSONLD_CONTEXT, '@id' => $entity['iri'] ?? null];
        foreach ($entity['properties'] ?? [] as $prop => $val) {
            $result[$prop] = is_array($val) ? ($val['value'] ?? $val) : $val;
        }

        if (!empty($entity['related_agents'])) {
            $result['rico:isOrWasRelatedTo'] = array_map(fn ($r) => [
                '@id' => $r['relatedAgent']['value'] ?? null,
                'rico:title' => $r['name']['value'] ?? null,
            ], $entity['related_agents']);
        }

        return response()->json($result, 200, ['Content-Type' => 'application/ld+json']);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|string|in:person,corporate_body,family',
            'authorized_form_of_name' => 'required|string|max:1000',
            'identifier' => 'nullable|string|max:255',
        ]);

        $rdfType = self::TYPE_MAP[$data['type']];
        unset($data['type']);

        $user = $request->user();
        $iri = $this->service->createAgent($rdfType, $data, $user->getIri(), 'Created via API');

        return response()->json([
            '@context' => self::JSONLD_CONTEXT,
            '@id' => $iri,
            '@type' => $rdfType,
        ], 201, ['Content-Type' => 'application/ld+json', 'Location' => url("/api/v1/agents/" . urlencode($iri))]);
    }

    public function update(Request $request, string $iri): JsonResponse
    {
        $iri = urldecode($iri);
        $existing = $this->service->findAgent($iri);
        if ($existing === null) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $data = $request->validate([
            'authorized_form_of_name' => 'sometimes|string|max:1000',
            'identifier' => 'nullable|string|max:255',
            'dates_of_existence' => 'nullable|string|max:500',
            'history' => 'nullable|string',
        ]);

        $user = $request->user();
        $this->service->updateAgent($iri, $data, $user->getIri(), 'Updated via API');

        return response()->json(['@context' => self::JSONLD_CONTEXT, '@id' => $iri], 200, ['Content-Type' => 'application/ld+json']);
    }

    public function destroy(Request $request, string $iri): JsonResponse
    {
        $iri = urldecode($iri);
        $user = $request->user();
        $this->service->deleteAgent($iri, $user->getIri(), 'Deleted via API');

        return response()->json(null, 204);
    }
}
