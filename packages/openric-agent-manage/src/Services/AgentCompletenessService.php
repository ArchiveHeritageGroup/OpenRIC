<?php

declare(strict_types=1);

namespace OpenRiC\AgentManage\Services;

use Illuminate\Support\Facades\DB;

/**
 * Agent Completeness Service.
 * Adapted from Heratio AuthorityCompletenessService.
 * Calculates completeness scores for RiC-O Agent records based on
 * ISAAR(CPF)-equivalent fields. PostgreSQL ILIKE.
 */
class AgentCompletenessService
{
    public const FIELD_WEIGHTS = [
        'authorized_name'   => 15,
        'entity_type'       => 5,
        'dates_existence'   => 10,
        'history'           => 10,
        'places'            => 5,
        'legal_status'      => 3,
        'functions'         => 5,
        'mandates'          => 3,
        'internal_struct'   => 3,
        'general_context'   => 3,
        'description_id'    => 3,
        'sources'           => 3,
        'maintenance_notes' => 2,
        'external_ids'      => 10,
        'relations'         => 10,
        'resources'         => 5,
        'contacts'          => 5,
    ];

    public const LEVELS = [
        'stub'    => [0, 24],
        'minimal' => [25, 49],
        'partial' => [50, 74],
        'full'    => [75, 100],
    ];

    public function calculateScore(int $agentId): array
    {
        $fieldScores = [];
        $totalWeight = array_sum(self::FIELD_WEIGHTS);
        $earnedWeight = 0;

        $agentI18n = DB::table('actor_i18n')->where('id', $agentId)->where('culture', 'en')->first();

        $fieldScores['authorized_name'] = (!empty($agentI18n->authorized_form_of_name)) ? 1 : 0;
        $fieldScores['history'] = (!empty($agentI18n->history)) ? 1 : 0;
        $fieldScores['places'] = (!empty($agentI18n->places)) ? 1 : 0;
        $fieldScores['legal_status'] = (!empty($agentI18n->legal_status)) ? 1 : 0;
        $fieldScores['functions'] = (!empty($agentI18n->functions)) ? 1 : 0;
        $fieldScores['mandates'] = (!empty($agentI18n->mandates)) ? 1 : 0;
        $fieldScores['internal_struct'] = (!empty($agentI18n->internal_structures)) ? 1 : 0;
        $fieldScores['general_context'] = (!empty($agentI18n->general_context)) ? 1 : 0;
        $fieldScores['description_id'] = (!empty($agentI18n->description_identifier)) ? 1 : 0;
        $fieldScores['sources'] = (!empty($agentI18n->sources)) ? 1 : 0;
        $fieldScores['maintenance_notes'] = (!empty($agentI18n->revision_history)) ? 1 : 0;
        $fieldScores['dates_existence'] = (!empty($agentI18n->dates_of_existence)) ? 1 : 0;

        $agent = DB::table('actor')->where('id', $agentId)->first();
        $fieldScores['entity_type'] = ($agent && !empty($agent->entity_type_id)) ? 1 : 0;

        $fieldScores['external_ids'] = DB::table('agent_identifier')->where('agent_id', $agentId)->exists() ? 1 : 0;
        $fieldScores['relations'] = DB::table('relation')->where(fn ($q) => $q->where('subject_id', $agentId)->orWhere('object_id', $agentId))->exists() ? 1 : 0;
        $fieldScores['resources'] = DB::table('event')->where('actor_id', $agentId)->exists() ? 1 : 0;

        $fieldScores['contacts'] = 0;
        try { $fieldScores['contacts'] = DB::table('contact_information')->where('actor_id', $agentId)->exists() ? 1 : 0; } catch (\Exception $e) {}

        foreach ($fieldScores as $field => $score) {
            if ($score && isset(self::FIELD_WEIGHTS[$field])) {
                $earnedWeight += self::FIELD_WEIGHTS[$field];
            }
        }

        $percentage = $totalWeight > 0 ? (int) round(($earnedWeight / $totalWeight) * 100) : 0;
        $level = $this->determineLevel($percentage);

        $record = [
            'completeness_level' => $level,
            'completeness_score' => $percentage,
            'field_scores'       => json_encode($fieldScores),
            'has_external_ids'   => $fieldScores['external_ids'],
            'has_relations'      => $fieldScores['relations'],
            'has_resources'      => $fieldScores['resources'],
            'has_contacts'       => $fieldScores['contacts'],
            'scored_at'          => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ];

        $existing = DB::table('agent_completeness')->where('agent_id', $agentId)->first();
        if ($existing) {
            if ($existing->manual_override) { $record['completeness_level'] = $existing->completeness_level; }
            DB::table('agent_completeness')->where('id', $existing->id)->update($record);
        } else {
            $record['agent_id'] = $agentId;
            $record['created_at'] = date('Y-m-d H:i:s');
            DB::table('agent_completeness')->insert($record);
        }

        return ['score' => $percentage, 'level' => $level, 'field_scores' => $fieldScores];
    }

    public function determineLevel(int $score): string
    {
        foreach (self::LEVELS as $level => $range) {
            if ($score >= $range[0] && $score <= $range[1]) { return $level; }
        }
        return 'stub';
    }

    public function getCompleteness(int $agentId): ?object
    {
        return DB::table('agent_completeness')->where('agent_id', $agentId)->first();
    }

    public function getDashboardStats(): array
    {
        $byLevel = DB::table('agent_completeness')->select('completeness_level', DB::raw('COUNT(*) as count'))->groupBy('completeness_level')->get()->keyBy('completeness_level')->all();
        $totalScored = DB::table('agent_completeness')->count();
        $totalAgents = DB::table('actor')->count();
        $avgScore = DB::table('agent_completeness')->avg('completeness_score') ?? 0;
        $withExternalIds = DB::table('agent_completeness')->where('has_external_ids', 1)->count();
        $withRelations = DB::table('agent_completeness')->where('has_relations', 1)->count();

        return ['total_agents' => $totalAgents, 'total_scored' => $totalScored, 'unscored' => $totalAgents - $totalScored, 'avg_score' => round((float) $avgScore, 1), 'by_level' => $byLevel, 'with_external' => $withExternalIds, 'with_relations' => $withRelations];
    }

    public function getWorkqueue(array $filters = []): array
    {
        $query = DB::table('agent_completeness as c')
            ->join('actor_i18n as ai', fn ($j) => $j->on('c.agent_id', '=', 'ai.id')->where('ai.culture', '=', 'en'))
            ->leftJoin('slug', 'c.agent_id', '=', 'slug.object_id')
            ->select('c.*', 'ai.authorized_form_of_name as name', 'slug.slug');

        if (!empty($filters['level'])) { $query->where('c.completeness_level', $filters['level']); }
        $sort = $filters['sort'] ?? 'completeness_score';
        $dir = $filters['sortDir'] ?? 'asc';
        $limit = $filters['limit'] ?? 50;
        $page = $filters['page'] ?? 1;

        return $query->orderBy($sort, $dir)->paginate($limit, ['*'], 'page', $page)->toArray();
    }

    public function batchCalculate(int $limit = 0): int
    {
        $query = DB::table('actor')->select('actor.id')->orderBy('actor.id');
        if ($limit > 0) { $query->limit($limit); }
        $agentIds = $query->pluck('id')->all();
        $count = 0;
        foreach ($agentIds as $agentId) { $this->calculateScore((int) $agentId); $count++; }
        return $count;
    }
}
