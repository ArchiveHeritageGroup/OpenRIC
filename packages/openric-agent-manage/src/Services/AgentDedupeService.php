<?php

declare(strict_types=1);

namespace OpenRiC\AgentManage\Services;

use Illuminate\Support\Facades\DB;

/**
 * Agent Deduplication Service.
 * Adapted from Heratio AuthorityDedupeService.
 * Uses Jaro-Winkler similarity algorithm. PostgreSQL ILIKE.
 */
class AgentDedupeService
{
    protected float $threshold = 0.80;

    public function __construct()
    {
        try {
            $row = DB::table('agent_config')->where('config_key', 'dedup_threshold')->first();
            if ($row && is_numeric($row->config_value)) { $this->threshold = (float) $row->config_value; }
        } catch (\Exception $e) {}
    }

    public function scan(int $limit = 500): array
    {
        $agents = DB::table('actor_i18n as ai')
            ->join('actor as a', 'ai.id', '=', 'a.id')
            ->where('ai.culture', 'en')
            ->whereNotNull('ai.authorized_form_of_name')
            ->where('ai.authorized_form_of_name', '!=', '')
            ->select('ai.id', 'ai.authorized_form_of_name as name', 'ai.dates_of_existence as dates')
            ->orderBy('ai.id')->limit($limit)->get()->all();

        $pairs = [];
        $count = count($agents);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $score = $this->calculateSimilarity($agents[$i], $agents[$j]);
                if ($score >= $this->threshold) {
                    $pairs[] = ['agent_a_id' => $agents[$i]->id, 'agent_a_name' => $agents[$i]->name, 'agent_b_id' => $agents[$j]->id, 'agent_b_name' => $agents[$j]->name, 'score' => round($score, 4), 'match_type' => $this->getMatchType($score)];
                }
            }
        }
        usort($pairs, fn ($a, $b) => $b['score'] <=> $a['score']);
        return $pairs;
    }

    public function calculateSimilarity(object $a, object $b): float
    {
        $nameA = $this->normalizeText($a->name ?? '');
        $nameB = $this->normalizeText($b->name ?? '');
        if (empty($nameA) || empty($nameB)) { return 0.0; }
        $nameSimilarity = $this->jaroWinkler($nameA, $nameB);
        $dateBoost = (!empty($a->dates) && !empty($b->dates) && $a->dates === $b->dates) ? 0.10 : 0.0;
        $idBoost = $this->checkSharedIdentifiers((int) $a->id, (int) $b->id);
        return min(1.0, $nameSimilarity + $dateBoost + $idBoost);
    }

    protected function checkSharedIdentifiers(int $idA, int $idB): float
    {
        try {
            $idsA = DB::table('agent_identifier')->where('agent_id', $idA)->get()->all();
            foreach ($idsA as $identA) {
                if (DB::table('agent_identifier')->where('agent_id', $idB)->where('identifier_type', $identA->identifier_type)->where('identifier_value', $identA->identifier_value)->exists()) { return 0.30; }
            }
        } catch (\Exception $e) {}
        return 0.0;
    }

    public function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/^(mr|mrs|ms|dr|prof|sir|dame|rev)\.?\s+/i', '', $text);
        return $text;
    }

    public function jaroWinkler(string $s1, string $s2, float $p = 0.1): float
    {
        $jaro = $this->jaro($s1, $s2);
        $prefix = 0;
        $maxPrefix = min(4, min(mb_strlen($s1), mb_strlen($s2)));
        for ($i = 0; $i < $maxPrefix; $i++) {
            if (mb_substr($s1, $i, 1) === mb_substr($s2, $i, 1)) { $prefix++; } else { break; }
        }
        return $jaro + ($prefix * $p * (1 - $jaro));
    }

    protected function jaro(string $s1, string $s2): float
    {
        $len1 = mb_strlen($s1); $len2 = mb_strlen($s2);
        if ($len1 === 0 && $len2 === 0) { return 1.0; }
        if ($len1 === 0 || $len2 === 0) { return 0.0; }
        $matchDistance = max(0, (int) floor(max($len1, $len2) / 2 - 1));
        $s1Matches = array_fill(0, $len1, false); $s2Matches = array_fill(0, $len2, false);
        $matches = 0; $transpositions = 0;
        for ($i = 0; $i < $len1; $i++) {
            $start = max(0, $i - $matchDistance); $end = min($i + $matchDistance + 1, $len2);
            for ($j = $start; $j < $end; $j++) {
                if ($s2Matches[$j] || mb_substr($s1, $i, 1) !== mb_substr($s2, $j, 1)) { continue; }
                $s1Matches[$i] = true; $s2Matches[$j] = true; $matches++; break;
            }
        }
        if ($matches === 0) { return 0.0; }
        $k = 0;
        for ($i = 0; $i < $len1; $i++) {
            if (!$s1Matches[$i]) { continue; }
            while (!$s2Matches[$k]) { $k++; }
            if (mb_substr($s1, $i, 1) !== mb_substr($s2, $k, 1)) { $transpositions++; }
            $k++;
        }
        return (($matches / $len1) + ($matches / $len2) + (($matches - $transpositions / 2) / $matches)) / 3;
    }

    protected function getMatchType(float $score): string
    {
        if ($score >= 0.95) { return 'exact'; }
        if ($score >= 0.85) { return 'strong'; }
        if ($score >= 0.80) { return 'possible'; }
        return 'weak';
    }

    public function getStats(): array
    {
        return [
            'threshold' => $this->threshold,
            'total_agents' => DB::table('actor')->count(),
            'total_merges' => DB::table('agent_merge')->where('merge_type', 'merge')->count(),
            'pending' => DB::table('agent_merge')->where('status', 'pending')->count(),
            'completed' => DB::table('agent_merge')->where('status', 'completed')->count(),
        ];
    }
}
