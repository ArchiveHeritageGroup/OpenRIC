<?php

declare(strict_types=1);

namespace OpenRiC\AI\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Named Entity Recognition Service — adapted from Heratio NerService (428 lines).
 *
 * Extracts named entities (persons, organizations, places, dates, subjects)
 * from archival descriptions via LLM or dedicated NER API, then stores
 * results in ner_entities table for human review and linking.
 *
 * Workflow:
 *   1. Extract entities from text (via LLM or NER API)
 *   2. Store as pending in ner_entities table
 *   3. Archivist reviews: link to existing authority, approve, or reject
 *   4. Linked entities create relationships in the triplestore
 */
class NerService
{
    public function __construct(
        private readonly LlmService $llm,
    ) {}

    // =========================================================================
    // Entity Extraction — from Heratio lines 30-120
    // =========================================================================

    /**
     * Extract entities from text using LLM.
     *
     * @return array{persons: string[], organizations: string[], places: string[], dates: string[], subjects: string[]}
     */
    public function extract(string $text): array
    {
        // Try dedicated NER API first if available
        if ($this->isApiAvailable()) {
            $result = $this->extractViaApi($text);
            if ($result !== null) {
                return $result;
            }
        }

        // Fallback to LLM
        return $this->llm->extractEntities($text);
    }

    /**
     * Extract entities from text and store in the database for review.
     *
     * @return int Number of entities stored
     */
    public function extractAndStore(string $entityIri, string $text, ?int $userId = null): int
    {
        $entities = $this->extract($text);
        $count = 0;

        $typeMap = [
            'persons' => 'person',
            'organizations' => 'organization',
            'places' => 'place',
            'dates' => 'date',
            'subjects' => 'subject',
        ];

        foreach ($typeMap as $key => $type) {
            foreach ($entities[$key] ?? [] as $entityText) {
                if (empty(trim($entityText))) {
                    continue;
                }

                // Skip duplicates for this entity
                $exists = DB::table('ner_entities')
                    ->where('entity_iri', $entityIri)
                    ->where('entity_type', $type)
                    ->where('text', $entityText)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('ner_entities')->insert([
                    'entity_iri' => $entityIri,
                    'entity_type' => $type,
                    'text' => $entityText,
                    'normalized_text' => $this->normalizeEntityText($entityText),
                    'source' => 'llm',
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

        return $count;
    }

    // =========================================================================
    // Entity Review Workflow — from Heratio lines 120-260
    // =========================================================================

    /**
     * Get pending entities for a specific record.
     */
    public function getPendingEntities(string $entityIri): Collection
    {
        return DB::table('ner_entities')
            ->where('entity_iri', $entityIri)
            ->where('status', 'pending')
            ->orderBy('entity_type')
            ->orderBy('text')
            ->get();
    }

    /**
     * Get all entities for a record (any status).
     */
    public function getEntitiesForRecord(string $entityIri): Collection
    {
        return DB::table('ner_entities')
            ->where('entity_iri', $entityIri)
            ->orderBy('entity_type')
            ->orderBy('text')
            ->get();
    }

    /**
     * Get records with pending NER entities.
     */
    public function getPendingRecords(int $limit = 50): array
    {
        return DB::table('ner_entities')
            ->where('status', 'pending')
            ->select('entity_iri', DB::raw('COUNT(*) as entity_count'))
            ->groupBy('entity_iri')
            ->orderByDesc('entity_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Update entity status: link, approve, or reject.
     *
     * @param string $action 'link', 'approve', 'reject'
     * @param string|null $linkedIri IRI of the linked authority/term (for 'link' action)
     */
    public function updateEntityStatus(int $entityId, string $action, ?string $linkedIri = null, ?int $userId = null): bool
    {
        $entity = DB::table('ner_entities')->find($entityId);
        if (!$entity) {
            return false;
        }

        $update = [
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'updated_at' => now(),
        ];

        switch ($action) {
            case 'link':
                $update['status'] = 'linked';
                $update['linked_iri'] = $linkedIri;
                break;
            case 'approve':
                $update['status'] = 'approved';
                break;
            case 'reject':
                $update['status'] = 'rejected';
                break;
            default:
                return false;
        }

        return DB::table('ner_entities')->where('id', $entityId)->update($update) > 0;
    }

    /**
     * Bulk update entity statuses.
     *
     * @param array $decisions Array of ['id' => int, 'action' => string, 'linked_iri' => ?string]
     */
    public function bulkUpdateEntities(array $decisions, int $userId): int
    {
        $count = 0;
        foreach ($decisions as $decision) {
            if ($this->updateEntityStatus(
                (int) $decision['id'],
                $decision['action'],
                $decision['linked_iri'] ?? null,
                $userId
            )) {
                $count++;
            }
        }
        return $count;
    }

    // =========================================================================
    // Statistics — from Heratio lines 260-300
    // =========================================================================

    /**
     * Get NER statistics.
     */
    public function getStats(): array
    {
        return [
            'pending' => DB::table('ner_entities')->where('status', 'pending')->count(),
            'linked' => DB::table('ner_entities')->where('status', 'linked')->count(),
            'approved' => DB::table('ner_entities')->where('status', 'approved')->count(),
            'rejected' => DB::table('ner_entities')->where('status', 'rejected')->count(),
            'total' => DB::table('ner_entities')->count(),
            'by_type' => DB::table('ner_entities')
                ->select('entity_type', DB::raw('COUNT(*) as count'))
                ->groupBy('entity_type')
                ->pluck('count', 'entity_type')
                ->toArray(),
            'records_with_pending' => DB::table('ner_entities')
                ->where('status', 'pending')
                ->distinct('entity_iri')
                ->count('entity_iri'),
        ];
    }

    // =========================================================================
    // NER API Health — from Heratio lines 300-340
    // =========================================================================

    /**
     * Check if a dedicated NER API is available.
     */
    public function isApiAvailable(): bool
    {
        $endpoint = $this->llm->getAiSetting('ner', 'api_endpoint');
        return !empty($endpoint);
    }

    /**
     * Get NER API health details.
     */
    public function getApiHealth(): array
    {
        $endpoint = $this->llm->getAiSetting('ner', 'api_endpoint');
        if (empty($endpoint)) {
            return ['available' => false, 'error' => 'No NER API endpoint configured'];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get("{$endpoint}/health");
            return [
                'available' => $response->successful(),
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            return ['available' => false, 'endpoint' => $endpoint, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Extract via dedicated NER API (if configured).
     */
    private function extractViaApi(string $text): ?array
    {
        $endpoint = $this->llm->getAiSetting('ner', 'api_endpoint');
        if (empty($endpoint)) {
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->post("{$endpoint}/extract", ['text' => $text]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            return $this->normalizeApiResult($data);
        } catch (\Exception $e) {
            Log::warning("NER API extraction failed: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Normalize API result to standard format.
     */
    private function normalizeApiResult(array $data): array
    {
        return [
            'persons' => $data['persons'] ?? $data['PER'] ?? [],
            'organizations' => $data['organizations'] ?? $data['ORG'] ?? [],
            'places' => $data['places'] ?? $data['LOC'] ?? $data['GPE'] ?? [],
            'dates' => $data['dates'] ?? $data['DATE'] ?? [],
            'subjects' => $data['subjects'] ?? $data['MISC'] ?? [],
        ];
    }

    /**
     * Normalize entity text for deduplication.
     */
    private function normalizeEntityText(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return mb_strtolower($text);
    }
}
