<?php

declare(strict_types=1);

namespace OpenRiC\AgentManage\Services;

use Illuminate\Support\Facades\DB;

/**
 * Agent Graph Service.
 * Adapted from Heratio AuthorityGraphService.
 * Builds graph data from the relation table for agent-to-agent visualization.
 */
class AgentGraphService
{
    public function getGraphData(int $agentId, int $depth = 1): array
    {
        $nodes = []; $edges = []; $visited = [];
        $this->buildGraph($agentId, $depth, $nodes, $edges, $visited);
        return ['nodes' => array_values($nodes), 'edges' => array_values($edges)];
    }

    protected function buildGraph(int $agentId, int $depth, array &$nodes, array &$edges, array &$visited): void
    {
        if ($depth < 0 || isset($visited[$agentId])) { return; }
        $visited[$agentId] = true;

        if (!isset($nodes[$agentId])) {
            $agent = $this->getAgentInfo($agentId);
            if ($agent) {
                $nodes[$agentId] = ['data' => ['id' => 'agent_' . $agentId, 'label' => $agent->name ?? ('Agent #' . $agentId), 'type' => $agent->entity_type ?? 'unknown', 'slug' => $agent->slug ?? '']];
            }
        }
        if ($depth === 0) { return; }

        $relations = DB::table('relation as r')
            ->leftJoin('relation_i18n as ri', fn ($j) => $j->on('r.id', '=', 'ri.id')->where('ri.culture', '=', 'en'))
            ->leftJoin('term_i18n as ti', fn ($j) => $j->on('r.type_id', '=', 'ti.id')->where('ti.culture', '=', 'en'))
            ->where(fn ($q) => $q->where('r.subject_id', $agentId)->orWhere('r.object_id', $agentId))
            ->select('r.id', 'r.subject_id', 'r.object_id', 'r.type_id', 'ri.description', 'ri.date as relation_date', 'ti.name as relation_type')
            ->get()->all();

        foreach ($relations as $rel) {
            $edgeKey = $rel->subject_id . '_' . $rel->object_id . '_' . $rel->type_id;
            if (!isset($edges[$edgeKey])) {
                $edges[$edgeKey] = ['data' => ['id' => 'edge_' . $rel->id, 'source' => 'agent_' . $rel->subject_id, 'target' => 'agent_' . $rel->object_id, 'label' => $rel->relation_type ?? 'related', 'date' => $rel->relation_date ?? '']];
            }
            $relatedId = ($rel->subject_id == $agentId) ? (int) $rel->object_id : (int) $rel->subject_id;
            $this->buildGraph($relatedId, $depth - 1, $nodes, $edges, $visited);
        }
    }

    protected function getAgentInfo(int $agentId): ?object
    {
        return DB::table('actor as a')
            ->leftJoin('actor_i18n as ai', fn ($j) => $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en'))
            ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as ti', fn ($j) => $j->on('a.entity_type_id', '=', 'ti.id')->where('ti.culture', '=', 'en'))
            ->where('a.id', $agentId)
            ->select('a.id', 'ai.authorized_form_of_name as name', 'slug.slug', 'ti.name as entity_type')
            ->first();
    }
}
