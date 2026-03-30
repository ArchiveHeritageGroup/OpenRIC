<?php

declare(strict_types=1);

namespace OpenRiC\AgentManage\Services;

use Illuminate\Support\Facades\DB;

/**
 * External Agent Identifier CRUD.
 * Adapted from Heratio AuthorityIdentifierService.
 * Manages external identifiers (Wikidata, VIAF, ULAN, LCNAF, ISNI, ORCID, GND).
 */
class AgentIdentifierService
{
    public const URI_PATTERNS = [
        'wikidata' => 'https://www.wikidata.org/wiki/%s',
        'viaf'     => 'https://viaf.org/viaf/%s',
        'ulan'     => 'https://vocab.getty.edu/ulan/%s',
        'lcnaf'    => 'https://id.loc.gov/authorities/names/%s',
        'isni'     => 'https://isni.org/isni/%s',
        'orcid'    => 'https://orcid.org/%s',
        'gnd'      => 'https://d-nb.info/gnd/%s',
    ];

    public function getIdentifiers(int $agentId): array
    {
        return DB::table('agent_identifier')->where('agent_id', $agentId)->orderBy('identifier_type')->get()->all();
    }

    public function getById(int $id): ?object
    {
        return DB::table('agent_identifier')->where('id', $id)->first();
    }

    public function save(int $agentId, array $data): int
    {
        $type = $data['identifier_type'] ?? '';
        $value = trim($data['identifier_value'] ?? '');
        $uri = $data['uri'] ?? null;
        if (empty($uri) && isset(self::URI_PATTERNS[$type]) && !empty($value)) {
            $uri = sprintf(self::URI_PATTERNS[$type], $value);
        }

        $row = ['agent_id' => $agentId, 'identifier_type' => $type, 'identifier_value' => $value, 'uri' => $uri, 'label' => $data['label'] ?? null, 'source' => $data['source'] ?? 'manual', 'updated_at' => date('Y-m-d H:i:s')];

        $existing = DB::table('agent_identifier')->where('agent_id', $agentId)->where('identifier_type', $type)->first();
        if ($existing) {
            DB::table('agent_identifier')->where('id', $existing->id)->update($row);
            return (int) $existing->id;
        }
        $row['created_at'] = date('Y-m-d H:i:s');
        return (int) DB::table('agent_identifier')->insertGetId($row);
    }

    public function delete(int $id): bool
    {
        return DB::table('agent_identifier')->where('id', $id)->delete() > 0;
    }

    public function verify(int $id, int $userId): bool
    {
        return DB::table('agent_identifier')->where('id', $id)->update(['is_verified' => 1, 'verified_at' => date('Y-m-d H:i:s'), 'verified_by' => $userId, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
    }

    public function hasIdentifiers(int $agentId): bool
    {
        return DB::table('agent_identifier')->where('agent_id', $agentId)->exists();
    }

    public function getStats(): array
    {
        return DB::table('agent_identifier')->select('identifier_type', DB::raw('COUNT(*) as count'))->groupBy('identifier_type')->orderBy('count', 'desc')->get()->all();
    }

    public static function buildUri(string $type, string $value): ?string
    {
        if (isset(self::URI_PATTERNS[$type]) && !empty($value)) { return sprintf(self::URI_PATTERNS[$type], $value); }
        return null;
    }
}
