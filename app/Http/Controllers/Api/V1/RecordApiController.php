<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenRiC\RecordManage\Contracts\RecordServiceInterface;

/**
 * Record API Controller — REST + JSON-LD.
 *
 * All responses return application/ld+json with RiC-O @context.
 * Adapted from Heratio ahg-api pattern.
 */
class RecordApiController extends Controller
{
    private const JSONLD_CONTEXT = 'https://www.ica.org/standards/RiC/ontology';

    public function __construct(
        private readonly RecordServiceInterface $service,
    ) {}

    /**
     * GET /api/v1/records — Browse records.
     */
    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $limit = min(100, max(1, (int) $request->input('limit', 25)));
        $offset = ($page - 1) * $limit;

        $filters = $request->only(['q', 'level', 'parent_iri', 'creator_iri', 'date_from', 'date_to', 'sort', 'direction']);
        $result = $this->service->browse($filters, $limit, $offset);

        return response()->json([
            '@context' => self::JSONLD_CONTEXT,
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => $result['total'],
            'hydra:member' => $this->formatItems($result['items']),
            'hydra:view' => [
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => $this->pageUrl($request, 1),
                'hydra:last' => $this->pageUrl($request, max(1, (int) ceil($result['total'] / $limit))),
                'hydra:next' => $result['total'] > $offset + $limit ? $this->pageUrl($request, $page + 1) : null,
                'hydra:previous' => $page > 1 ? $this->pageUrl($request, $page - 1) : null,
            ],
        ], 200, ['Content-Type' => 'application/ld+json']);
    }

    /**
     * GET /api/v1/records/{iri} — Get single record.
     */
    public function show(string $iri): JsonResponse
    {
        $entity = $this->service->find(urldecode($iri));

        if ($entity === null) {
            return response()->json(['error' => 'Record not found', '@context' => self::JSONLD_CONTEXT], 404, ['Content-Type' => 'application/ld+json']);
        }

        return response()->json(array_merge(
            ['@context' => self::JSONLD_CONTEXT, '@type' => 'rico:Record'],
            $this->formatEntity($entity)
        ), 200, ['Content-Type' => 'application/ld+json']);
    }

    /**
     * POST /api/v1/records — Create record.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:1000',
            'identifier' => 'nullable|string|max:255',
            'scope_and_content' => 'nullable|string',
            'parent_iri' => 'nullable|string|max:2048',
        ]);

        $user = $request->user();
        $iri = $this->service->create($data, $user->getIri(), 'Created via API');

        $entity = $this->service->find($iri);

        return response()->json(array_merge(
            ['@context' => self::JSONLD_CONTEXT, '@type' => 'rico:Record'],
            $this->formatEntity($entity ?? ['iri' => $iri])
        ), 201, ['Content-Type' => 'application/ld+json', 'Location' => url("/api/v1/records/" . urlencode($iri))]);
    }

    /**
     * PUT /api/v1/records/{iri} — Update record.
     */
    public function update(Request $request, string $iri): JsonResponse
    {
        $iri = urldecode($iri);
        $existing = $this->service->find($iri);
        if ($existing === null) {
            return response()->json(['error' => 'Record not found'], 404, ['Content-Type' => 'application/ld+json']);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:1000',
            'identifier' => 'nullable|string|max:255',
            'scope_and_content' => 'nullable|string',
        ]);

        $user = $request->user();
        $this->service->update($iri, $data, $user->getIri(), 'Updated via API');

        $entity = $this->service->find($iri);

        return response()->json(array_merge(
            ['@context' => self::JSONLD_CONTEXT, '@type' => 'rico:Record'],
            $this->formatEntity($entity ?? [])
        ), 200, ['Content-Type' => 'application/ld+json']);
    }

    /**
     * DELETE /api/v1/records/{iri} — Delete record.
     */
    public function destroy(Request $request, string $iri): JsonResponse
    {
        $iri = urldecode($iri);
        $existing = $this->service->find($iri);
        if ($existing === null) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        $user = $request->user();
        $this->service->delete($iri, $user->getIri(), 'Deleted via API');

        return response()->json(null, 204);
    }

    private function formatItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                '@id' => $item['iri']['value'] ?? null,
                '@type' => 'rico:Record',
                'rico:title' => $item['title']['value'] ?? null,
                'rico:identifier' => $item['identifier']['value'] ?? null,
                'rico:hasRecordSetType' => $item['levelOfDescription']['value'] ?? null,
                'rico:scopeAndContent' => isset($item['scopeAndContent']) ? substr($item['scopeAndContent']['value'] ?? '', 0, 200) : null,
            ];
        }, $items);
    }

    private function formatEntity(array $entity): array
    {
        $result = ['@id' => $entity['iri'] ?? null];

        foreach ($entity['properties'] ?? [] as $property => $value) {
            $result[$property] = is_array($value) ? ($value['value'] ?? $value) : $value;
        }

        if (!empty($entity['children'])) {
            $result['rico:includesOrIncluded'] = array_map(fn ($c) => [
                '@id' => $c['iri']['value'] ?? null,
                'rico:title' => $c['title']['value'] ?? null,
            ], $entity['children']);
        }

        return $result;
    }

    private function pageUrl(Request $request, int $page): string
    {
        return $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => $page]));
    }
}
