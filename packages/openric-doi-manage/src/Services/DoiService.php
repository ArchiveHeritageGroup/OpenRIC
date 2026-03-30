<?php

declare(strict_types=1);

namespace OpenRiC\DoiManage\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenRiC\DoiManage\Contracts\DoiServiceInterface;

/**
 * DOI management service -- adapted from Heratio AhgDoiManage\Controllers\DoiController (360 lines).
 *
 * Full DataCite REST API integration: minting, metadata sync, deactivation/reactivation,
 * queue management, batch operations, activity logging, reporting, and CSV/JSON export.
 * Configuration is stored in the PostgreSQL 'settings' table (group='doi').
 */
class DoiService implements DoiServiceInterface
{
    // =========================================================================
    // Configuration
    // =========================================================================

    public function getConfig(): array
    {
        $settings = DB::table('settings')
            ->where('group', 'doi')
            ->pluck('value', 'key')
            ->toArray();

        return [
            'prefix'         => $settings['datacite_prefix'] ?? config('openric-doi.datacite_prefix', ''),
            'repository_id'  => $settings['datacite_repository_id'] ?? config('openric-doi.datacite_repository_id', ''),
            'password'       => $settings['datacite_password'] ?? config('openric-doi.datacite_password', ''),
            'url'            => $settings['datacite_url'] ?? config('openric-doi.datacite_url', 'https://api.test.datacite.org'),
            'environment'    => $settings['datacite_environment'] ?? config('openric-doi.datacite_environment', 'test'),
            'auto_mint'      => filter_var($settings['auto_mint'] ?? config('openric-doi.auto_mint', false), FILTER_VALIDATE_BOOLEAN),
            'publisher'      => $settings['default_publisher'] ?? config('openric-doi.default_publisher', config('app.name', 'OpenRiC')),
            'resource_type'  => $settings['default_resource_type'] ?? config('openric-doi.default_resource_type', 'Dataset'),
            'suffix_pattern' => $settings['suffix_pattern'] ?? config('openric-doi.suffix_pattern', '{year}/{entity_id}'),
            'max_attempts'   => (int) ($settings['max_attempts'] ?? config('openric-doi.max_attempts', 3)),
            'auto_mint_levels'    => $settings['auto_mint_levels'] ?? '[]',
            'require_digital_object' => $settings['require_digital_object'] ?? '0',
        ];
    }

