<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenRiC\RecordManage\Contracts\AiNerServiceInterface;

/**
 * Service for AI/NER entity extraction and review operations.
 * Adapted from Heratio AiNerService (276 lines).
 *
 * Tables: ner_entities, ner_entity_links, ner_extractions, ner_usage
 */
class AiNerService implements AiNerServiceInterface
{
    public function getEntitiesForRecord(int $recordId): Collection
    {
        try {
            return DB::table('ner_entities')
                ->where('record_id', $recordId)
                ->orderBy('entity_type')
                ->orderBy('entity_value')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getPendingExtractions(): Collection
    {
        try {
            return DB::table('ner_entities')
                ->join('records', 'ner_entities.record_id', '=', 'records.id')
                ->where('ner_entities.status', 'pending')
                ->select(
                    'ner_entities.record_id as id',
                    'records.title',
                    'records.iri',
                    DB::raw("COUNT(CASE WHEN ner_entities.status = 'pending' THEN 1 END) as pending_count"),
                    DB::raw("(SELECT COUNT(*) FROM ner_entities AS ne2 WHERE ne2.record_id = ner_entities.record_id AND ne2.status = 'approved') as approved_count")
                )
                ->groupBy('ner_entities.record_id', 'records.title', 'records.iri')
                ->orderBy('pending_count', 'desc')
                ->limit(50)
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getExtraction(int $id): ?object
    {
        try {
            return DB::table('ner_extractions')->where('id', $id)->first();
        } catch (\Illuminate\Database\QueryException $e) {
            return null;
        }
    }

    public function getEntityLinks(int $recordId): Collection
    {
        try {
            return DB::table('ner_entity_links')
                ->join('ner_entities', 'ner_entities.id', '=', 'ner_entity_links.entity_id')
                ->leftJoin('agents', 'agents.id', '=', 'ner_entity_links.agent_id')
                ->where('ner_entities.record_id', $recordId)
                ->select(
                    'ner_entity_links.id as link_id', 'ner_entity_links.entity_id',
                    'ner_entity_links.agent_id', 'ner_entity_links.link_type',
                    'ner_entity_links.confidence', 'ner_entities.entity_type',
                    'ner_entities.entity_value', 'ner_entities.status as entity_status',
                    'agents.name as agent_name'
                )
                ->orderBy('ner_entities.entity_type')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getUsageStats(): object
    {
        $stats = new \stdClass();

        try {
            $stats->total_requests = DB::table('ner_usage')->count();
            $stats->today_requests = DB::table('ner_usage')->whereDate('created_at', today())->count();
            $stats->avg_response_time = DB::table('ner_usage')->whereNotNull('response_time_ms')->avg('response_time_ms');
            $stats->error_count = DB::table('ner_usage')->where('status_code', '>=', 400)->count();
        } catch (\Illuminate\Database\QueryException $e) {
            $stats->total_requests = $stats->today_requests = $stats->error_count = 0;
            $stats->avg_response_time = 0;
        }

        return $stats;
    }

    public function approveEntity(int $entityId, ?int $reviewedBy = null): bool
    {
        try {
            return DB::table('ner_entities')->where('id', $entityId)->update([
                'status' => 'approved', 'reviewed_by' => $reviewedBy, 'reviewed_at' => now(),
            ]) > 0;
        } catch (\Illuminate\Database\QueryException $e) {
            return false;
        }
    }

    public function rejectEntity(int $entityId, ?int $reviewedBy = null): bool
    {
        try {
            return DB::table('ner_entities')->where('id', $entityId)->update([
                'status' => 'rejected', 'reviewed_by' => $reviewedBy, 'reviewed_at' => now(),
            ]) > 0;
        } catch (\Illuminate\Database\QueryException $e) {
            return false;
        }
    }

    public function getExtractionHistory(int $recordId): Collection
    {
        try {
            return DB::table('ner_extractions')
                ->where('record_id', $recordId)
                ->orderBy('extracted_at', 'desc')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getPendingCount(): int
    {
        try {
            return DB::table('ner_entities')->where('status', 'pending')->count();
        } catch (\Illuminate\Database\QueryException $e) {
            return 0;
        }
    }

    public function findMatchingAgents(string $entityValue): array
    {
        try {
            $exact = DB::table('agents')
                ->where('name', $entityValue)
                ->select('id', 'name')
                ->get()->toArray();

            $exactIds = array_column($exact, 'id');

            $partial = DB::table('agents')
                ->where('name', 'ILIKE', '%' . $entityValue . '%')
                ->when(!empty($exactIds), fn ($q) => $q->whereNotIn('id', $exactIds))
                ->select('id', 'name')
                ->limit(5)
                ->get()->toArray();

            return ['exact' => $exact, 'partial' => $partial];
        } catch (\Illuminate\Database\QueryException $e) {
            return ['exact' => [], 'partial' => []];
        }
    }
}
