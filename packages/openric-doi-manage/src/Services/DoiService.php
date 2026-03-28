<?php

declare(strict_types=1);

namespace OpenRiC\DoiManage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use OpenRiC\DoiManage\Contracts\DoiServiceInterface;

/**
 * DOI management service -- adapted from Heratio AhgDoiManage\Controllers\DoiController (360 lines).
 *
 * Implements DataCite REST API integration for DOI minting, updating, and resolution.
 */
class DoiService implements DoiServiceInterface
{
    private function getConfig(): array
    {
        $settings = DB::table('settings')
            ->where('setting_group', 'doi')
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        return [
            'prefix'        => $settings['datacite_prefix'] ?? '',
            'repository_id' => $settings['datacite_repository_id'] ?? '',
            'password'      => $settings['datacite_password'] ?? '',
            'url'           => $settings['datacite_url'] ?? 'https://api.test.datacite.org',
            'environment'   => $settings['datacite_environment'] ?? 'test',
            'auto_mint'     => filter_var($settings['auto_mint'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'publisher'     => $settings['default_publisher'] ?? config('app.name', 'OpenRiC'),
            'resource_type' => $settings['default_resource_type'] ?? 'Dataset',
        ];
    }

    public function mintDoi(string $entityIri, string $title, array $metadata = []): array
    {
        $config = $this->getConfig();

        if (empty($config['prefix']) || empty($config['repository_id'])) {
            return ['success' => false, 'error' => 'DataCite configuration is incomplete.'];
        }

        // Check for existing DOI
        $existing = DB::table('dois')->where('entity_iri', $entityIri)->first();
        if ($existing) {
            return ['success' => false, 'error' => 'Entity already has a DOI: ' . $existing->doi];
        }

        $suffix = bin2hex(random_bytes(6));
        $doi    = $config['prefix'] . '/' . $suffix;

        // Build DataCite payload
        $payload = [
            'data' => [
                'type'       => 'dois',
                'attributes' => [
                    'doi'    => $doi,
                    'event'  => 'publish',
                    'creators' => $metadata['creators'] ?? [['name' => $config['publisher']]],
                    'titles'   => [['title' => $title]],
                    'publisher' => $config['publisher'],
                    'publicationYear' => $metadata['year'] ?? (int) date('Y'),
                    'types'    => ['resourceTypeGeneral' => $config['resource_type']],
                    'url'      => $metadata['url'] ?? config('app.url') . '/entity/' . urlencode($entityIri),
                ],
            ],
        ];

        try {
            $response = Http::withBasicAuth($config['repository_id'], $config['password'])
                ->withHeaders(['Content-Type' => 'application/vnd.api+json'])
                ->post(rtrim($config['url'], '/') . '/dois', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $mintedDoi    = $responseData['data']['attributes']['doi'] ?? $doi;

                DB::table('dois')->insert([
                    'doi'        => $mintedDoi,
                    'entity_iri' => $entityIri,
                    'title'      => $title,
                    'status'     => 'findable',
                    'metadata'   => json_encode($metadata, JSON_THROW_ON_ERROR),
                    'minted_at'  => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['success' => true, 'doi' => $mintedDoi];
            }

            return ['success' => false, 'error' => 'DataCite API error: ' . $response->body()];
        } catch (\Throwable $e) {
            // Fallback: register locally even if API fails
            DB::table('dois')->insert([
                'doi'        => $doi,
                'entity_iri' => $entityIri,
                'title'      => $title,
                'status'     => 'draft',
                'metadata'   => json_encode($metadata, JSON_THROW_ON_ERROR),
                'minted_at'  => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['success' => false, 'error' => 'API unreachable. DOI saved as draft: ' . $e->getMessage()];
        }
    }

    public function resolveDoi(string $doi): ?string
    {
        $row = DB::table('dois')->where('doi', $doi)->first();

        return $row?->entity_iri;
    }

    public function getDoiForEntity(string $entityIri): ?object
    {
        return DB::table('dois')->where('entity_iri', $entityIri)->first();
    }

    public function getEntitiesWithDoi(array $params = []): array
    {
        $page   = max(1, (int) ($params['page'] ?? 1));
        $limit  = min(100, max(1, (int) ($params['limit'] ?? 20)));
        $status = $params['status'] ?? null;

        $q = DB::table('dois');

        if ($status && in_array($status, ['draft', 'registered', 'findable'], true)) {
            $q->where('status', $status);
        }

        $total = $q->count();

        $results = $q->orderByDesc('created_at')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        return [
            'results'  => $results,
            'total'    => $total,
            'page'     => $page,
            'lastPage' => max(1, (int) ceil($total / $limit)),
            'limit'    => $limit,
        ];
    }

    public function updateDoiMetadata(string $doi, array $metadata): array
    {
        $config = $this->getConfig();

        $row = DB::table('dois')->where('doi', $doi)->first();
        if (!$row) {
            return ['success' => false, 'error' => 'DOI not found locally.'];
        }

        // Merge existing metadata
        $existingMeta = json_decode($row->metadata ?? '{}', true) ?: [];
        $mergedMeta   = array_merge($existingMeta, $metadata);

        DB::table('dois')->where('doi', $doi)->update([
            'metadata'   => json_encode($mergedMeta, JSON_THROW_ON_ERROR),
            'title'      => $metadata['title'] ?? $row->title,
            'updated_at' => now(),
        ]);

        // Sync to DataCite if configured
        if (!empty($config['repository_id']) && !empty($config['password'])) {
            try {
                $payload = [
                    'data' => [
                        'type'       => 'dois',
                        'attributes' => [
                            'titles' => [['title' => $metadata['title'] ?? $row->title]],
                        ],
                    ],
                ];

                $encodedDoi = urlencode($doi);
                Http::withBasicAuth($config['repository_id'], $config['password'])
                    ->withHeaders(['Content-Type' => 'application/vnd.api+json'])
                    ->put(rtrim($config['url'], '/') . '/dois/' . $encodedDoi, $payload);
            } catch (\Throwable) {
                // Non-fatal; local update already persisted
            }
        }

        return ['success' => true];
    }

    public function getStats(): array
    {
        $row = DB::table('dois')
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status = 'findable' THEN 1 ELSE 0 END) as findable")
            ->selectRaw("SUM(CASE WHEN status = 'registered' THEN 1 ELSE 0 END) as registered")
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft")
            ->first();

        return [
            'total'      => (int) $row->total,
            'findable'   => (int) $row->findable,
            'registered' => (int) $row->registered,
            'draft'      => (int) $row->draft,
        ];
    }
}