    public function saveConfig(array $values): void
    {
        $allowedKeys = [
            'datacite_prefix',
            'datacite_repository_id',
            'datacite_password',
            'datacite_url',
            'datacite_environment',
            'auto_mint',
            'auto_mint_levels',
            'require_digital_object',
            'default_publisher',
            'default_resource_type',
            'suffix_pattern',
            'max_attempts',
        ];

        DB::transaction(function () use ($values, $allowedKeys): void {
            foreach ($allowedKeys as $key) {
                if (!array_key_exists($key, $values)) {
                    continue;
                }

                $val = is_array($values[$key]) ? json_encode($values[$key], JSON_THROW_ON_ERROR) : (string) $values[$key];

                $exists = DB::table('settings')
                    ->where('group', 'doi')
                    ->where('key', $key)
                    ->exists();

                if ($exists) {
                    DB::table('settings')
                        ->where('group', 'doi')
                        ->where('key', $key)
                        ->update(['value' => $val, 'updated_at' => now()]);
                } else {
                    DB::table('settings')->insert([
                        'group'      => 'doi',
                        'key'        => $key,
                        'value'      => $val,
                        'type'       => 'string',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function testConnection(): array
    {
        $config = $this->getConfig();

        if (empty($config['repository_id']) || empty($config['password'])) {
            return ['success' => false, 'message' => 'Repository ID and password are required.'];
        }

        try {
            $response = Http::withBasicAuth($config['repository_id'], $config['password'])
                ->timeout(10)
                ->get(rtrim($config['url'], '/') . '/heartbeat');

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connection successful (' . $config['environment'] . ' environment).'];
            }

            return ['success' => false, 'message' => 'DataCite returned HTTP ' . $response->status() . '.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // Dashboard & Stats
    // =========================================================================

    public function getStats(): array
    {
        $doiRow = DB::table('dois')
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status = 'findable' THEN 1 ELSE 0 END) as findable")
            ->selectRaw("SUM(CASE WHEN status = 'registered' THEN 1 ELSE 0 END) as registered")
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft")
            ->first();

        $pending = DB::table('doi_queue')->where('status', 'pending')->count();
        $failed  = DB::table('doi_queue')->where('status', 'failed')->count();

        return [
            'total'      => (int) ($doiRow->total ?? 0),
            'findable'   => (int) ($doiRow->findable ?? 0),
            'registered' => (int) ($doiRow->registered ?? 0),
            'draft'      => (int) ($doiRow->draft ?? 0),
            'pending'    => $pending,
            'failed'     => $failed,
        ];
    }

    public function getRecentDois(int $limit = 10): array
    {
        return DB::table('dois')
            ->select([
                'dois.id',
                'dois.doi',
                'dois.entity_iri',
                'dois.title',
                'dois.status',
                'dois.minted_at',
                'dois.created_at',
            ])
            ->orderByDesc('dois.created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    // =========================================================================
    // Browse & View
    // =========================================================================

    public function browse(array $params = []): LengthAwarePaginator
    {
        $perPage = min(100, max(1, (int) ($params['limit'] ?? config('openric-doi.hits_per_page', 20))));
        $status  = $params['status'] ?? null;

        $query = DB::table('dois')
            ->select([
                'dois.id',
                'dois.doi',
                'dois.entity_iri',
                'dois.title',
                'dois.status',
                'dois.minted_at',
                'dois.last_sync_at',
                'dois.created_at',
            ]);

        if ($status && in_array($status, ['draft', 'registered', 'findable', 'deleted'], true)) {
            $query->where('dois.status', $status);
        }

        return $query->orderByDesc('dois.minted_at')->paginate($perPage);
    }

    public function find(int $id): ?object
    {
        return DB::table('dois')
            ->where('dois.id', $id)
            ->select([
                'dois.id',
                'dois.doi',
                'dois.entity_iri',
                'dois.title',
                'dois.status',
                'dois.metadata',
                'dois.minted_at',
                'dois.last_sync_at',
                'dois.deactivated_at',
                'dois.deactivation_reason',
                'dois.created_at',
                'dois.updated_at',
            ])
            ->first();
    }

    public function getActivityLog(int $doiId): array
    {
        return DB::table('doi_log')
            ->where('doi_id', $doiId)
            ->select([
                'id',
                'doi_id',
                'event_type',
                'status_before',
                'status_after',
                'details',
                'performed_by',
                'performed_at',
            ])
            ->orderByDesc('performed_at')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    // =========================================================================
    // Minting
    // =========================================================================

    public function mintDoi(string $entityIri, string $title, array $metadata = []): array
    {
        $config = $this->getConfig();

        if (empty($config['prefix']) || empty($config['repository_id'])) {
            return ['success' => false, 'error' => 'DataCite configuration is incomplete. Set prefix and repository ID.'];
        }

        // Check for existing DOI
        $existing = DB::table('dois')->where('entity_iri', $entityIri)->first();
        if ($existing) {
            return ['success' => false, 'error' => 'Entity already has a DOI: ' . $existing->doi];
        }

        // Generate suffix from pattern
        $suffix = $this->generateSuffix($config['suffix_pattern'], $entityIri);
        $doi    = $config['prefix'] . '/' . $suffix;

        // Build DataCite payload per DataCite REST API v2
        $payload = [
            'data' => [
                'type'       => 'dois',
                'attributes' => [
                    'doi'             => $doi,
                    'event'           => 'publish',
                    'creators'        => $metadata['creators'] ?? [['name' => $config['publisher']]],
                    'titles'          => [['title' => $title]],
                    'publisher'       => ['name' => $config['publisher']],
                    'publicationYear' => $metadata['year'] ?? (int) date('Y'),
                    'types'           => ['resourceTypeGeneral' => $config['resource_type']],
                    'url'             => $metadata['url'] ?? config('app.url') . '/entity/' . urlencode($entityIri),
                    'schemaVersion'   => 'http://datacite.org/schema/kernel-4',
                ],
            ],
        ];

        // Add optional metadata fields
        if (!empty($metadata['descriptions'])) {
            $payload['data']['attributes']['descriptions'] = $metadata['descriptions'];
        }
        if (!empty($metadata['subjects'])) {
            $payload['data']['attributes']['subjects'] = $metadata['subjects'];
        }
        if (!empty($metadata['dates'])) {
            $payload['data']['attributes']['dates'] = $metadata['dates'];
        }
        if (!empty($metadata['identifiers'])) {
            $payload['data']['attributes']['relatedIdentifiers'] = $metadata['identifiers'];
        }
        if (!empty($metadata['rights'])) {
            $payload['data']['attributes']['rightsList'] = $metadata['rights'];
        }

        try {
            $response = Http::withBasicAuth($config['repository_id'], $config['password'])
                ->withHeaders(['Content-Type' => 'application/vnd.api+json'])
                ->timeout(30)
                ->post(rtrim($config['url'], '/') . '/dois', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $mintedDoi    = $responseData['data']['attributes']['doi'] ?? $doi;
                $mintedStatus = $responseData['data']['attributes']['state'] ?? 'findable';

                $doiId = DB::table('dois')->insertGetId([
                    'doi'          => $mintedDoi,
                    'entity_iri'   => $entityIri,
                    'title'        => $title,
                    'status'       => $mintedStatus,
                    'metadata'     => json_encode($metadata, JSON_THROW_ON_ERROR),
                    'minted_at'    => now(),
                    'last_sync_at' => now(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                $this->logActivity($doiId, 'mint', null, $mintedStatus, 'DOI minted via DataCite API.');

                return ['success' => true, 'doi' => $mintedDoi];
            }

            // API error -- save as draft locally
            $doiId = DB::table('dois')->insertGetId([
                'doi'        => $doi,
                'entity_iri' => $entityIri,
                'title'      => $title,
                'status'     => 'draft',
                'metadata'   => json_encode($metadata, JSON_THROW_ON_ERROR),
                'minted_at'  => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $errorBody = $response->body();
            $this->logActivity($doiId, 'mint_failed', null, 'draft', 'DataCite HTTP ' . $response->status() . ': ' . mb_substr($errorBody, 0, 500));

            return ['success' => false, 'error' => 'DataCite API error (HTTP ' . $response->status() . '): ' . mb_substr($errorBody, 0, 200)];
        } catch (\Throwable $e) {
            // Fallback: register locally as draft
            $doiId = DB::table('dois')->insertGetId([
                'doi'        => $doi,
                'entity_iri' => $entityIri,
                'title'      => $title,
                'status'     => 'draft',
                'metadata'   => json_encode($metadata, JSON_THROW_ON_ERROR),
                'minted_at'  => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->logActivity($doiId, 'mint_failed', null, 'draft', 'API unreachable: ' . $e->getMessage());
            Log::warning('DOI mint failed for entity ' . $entityIri . ': ' . $e->getMessage());

            return ['success' => false, 'error' => 'API unreachable. DOI saved as draft: ' . $e->getMessage()];
        }
    }

    public function batchMint(array $entityIris): array
    {
        $queued  = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($entityIris as $iri) {
            $iri = trim($iri);
            if ($iri === '') {
                continue;
            }

            // Skip if entity already has a DOI
            $existing = DB::table('dois')->where('entity_iri', $iri)->exists();
            if ($existing) {
                $skipped++;
                continue;
            }

            // Skip if already in queue
            $inQueue = DB::table('doi_queue')
                ->where('entity_iri', $iri)
                ->whereIn('status', ['pending', 'processing'])
                ->exists();
            if ($inQueue) {
                $skipped++;
                continue;
            }

            try {
                DB::table('doi_queue')->insert([
                    'entity_iri'   => $iri,
                    'action'       => 'mint',
                    'status'       => 'pending',
                    'attempts'     => 0,
                    'scheduled_at' => now(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
                $queued++;
            } catch (\Throwable $e) {
                $errors[$iri] = $e->getMessage();
            }
        }

        return ['queued' => $queued, 'skipped' => $skipped, 'errors' => $errors];
    }

    // =========================================================================
    // Sync & Deactivate
    // =========================================================================

    public function syncMetadata(int $doiId): array
    {
        $doi = $this->find($doiId);
        if (!$doi) {
            return ['success' => false, 'error' => 'DOI not found.'];
        }

        $config = $this->getConfig();
        if (empty($config['repository_id']) || empty($config['password'])) {
            return ['success' => false, 'error' => 'DataCite credentials not configured.'];
        }

        $metadata = json_decode($doi->metadata ?? '{}', true) ?: [];

        $payload = [
            'data' => [
                'type'       => 'dois',
                'attributes' => [
                    'titles'          => [['title' => $doi->title]],
                    'publisher'       => ['name' => $config['publisher']],
                    'publicationYear' => $metadata['year'] ?? (int) date('Y'),
                    'url'             => $metadata['url'] ?? config('app.url') . '/entity/' . urlencode($doi->entity_iri),
                ],
            ],
        ];

        try {
            $encodedDoi = urlencode($doi->doi);
            $response = Http::withBasicAuth($config['repository_id'], $config['password'])
                ->withHeaders(['Content-Type' => 'application/vnd.api+json'])
                ->timeout(30)
                ->put(rtrim($config['url'], '/') . '/dois/' . $encodedDoi, $payload);

            if ($response->successful()) {
                DB::table('dois')->where('id', $doiId)->update([
                    'last_sync_at' => now(),
                    'updated_at'   => now(),
                ]);

                $this->logActivity($doiId, 'sync', $doi->status, $doi->status, 'Metadata synced to DataCite.');

                return ['success' => true];
            }

            $this->logActivity($doiId, 'sync_failed', $doi->status, $doi->status, 'HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 500));

            return ['success' => false, 'error' => 'DataCite returned HTTP ' . $response->status() . '.'];
        } catch (\Throwable $e) {
            $this->logActivity($doiId, 'sync_failed', $doi->status, $doi->status, $e->getMessage());

            return ['success' => false, 'error' => 'Sync failed: ' . $e->getMessage()];
        }
    }

    public function deactivate(int $doiId, string $reason = ''): array
    {
        $doi = $this->find($doiId);
        if (!$doi) {
            return ['success' => false, 'error' => 'DOI not found.'];
        }

        if ($doi->status === 'deleted') {
            return ['success' => false, 'error' => 'DOI is already deactivated.'];
        }

        $config    = $this->getConfig();
        $oldStatus = $doi->status;

        // Attempt to hide at DataCite (set state to 'registered')
        if (!empty($config['repository_id']) && !empty($config['password'])) {
            try {
                $payload = [
                    'data' => [
                        'type'       => 'dois',
                        'attributes' => [
                            'event' => 'hide',
                        ],
                    ],
                ];

                $encodedDoi = urlencode($doi->doi);
                Http::withBasicAuth($config['repository_id'], $config['password'])
                    ->withHeaders(['Content-Type' => 'application/vnd.api+json'])
                    ->timeout(30)
                    ->put(rtrim($config['url'], '/') . '/dois/' . $encodedDoi, $payload);
            } catch (\Throwable $e) {
                Log::warning('Failed to hide DOI at DataCite: ' . $e->getMessage());
            }
        }

        DB::table('dois')->where('id', $doiId)->update([
            'status'              => 'deleted',
            'deactivated_at'      => now(),
            'deactivation_reason' => $reason,
            'updated_at'          => now(),
        ]);

        $this->logActivity($doiId, 'deactivate', $oldStatus, 'deleted', 'Deactivated. Reason: ' . ($reason ?: 'none'));

        return ['success' => true];
    }

    public function reactivate(int $doiId): array
    {
        $doi = $this->find($doiId);
        if (!$doi) {
            return ['success' => false, 'error' => 'DOI not found.'];
        }

        if ($doi->status !== 'deleted') {
            return ['success' => false, 'error' => 'DOI is not deactivated.'];
        }

        $config = $this->getConfig();

        // Attempt to publish at DataCite
        if (!empty($config['repository_id']) && !empty($config['password'])) {
            try {
                $payload = [
                    'data' => [
                        'type'       => 'dois',
                        'attributes' => [
                            'event' => 'publish',
                        ],
                    ],
                ];

                $encodedDoi = urlencode($doi->doi);
                Http::withBasicAuth($config['repository_id'], $config['password'])
                    ->withHeaders(['Content-Type' => 'application/vnd.api+json'])
                    ->timeout(30)
                    ->put(rtrim($config['url'], '/') . '/dois/' . $encodedDoi, $payload);
            } catch (\Throwable $e) {
                Log::warning('Failed to publish DOI at DataCite: ' . $e->getMessage());
            }
        }

        DB::table('dois')->where('id', $doiId)->update([
            'status'              => 'findable',
            'deactivated_at'      => null,
            'deactivation_reason' => null,
            'updated_at'          => now(),
        ]);

        $this->logActivity($doiId, 'reactivate', 'deleted', 'findable', 'DOI reactivated.');

        return ['success' => true];
    }

    // =========================================================================
    // Queue
    // =========================================================================

    public function browseQueue(array $params = []): LengthAwarePaginator
    {
        $perPage = min(100, max(1, (int) ($params['limit'] ?? config('openric-doi.hits_per_page', 20))));
        $status  = $params['status'] ?? null;

        $query = DB::table('doi_queue')
            ->select([
                'doi_queue.id',
                'doi_queue.entity_iri',
                'doi_queue.action',
                'doi_queue.status',
                'doi_queue.attempts',
                'doi_queue.scheduled_at',
                'doi_queue.error_message',
                'doi_queue.created_at',
            ]);

        if ($status && in_array($status, ['pending', 'processing', 'completed', 'failed'], true)) {
            $query->where('doi_queue.status', $status);
        }

        return $query->orderByDesc('doi_queue.created_at')->paginate($perPage);
    }

    public function getQueueCounts(): array
    {
        $rows = DB::table('doi_queue')
            ->selectRaw("status, COUNT(*) as cnt")
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        return [
            'pending'    => (int) ($rows['pending'] ?? 0),
            'processing' => (int) ($rows['processing'] ?? 0),
            'failed'     => (int) ($rows['failed'] ?? 0),
            'completed'  => (int) ($rows['completed'] ?? 0),
        ];
    }

    public function retryQueueItem(int $queueId): bool
    {
        $item = DB::table('doi_queue')->where('id', $queueId)->first();
        if (!$item || $item->status !== 'failed') {
            return false;
        }

        DB::table('doi_queue')->where('id', $queueId)->update([
            'status'        => 'pending',
            'error_message' => null,
            'scheduled_at'  => now(),
            'updated_at'    => now(),
        ]);

        return true;
    }

    // =========================================================================
    // Reports & Export
    // =========================================================================

    public function getMonthlyStats(int $months = 24): array
    {
        return DB::table('dois')
            ->selectRaw("TO_CHAR(minted_at, 'YYYY-MM') as month")
            ->selectRaw("SUM(CASE WHEN minted_at IS NOT NULL THEN 1 ELSE 0 END) as minted_count")
            ->selectRaw("SUM(CASE WHEN updated_at > created_at THEN 1 ELSE 0 END) as updated_count")
            ->whereNotNull('minted_at')
            ->groupByRaw("TO_CHAR(minted_at, 'YYYY-MM')")
            ->orderByDesc('month')
            ->limit($months)
            ->get()
            ->map(fn ($row) => [
                'month'         => $row->month,
                'minted_count'  => (int) $row->minted_count,
                'updated_count' => (int) $row->updated_count,
            ])
            ->toArray();
    }

    public function getByRepository(): array
    {
        // In OpenRiC, entities are RiC-O based. We group by the prefix portion
        // of entity_iri (the repository/fonds identifier).
        return DB::table('dois')
            ->selectRaw("COALESCE(SPLIT_PART(entity_iri, '/', 4), '[Unknown]') as repository_name")
            ->selectRaw("COUNT(*) as doi_count")
            ->groupByRaw("COALESCE(SPLIT_PART(entity_iri, '/', 4), '[Unknown]')")
            ->orderByDesc('doi_count')
            ->get()
            ->map(fn ($row) => [
                'repository_name' => $row->repository_name,
                'doi_count'       => (int) $row->doi_count,
            ])
            ->toArray();
    }

    public function export(array $filters = []): array
    {
        $query = DB::table('dois')
            ->select([
                'doi',
                'entity_iri',
                'title',
                'status',
                'minted_at',
                'last_sync_at',
                'created_at',
                'updated_at',
            ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('minted_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('minted_at', '<=', $filters['to_date'] . ' 23:59:59');
        }

        return $query->orderByDesc('minted_at')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Generate a DOI suffix from the configured pattern.
     */
    private function generateSuffix(string $pattern, string $entityIri): string
    {
        $entityId = basename($entityIri) ?: bin2hex(random_bytes(6));

        $replacements = [
            '{year}'        => date('Y'),
            '{month}'       => date('m'),
            '{entity_id}'   => $entityId,
            '{slug}'        => preg_replace('/[^a-z0-9]+/i', '-', $entityId),
            '{identifier}'  => $entityId,
            '{random}'      => bin2hex(random_bytes(4)),
        ];

        $suffix = str_replace(array_keys($replacements), array_values($replacements), $pattern);

        // Ensure suffix is valid for DOI (alphanumeric, hyphens, dots, slashes)
        $suffix = preg_replace('/[^a-zA-Z0-9\-._\/]/', '', $suffix);

        return $suffix !== '' ? $suffix : bin2hex(random_bytes(6));
    }

    /**
     * Write an entry to the doi_log table.
     */
    private function logActivity(int $doiId, string $eventType, ?string $statusBefore, ?string $statusAfter, string $details = ''): void
    {
        try {
            DB::table('doi_log')->insert([
                'doi_id'        => $doiId,
                'event_type'    => $eventType,
                'status_before' => $statusBefore,
                'status_after'  => $statusAfter,
                'details'       => $details,
                'performed_by'  => auth()->id(),
                'performed_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write DOI activity log: ' . $e->getMessage());
        }
    }
}
