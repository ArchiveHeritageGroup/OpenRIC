<?php

declare(strict_types=1);

namespace OpenRiC\Triplestore\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * RiC Sync Service — adapted from Heratio RicController (2,145 lines).
 *
 * In Heratio, sync goes MySQL → Fuseki (one-way push).
 * In OpenRiC, Fuseki IS the source of truth. This service manages:
 *   - Sync queue for external integrations (Elasticsearch, Qdrant, OAI-PMH)
 *   - Dashboard statistics (triple counts, queue status, sync health)
 *   - Orphan detection (entities in queue but not in triplestore)
 *   - Sync log (audit trail of sync operations)
 *   - Fuseki health monitoring
 */
class RicSyncService
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    // =========================================================================
    // Dashboard — from Heratio index() (lines 26-120)
    // =========================================================================

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        $stats = [
            'fuseki_online' => false,
            'triple_count' => 0,
            'entity_counts' => [],
            'queue_status' => [],
            'recent_operations' => [],
            'sync_trend' => [],
            'error_count' => 0,
        ];

        // Fuseki health
        try {
            $countSparql = 'SELECT (COUNT(*) AS ?count) WHERE { ?s ?p ?o }';
            $result = $this->triplestore->select($countSparql);
            $stats['fuseki_online'] = true;
            $stats['triple_count'] = (int) ($result[0]['count']['value'] ?? 0);
        } catch (\Exception) {
            $stats['fuseki_online'] = false;
        }

        // Entity counts by RDF type
        if ($stats['fuseki_online']) {
            try {
                $entitySparql = <<<'SPARQL'
                    SELECT ?type (COUNT(?s) AS ?count) WHERE {
                        ?s a ?type .
                        FILTER(STRSTARTS(STR(?type), "https://www.ica.org/standards/RiC/ontology#"))
                    }
                    GROUP BY ?type
                    ORDER BY DESC(?count)
                    LIMIT 20
                    SPARQL;

                $stats['entity_counts'] = $this->triplestore->select($entitySparql);
            } catch (\Exception) {
                $stats['entity_counts'] = [];
            }
        }

        // Queue status
        try {
            $stats['queue_status'] = DB::table('ric_sync_queue')
                ->selectRaw("status, COUNT(*) as count")
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        } catch (\Exception) {
        }

        // Recent operations
        try {
            $stats['recent_operations'] = DB::table('ric_sync_log')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->toArray();
        } catch (\Exception) {
        }

        // 7-day sync trend
        try {
            $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();
            $stats['sync_trend'] = DB::table('ric_sync_log')
                ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
                ->where('created_at', '>=', $sevenDaysAgo)
                ->groupByRaw("DATE(created_at)")
                ->orderBy('date')
                ->get()
                ->toArray();
        } catch (\Exception) {
        }

        // Error count (last 24h)
        try {
            $stats['error_count'] = DB::table('ric_sync_queue')
                ->where('status', 'failed')
                ->where('updated_at', '>=', now()->subDay())
                ->count();
        } catch (\Exception) {
        }

        return $stats;
    }

    // =========================================================================
    // Queue Management — from Heratio syncStatus/queueIndex/queueRetry
    // =========================================================================

    /**
     * Get sync queue entries with filters and pagination.
     */
    public function getQueue(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = DB::table('ric_sync_queue');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        $total = $query->count();

        $items = (clone $query)
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Queue an entity for sync (to Elasticsearch, Qdrant, or other consumers).
     */
    public function queueSync(string $entityType, string $entityIri, string $action, ?int $userId = null, ?array $payload = null): int
    {
        return DB::table('ric_sync_queue')->insertGetId([
            'entity_type' => $entityType,
            'entity_iri' => $entityIri,
            'action' => $action,
            'status' => 'pending',
            'retry_count' => 0,
            'payload' => $payload ? json_encode($payload) : null,
            'triggered_by' => $userId,
            'scheduled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Retry a failed sync queue entry.
     */
    public function retryQueueEntry(int $id): bool
    {
        return DB::table('ric_sync_queue')
            ->where('id', $id)
            ->where('status', 'failed')
            ->update([
                'status' => 'pending',
                'error_message' => null,
                'scheduled_at' => now(),
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Retry all failed entries.
     */
    public function retryAllFailed(): int
    {
        return DB::table('ric_sync_queue')
            ->where('status', 'failed')
            ->update([
                'status' => 'pending',
                'error_message' => null,
                'scheduled_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Mark a queue entry as processing.
     */
    public function markProcessing(int $id): bool
    {
        return DB::table('ric_sync_queue')
            ->where('id', $id)
            ->where('status', 'pending')
            ->update(['status' => 'processing', 'updated_at' => now()]) > 0;
    }

    /**
     * Mark a queue entry as synced (success).
     */
    public function markSynced(int $id, int $triplesAffected = 0, ?int $durationMs = null): void
    {
        DB::table('ric_sync_queue')
            ->where('id', $id)
            ->update([
                'status' => 'synced',
                'processed_at' => now(),
                'updated_at' => now(),
            ]);

        $entry = DB::table('ric_sync_queue')->find($id);
        if ($entry) {
            $this->logSync($entry->entity_type, $entry->entity_iri, $entry->action, 'success', $triplesAffected, $durationMs, null, $id);
        }
    }

    /**
     * Mark a queue entry as failed.
     */
    public function markFailed(int $id, string $errorMessage): void
    {
        DB::table('ric_sync_queue')
            ->where('id', $id)
            ->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
                'retry_count' => DB::raw('retry_count + 1'),
                'updated_at' => now(),
            ]);

        $entry = DB::table('ric_sync_queue')->find($id);
        if ($entry) {
            $this->logSync($entry->entity_type, $entry->entity_iri, $entry->action, 'failure', 0, null, $errorMessage, $id);
        }
    }

    /**
     * Clear completed/synced entries older than N days.
     */
    public function purgeCompleted(int $olderThanDays = 30): int
    {
        return DB::table('ric_sync_queue')
            ->where('status', 'synced')
            ->where('processed_at', '<', now()->subDays($olderThanDays))
            ->delete();
    }

    // =========================================================================
    // Sync Log — from Heratio syncLog (lines 350-450)
    // =========================================================================

    /**
     * Get sync log entries with filters.
     */
    public function getSyncLog(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = DB::table('ric_sync_log');

        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $total = $query->count();
        $items = (clone $query)->orderByDesc('created_at')->offset($offset)->limit($limit)->get()->toArray();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Log a sync operation.
     */
    public function logSync(string $entityType, string $entityIri, string $action, string $status, int $triplesAffected = 0, ?int $durationMs = null, ?string $errorMessage = null, ?int $queueId = null): void
    {
        DB::table('ric_sync_log')->insert([
            'queue_id' => $queueId,
            'entity_type' => $entityType,
            'entity_iri' => $entityIri,
            'action' => $action,
            'status' => $status,
            'triples_affected' => $triplesAffected,
            'duration_ms' => $durationMs,
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);
    }

    // =========================================================================
    // Fuseki Health — from Heratio fusekiStatus/fusekiTest
    // =========================================================================

    /**
     * Get Fuseki endpoint health status.
     */
    public function getFusekiHealth(): array
    {
        $health = [
            'online' => false,
            'endpoint' => config('openric.fuseki.endpoint', env('FUSEKI_ENDPOINT')),
            'triple_count' => 0,
            'response_time_ms' => null,
            'version' => null,
        ];

        $start = microtime(true);

        try {
            $result = $this->triplestore->select('SELECT (COUNT(*) AS ?count) WHERE { ?s ?p ?o }');
            $health['online'] = true;
            $health['triple_count'] = (int) ($result[0]['count']['value'] ?? 0);
            $health['response_time_ms'] = (int) ((microtime(true) - $start) * 1000);
        } catch (\Exception $e) {
            $health['error'] = $e->getMessage();
            $health['response_time_ms'] = (int) ((microtime(true) - $start) * 1000);
        }

        return $health;
    }

    /**
     * Validate an entity exists in the triplestore.
     */
    public function validateEntity(string $iri): bool
    {
        $sparql = 'ASK WHERE { ?entity ?p ?o }';

        try {
            return $this->triplestore->ask($sparql, ['entity' => $iri]);
        } catch (\Exception) {
            return false;
        }
    }

    // =========================================================================
    // Reports — from Heratio reconcile/stats (lines 500-600)
    // =========================================================================

    /**
     * Get entity type breakdown from triplestore.
     */
    public function getEntityTypeBreakdown(): array
    {
        try {
            $sparql = <<<'SPARQL'
                SELECT ?type (COUNT(?s) AS ?count) WHERE {
                    ?s a ?type .
                }
                GROUP BY ?type
                ORDER BY DESC(?count)
                LIMIT 50
                SPARQL;

            return $this->triplestore->select($sparql);
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Get property usage statistics.
     */
    public function getPropertyStats(): array
    {
        try {
            $sparql = <<<'SPARQL'
                SELECT ?p (COUNT(?s) AS ?count) WHERE {
                    ?s ?p ?o .
                    FILTER(STRSTARTS(STR(?p), "https://www.ica.org/standards/RiC/ontology#"))
                }
                GROUP BY ?p
                ORDER BY DESC(?count)
                LIMIT 50
                SPARQL;

            return $this->triplestore->select($sparql);
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Get sync statistics for reporting.
     */
    public function getSyncStats(int $days = 30): array
    {
        $since = now()->subDays($days);

        return [
            'total_synced' => DB::table('ric_sync_log')->where('status', 'success')->where('created_at', '>=', $since)->count(),
            'total_failed' => DB::table('ric_sync_log')->where('status', 'failure')->where('created_at', '>=', $since)->count(),
            'avg_duration_ms' => (int) DB::table('ric_sync_log')->where('status', 'success')->where('created_at', '>=', $since)->avg('duration_ms'),
            'total_triples_affected' => (int) DB::table('ric_sync_log')->where('status', 'success')->where('created_at', '>=', $since)->sum('triples_affected'),
            'queue_pending' => DB::table('ric_sync_queue')->where('status', 'pending')->count(),
            'queue_failed' => DB::table('ric_sync_queue')->where('status', 'failed')->count(),
            'by_entity_type' => DB::table('ric_sync_log')
                ->where('created_at', '>=', $since)
                ->selectRaw("entity_type, status, COUNT(*) as count")
                ->groupBy('entity_type', 'status')
                ->get()
                ->toArray(),
            'by_day' => DB::table('ric_sync_log')
                ->where('created_at', '>=', $since)
                ->selectRaw("DATE(created_at) as date, status, COUNT(*) as count")
                ->groupByRaw("DATE(created_at), status")
                ->orderBy('date')
                ->get()
                ->toArray(),
        ];
    }
}
