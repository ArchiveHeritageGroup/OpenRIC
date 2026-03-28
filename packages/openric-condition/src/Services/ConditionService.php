<?php

declare(strict_types=1);

namespace OpenRiC\Condition\Services;

use Illuminate\Support\Facades\DB;
use OpenRiC\Condition\Contracts\ConditionServiceInterface;

class ConditionService implements ConditionServiceInterface
{
    public function assess(string $objectIri, array $data, int $userId): int
    {
        return DB::table('condition_assessments')->insertGetId([
            'object_iri' => $objectIri,
            'assessed_by' => $userId,
            'assessed_at' => now(),
            'condition_code' => $data['condition_code'],
            'condition_label' => $data['condition_label'],
            'conservation_priority' => $data['conservation_priority'] ?? 0,
            'completeness_pct' => $data['completeness_pct'] ?? 100,
            'hazards' => isset($data['hazards']) ? json_encode($data['hazards']) : null,
            'storage_requirements' => $data['storage_requirements'] ?? null,
            'recommendations' => $data['recommendations'] ?? null,
            'next_assessment_date' => $data['next_assessment_date'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getLatest(string $objectIri): ?array
    {
        $record = DB::table('condition_assessments')
            ->where('object_iri', $objectIri)
            ->orderByDesc('assessed_at')
            ->first();

        return $record ? (array) $record : null;
    }

    public function getHistory(string $objectIri): array
    {
        return DB::table('condition_assessments')
            ->where('object_iri', $objectIri)
            ->orderByDesc('assessed_at')
            ->limit(100)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();
    }

    public function getUpcoming(int $days = 30): array
    {
        return DB::table('condition_assessments')
            ->whereNotNull('next_assessment_date')
            ->where('next_assessment_date', '<=', now()->addDays($days))
            ->where('next_assessment_date', '>=', now())
            ->orderBy('next_assessment_date')
            ->limit(50)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();
    }

    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = DB::table('condition_assessments')
            ->join('users', 'condition_assessments.assessed_by', '=', 'users.id')
            ->select('condition_assessments.*', 'users.display_name as assessor_name')
            ->orderByDesc('assessed_at');

        if (isset($filters['condition_code'])) {
            $query->where('condition_code', $filters['condition_code']);
        }

        $total = $query->count();
        $items = $query->limit($limit)->offset($offset)->get()->map(fn ($r) => (array) $r)->toArray();

        return ['items' => $items, 'total' => $total];
    }
}
