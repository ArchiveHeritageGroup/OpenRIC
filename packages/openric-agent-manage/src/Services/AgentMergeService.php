<?php

declare(strict_types=1);

namespace OpenRiC\AgentManage\Services;

use Illuminate\Support\Facades\DB;

/**
 * Agent Merge/Split Service.
 * Adapted from Heratio AuthorityMergeService.
 * Handles merge and split workflows for agent records.
 */
class AgentMergeService
{
    public function getMerge(int $id): ?object { return DB::table('agent_merge')->where('id', $id)->first(); }

    public function getMergeHistory(int $agentId): array
    {
        return DB::table('agent_merge')
            ->where(fn ($q) => $q->where('primary_agent_id', $agentId)->orWhereRaw("secondary_agent_ids::jsonb @> ?::jsonb", [json_encode($agentId)]))
            ->orderBy('created_at', 'desc')->get()->all();
    }

    public function compareAgents(int $primaryId, int $secondaryId): array
    {
        $primary = $this->getAgentDetail($primaryId);
        $secondary = $this->getAgentDetail($secondaryId);
        $fields = ['authorized_form_of_name', 'dates_of_existence', 'history', 'places', 'legal_status', 'functions', 'mandates', 'internal_structures', 'general_context', 'sources', 'description_identifier', 'revision_history'];
        $comparison = [];
        foreach ($fields as $field) { $comparison[$field] = ['primary' => $primary->$field ?? '', 'secondary' => $secondary->$field ?? '', 'match' => ($primary->$field ?? '') === ($secondary->$field ?? '')]; }

        $primaryRelations = DB::table('relation')->where(fn ($q) => $q->where('subject_id', $primaryId)->orWhere('object_id', $primaryId))->count();
        $secondaryRelations = DB::table('relation')->where(fn ($q) => $q->where('subject_id', $secondaryId)->orWhere('object_id', $secondaryId))->count();
        $primaryResources = DB::table('event')->where('actor_id', $primaryId)->count();
        $secondaryResources = DB::table('event')->where('actor_id', $secondaryId)->count();
        $primaryIds = DB::table('agent_identifier')->where('agent_id', $primaryId)->get()->all();
        $secondaryIds = DB::table('agent_identifier')->where('agent_id', $secondaryId)->get()->all();

        return ['primary' => $primary, 'secondary' => $secondary, 'comparison' => $comparison, 'primary_relations' => $primaryRelations, 'secondary_relations' => $secondaryRelations, 'primary_resources' => $primaryResources, 'secondary_resources' => $secondaryResources, 'primary_identifiers' => $primaryIds, 'secondary_identifiers' => $secondaryIds];
    }

    public function createMergeRequest(int $primaryId, array $secondaryIds, array $fieldChoices, int $userId, ?string $notes = null): int
    {
        $mergeId = (int) DB::table('agent_merge')->insertGetId(['merge_type' => 'merge', 'primary_agent_id' => $primaryId, 'secondary_agent_ids' => json_encode($secondaryIds), 'field_choices' => json_encode($fieldChoices), 'status' => 'pending', 'notes' => $notes, 'performed_by' => $userId, 'created_at' => date('Y-m-d H:i:s')]);
        return $mergeId;
    }

    public function executeMerge(int $mergeId, int $userId): bool
    {
        $merge = $this->getMerge($mergeId);
        if (!$merge || !in_array($merge->status, ['approved', 'pending'])) { return false; }
        $primaryId = (int) $merge->primary_agent_id;
        $secondaryIds = json_decode($merge->secondary_agent_ids, true) ?? [];
        $fieldChoices = json_decode($merge->field_choices, true) ?? [];
        $relationsTransferred = 0; $resourcesTransferred = 0;

        foreach ($secondaryIds as $secId) {
            $this->applyFieldChoices($primaryId, (int) $secId, $fieldChoices);
            $relationsTransferred += $this->transferRelations($primaryId, (int) $secId);
            $resourcesTransferred += $this->transferResources($primaryId, (int) $secId);
        }

        DB::table('agent_merge')->where('id', $mergeId)->update(['status' => 'completed', 'relations_transferred' => $relationsTransferred, 'resources_transferred' => $resourcesTransferred, 'performed_at' => date('Y-m-d H:i:s'), 'approved_by' => $userId, 'approved_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    protected function applyFieldChoices(int $primaryId, int $secondaryId, array $choices): void
    {
        $secondaryI18n = DB::table('actor_i18n')->where('id', $secondaryId)->where('culture', 'en')->first();
        if (!$secondaryI18n) { return; }
        $updates = [];
        foreach ($choices as $field => $source) { if ($source === 'secondary_' . $secondaryId && isset($secondaryI18n->$field)) { $updates[$field] = $secondaryI18n->$field; } }
        if (!empty($updates)) { DB::table('actor_i18n')->where('id', $primaryId)->where('culture', 'en')->update($updates); }
    }

    protected function transferRelations(int $primaryId, int $secondaryId): int
    {
        $count = DB::table('relation')->where('subject_id', $secondaryId)->where('object_id', '!=', $primaryId)->update(['subject_id' => $primaryId]);
        $count += DB::table('relation')->where('object_id', $secondaryId)->where('subject_id', '!=', $primaryId)->update(['object_id' => $primaryId]);
        return $count;
    }

    protected function transferResources(int $primaryId, int $secondaryId): int
    {
        return DB::table('event')->where('actor_id', $secondaryId)->update(['actor_id' => $primaryId]);
    }

    protected function getAgentDetail(int $agentId): ?object
    {
        return DB::table('actor_i18n as ai')->leftJoin('actor as a', 'ai.id', '=', 'a.id')->leftJoin('slug', 'a.id', '=', 'slug.object_id')->where('ai.id', $agentId)->where('ai.culture', 'en')->select('ai.*', 'slug.slug', 'a.entity_type_id')->first();
    }
}
