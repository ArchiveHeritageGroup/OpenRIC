<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenRiC\RecordManage\Contracts\ConditionServiceInterface;

/**
 * Service for condition report and assessment operations.
 * Adapted from Heratio ConditionService (250 lines).
 *
 * Tables: condition_reports, condition_damages
 */
class ConditionService implements ConditionServiceInterface
{
    public function getReportsForRecord(int $recordId): Collection
    {
        try {
            return DB::table('condition_reports')
                ->where('record_id', $recordId)
                ->orderBy('assessment_date', 'desc')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function getLatestReport(int $recordId): ?object
    {
        try {
            return DB::table('condition_reports')
                ->where('record_id', $recordId)
                ->orderBy('assessment_date', 'desc')
                ->first();
        } catch (\Illuminate\Database\QueryException $e) {
            return null;
        }
    }

    public function getReport(int $reportId): ?object
    {
        try {
            $report = DB::table('condition_reports')
                ->where('id', $reportId)
                ->first();

            if ($report) {
                $report->damages = $this->getDamages($reportId);
            }

            return $report;
        } catch (\Illuminate\Database\QueryException $e) {
            return null;
        }
    }

    public function getDamages(int $reportId): Collection
    {
        try {
            return DB::table('condition_damages')
                ->where('condition_report_id', $reportId)
                ->orderBy('created_at', 'asc')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            return collect();
        }
    }

    public function createReport(array $data): int
    {
        return DB::table('condition_reports')->insertGetId([
            'record_id'           => $data['record_id'],
            'assessor_user_id'    => $data['assessor_user_id'] ?? null,
            'assessment_date'     => $data['assessment_date'],
            'context'             => $data['context'] ?? 'routine',
            'overall_rating'      => $data['overall_rating'] ?? 'good',
            'summary'             => $data['summary'] ?? null,
            'recommendations'     => $data['recommendations'] ?? null,
            'priority'            => $data['priority'] ?? 'normal',
            'next_check_date'     => $data['next_check_date'] ?? null,
            'environmental_notes' => $data['environmental_notes'] ?? null,
            'handling_notes'      => $data['handling_notes'] ?? null,
            'display_notes'       => $data['display_notes'] ?? null,
            'storage_notes'       => $data['storage_notes'] ?? null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    public function updateReport(int $id, array $data): bool
    {
        $update = [];
        $fields = [
            'assessor_user_id', 'assessment_date', 'context', 'overall_rating',
            'summary', 'recommendations', 'priority', 'next_check_date',
            'environmental_notes', 'handling_notes', 'display_notes', 'storage_notes',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        $update['updated_at'] = now();

        return DB::table('condition_reports')
            ->where('id', $id)
            ->update($update) >= 0;
    }

    public function deleteReport(int $id): bool
    {
        DB::table('condition_damages')
            ->where('condition_report_id', $id)
            ->delete();

        return DB::table('condition_reports')
            ->where('id', $id)
            ->delete() > 0;
    }

    public function addDamage(int $reportId, array $data): int
    {
        return DB::table('condition_damages')->insertGetId([
            'condition_report_id' => $reportId,
            'damage_type'         => $data['damage_type'],
            'location'            => $data['location'] ?? 'overall',
            'severity'            => $data['severity'] ?? 'minor',
            'description'         => $data['description'] ?? null,
            'dimensions'          => $data['dimensions'] ?? null,
            'is_active'           => $data['is_active'] ?? true,
            'treatment_required'  => $data['treatment_required'] ?? false,
            'treatment_notes'     => $data['treatment_notes'] ?? null,
            'created_at'          => now(),
        ]);
    }

    public function getRatingOptions(): array
    {
        return [
            'excellent'    => 'Excellent',
            'good'         => 'Good',
            'fair'         => 'Fair',
            'poor'         => 'Poor',
            'unacceptable' => 'Unacceptable',
        ];
    }

    public function getContextOptions(): array
    {
        return [
            'routine'       => 'Routine',
            'acquisition'   => 'Acquisition',
            'loan_in'       => 'Loan In',
            'loan_out'      => 'Loan Out',
            'exhibition'    => 'Exhibition',
            'conservation'  => 'Conservation',
            'storage'       => 'Storage',
            'transit'       => 'Transit',
            'damage_report' => 'Damage Report',
            'insurance'     => 'Insurance',
            'audit'         => 'Audit',
            'other'         => 'Other',
        ];
    }

    public function getPriorityOptions(): array
    {
        return [
            'low'      => 'Low',
            'normal'   => 'Normal',
            'high'     => 'High',
            'urgent'   => 'Urgent',
            'critical' => 'Critical',
        ];
    }

    public function getDamageTypeOptions(): array
    {
        return [
            'tear'           => 'Tear',
            'crack'          => 'Crack',
            'scratch'        => 'Scratch',
            'stain'          => 'Stain',
            'foxing'         => 'Foxing',
            'mould'          => 'Mould',
            'insect_damage'  => 'Insect Damage',
            'water_damage'   => 'Water Damage',
            'fire_damage'    => 'Fire Damage',
            'fading'         => 'Fading',
            'discolouration' => 'Discolouration',
            'deformation'    => 'Deformation',
            'loss'           => 'Loss',
            'abrasion'       => 'Abrasion',
            'corrosion'      => 'Corrosion',
            'other'          => 'Other',
        ];
    }

    public function getSeverityOptions(): array
    {
        return [
            'negligible' => 'Negligible',
            'minor'      => 'Minor',
            'moderate'   => 'Moderate',
            'severe'     => 'Severe',
            'critical'   => 'Critical',
        ];
    }
}
