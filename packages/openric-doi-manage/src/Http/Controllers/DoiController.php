<?php

declare(strict_types=1);

namespace OpenRiC\DoiManage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenRiC\DoiManage\Contracts\DoiServiceInterface;

/**
 * DOI management controller -- adapted from Heratio AhgDoiManage\Controllers\DoiController (360 lines).
 */
class DoiController extends Controller
{
    public function __construct(
        private readonly DoiServiceInterface $service,
    ) {}

    /**
     * Dashboard: list DOIs with stats.
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['page', 'limit', 'status']);
        $data   = $this->service->getEntitiesWithDoi($params);
        $stats  = $this->service->getStats();

        return response()->json(array_merge($data, ['stats' => $stats]));
    }

    /**
     * Mint a new DOI for an entity.
     */
    public function mint(Request $request): JsonResponse
    {
        $request->validate([
            'entity_iri' => 'required|string|max:2048',
            'title'      => 'required|string|max:1024',
            'metadata'   => 'nullable|array',
        ]);

        $result = $this->service->mintDoi(
            $request->input('entity_iri'),
            $request->input('title'),
            $request->input('metadata', []),
        );

        $statusCode = $result['success'] ? 201 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * Resolve a DOI to its entity IRI.
     */
    public function resolve(string $doi): JsonResponse
    {
        $entityIri = $this->service->resolveDoi($doi);

        if ($entityIri === null) {
            return response()->json(['error' => 'DOI not found.'], 404);
        }

        return response()->json(['doi' => $doi, 'entity_iri' => $entityIri]);
    }

    /**
     * Get DOI configuration settings.
     */
    public function settings(): JsonResponse
    {
        $settings = \Illuminate\Support\Facades\DB::table('settings')
            ->where('setting_group', 'doi')
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        // Mask sensitive fields
        if (isset($settings['datacite_password'])) {
            $settings['datacite_password'] = str_repeat('*', 8);
        }

        return response()->json(['settings' => $settings]);
    }
}
