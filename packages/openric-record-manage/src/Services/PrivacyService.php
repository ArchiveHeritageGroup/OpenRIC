<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenRiC\RecordManage\Contracts\PrivacyServiceInterface;

/**
 * Service for privacy / PII / POPIA / GDPR operations.
 * Adapted from Heratio PrivacyService (275 lines).
 *
 * Tables: privacy_visual_redactions, privacy_dsar_requests, privacy_processing_activities, privacy_breaches
 */
class PrivacyService implements PrivacyServiceInterface
{
    public function getRedactions(int $recordId): Collection
    {
        try {
            return DB::table('privacy_visual_redactions')
                ->where('record_id', $recordId)
                ->orderBy('page_number')
                ->orderBy('created_at')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function saveRedaction(array $data): int
    {
        $record = [
            'record_id'         => $data['record_id'],
            'digital_object_id' => $data['digital_object_id'] ?? null,
            'page_number'       => $data['page_number'] ?? 1,
            'region_type'       => $data['region_type'] ?? 'rectangle',
            'coordinates'       => is_array($data['coordinates'] ?? null) ? json_encode($data['coordinates']) : ($data['coordinates'] ?? '{}'),
            'normalized'        => $data['normalized'] ?? true,
            'source'            => $data['source'] ?? 'manual',
            'linked_entity_id'  => $data['linked_entity_id'] ?? null,
            'label'             => $data['label'] ?? null,
            'color'             => $data['color'] ?? '#000000',
            'status'            => $data['status'] ?? 'pending',
            'created_by'        => $data['created_by'] ?? null,
            'updated_at'        => now(),
        ];

        if (!empty($data['id'])) {
            DB::table('privacy_visual_redactions')->where('id', $data['id'])->update($record);
            return (int) $data['id'];
        }

        $record['created_at'] = now();
        return DB::table('privacy_visual_redactions')->insertGetId($record);
    }

    public function deleteRedaction(int $id): bool
    {
        try {
            return DB::table('privacy_visual_redactions')->where('id', $id)->delete() > 0;
        } catch (\Illuminate\Database\QueryException $e) {
            return false;
        }
    }

    public function getDsarRequests(): Collection
    {
        try {
            return DB::table('privacy_dsar_requests')
                ->orderBy('received_date', 'desc')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getProcessingActivities(): Collection
    {
        try {
            return DB::table('privacy_processing_activities')
                ->orderBy('name')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getDashboardStats(): object
    {
        $stats = new \stdClass();

        try {
            $stats->dsar_total       = DB::table('privacy_dsar_requests')->count();
            $stats->dsar_pending     = DB::table('privacy_dsar_requests')->where('status', 'pending')->count();
            $stats->dsar_in_progress = DB::table('privacy_dsar_requests')->where('status', 'in_progress')->count();
            $stats->dsar_completed   = DB::table('privacy_dsar_requests')->where('status', 'completed')->count();
            $stats->dsar_overdue     = DB::table('privacy_dsar_requests')
                ->where('deadline_date', '<', now())->whereNull('completed_date')
                ->whereIn('status', ['pending', 'in_progress'])->count();
        } catch (\Illuminate\Database\QueryException $e) {
            $stats->dsar_total = $stats->dsar_pending = $stats->dsar_in_progress = $stats->dsar_completed = $stats->dsar_overdue = 0;
        }

        try {
            $stats->breach_total    = DB::table('privacy_breaches')->count();
            $stats->breach_open     = DB::table('privacy_breaches')->whereNotIn('status', ['resolved', 'closed'])->count();
            $stats->breach_critical = DB::table('privacy_breaches')->where('severity', 'critical')->whereNotIn('status', ['resolved', 'closed'])->count();
        } catch (\Illuminate\Database\QueryException $e) {
            $stats->breach_total = $stats->breach_open = $stats->breach_critical = 0;
        }

        try {
            $stats->processing_total      = DB::table('privacy_processing_activities')->count();
            $stats->processing_active     = DB::table('privacy_processing_activities')->where('status', 'active')->count();
            $stats->processing_review_due = DB::table('privacy_processing_activities')
                ->whereNotNull('next_review_date')->where('next_review_date', '<=', now()->addDays(30))->count();
        } catch (\Illuminate\Database\QueryException $e) {
            $stats->processing_total = $stats->processing_active = $stats->processing_review_due = 0;
        }

        try {
            $stats->redaction_total   = DB::table('privacy_visual_redactions')->count();
            $stats->redaction_pending = DB::table('privacy_visual_redactions')->where('status', 'pending')->count();
            $stats->redaction_applied = DB::table('privacy_visual_redactions')->where('status', 'applied')->count();
        } catch (\Illuminate\Database\QueryException $e) {
            $stats->redaction_total = $stats->redaction_pending = $stats->redaction_applied = 0;
        }

        return $stats;
    }
}
