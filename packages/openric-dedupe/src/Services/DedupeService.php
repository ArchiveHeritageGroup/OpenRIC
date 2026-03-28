<?php

declare(strict_types=1);

namespace OpenRiC\Dedupe\Services;

use OpenRiC\Dedupe\Contracts\DedupeServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * Deduplication service — adapted from Heratio DedupeController (741 lines).
 *
 * Heratio uses MySQL tables (ahg_duplicate_detection, ahg_duplicate_rule,
 * ahg_dedupe_scan) with information_object_i18n joins for title comparison.
 * Detection methods include title_similarity, identifier_exact/fuzzy,
 * date_creator, checksum, combined, and custom rules.
 *
 * OpenRiC uses SPARQL queries against Fuseki for similarity detection on
 * rico:RecordSet and rico:Agent entities. Candidate pairs are stored in
 * PostgreSQL (duplicate_candidates) for tracking resolution status.
 * Merges operate on the triplestore, transferring relationships from
 * the duplicate to the canonical entity.
 */
class DedupeService implements DedupeServiceInterface
{
    private TriplestoreServiceInterface $triplestore;

    public function __construct(TriplestoreServiceInterface $triplestore)
    {
        $this->triplestore = $triplestore;
    }

    /**
     * Find duplicate records using SPARQL-based similarity matching.
     *
     * Adapted from Heratio DedupeController::apiRealtime() which uses
     * MySQL LIKE queries on information_object_i18n.title with PHP
     * similar_text() scoring. OpenRiC uses SPARQL string comparison
     * across title, identifier, and date fields.
     */
    public function findDuplicates(array $params = []): array
    {
        $threshold = (float) ($params['threshold'] ?? 0.7);
        $entityType = $params['entityType'] ?? 'RecordSet';
        $limit = max(1, min(500, (int) ($params['limit'] ?? 100)));

        $prefixes = $this->triplestore->getPrefixes();
        $ricoType = 'rico:' . $entityType;

        // SPARQL query to find records with identical or near-identical titles
        // Uses string normalization (LCASE + REPLACE for whitespace) for comparison
        $sparql = $prefixes . '
SELECT ?a ?b ?titleA ?titleB ?identA ?identB ?dateA ?dateB
WHERE {
  ?a a ' . $ricoType . ' .
  ?b a ' . $ricoType . ' .
  ?a rico:title ?titleA .
  ?b rico:title ?titleB .
  OPTIONAL { ?a rico:identifier ?identA . }
  OPTIONAL { ?b rico:identifier ?identB . }
  OPTIONAL { ?a rico:date ?dateA . }
  OPTIONAL { ?b rico:date ?dateB . }
  FILTER(STR(?a) < STR(?b))
  FILTER(
    LCASE(REPLACE(STR(?titleA), "\\\\s+", " ")) = LCASE(REPLACE(STR(?titleB), "\\\\s+", " "))
    || (STRLEN(STR(?titleA)) > 5 && CONTAINS(LCASE(STR(?titleA)), LCASE(STR(?titleB))))
    || (STRLEN(STR(?titleB)) > 5 && CONTAINS(LCASE(STR(?titleB)), LCASE(STR(?titleA))))
    || (BOUND(?identA) && BOUND(?identB) && ?identA = ?identB && STR(?identA) != "")
  )
}
LIMIT ' . $limit;

        $rows = $this->triplestore->select($sparql);

        $results = [];
        foreach ($rows as $row) {
            $titleA = (string) ($row['titleA'] ?? '');
            $titleB = (string) ($row['titleB'] ?? '');
            $identA = (string) ($row['identA'] ?? '');
            $identB = (string) ($row['identB'] ?? '');
            $dateA = (string) ($row['dateA'] ?? '');
            $dateB = (string) ($row['dateB'] ?? '');

            $score = $this->calculateSimilarityScore($titleA, $titleB, $identA, $identB, $dateA, $dateB);

            if ($score < $threshold) {
                continue;
            }

            $matchFields = [];
            if ($titleA !== '' && $titleB !== '') {
                similar_text(strtolower($titleA), strtolower($titleB), $titlePct);
                $matchFields['title'] = ['a' => $titleA, 'b' => $titleB, 'score' => round($titlePct / 100, 4)];
            }
            if ($identA !== '' && $identB !== '' && $identA === $identB) {
                $matchFields['identifier'] = ['a' => $identA, 'b' => $identB, 'score' => 1.0];
            }
            if ($dateA !== '' && $dateB !== '' && $dateA === $dateB) {
                $matchFields['date'] = ['a' => $dateA, 'b' => $dateB, 'score' => 1.0];
            }

            $results[] = [
                'entity_a_iri'     => $row['a'] ?? '',
                'entity_b_iri'     => $row['b'] ?? '',
                'similarity_score' => $score,
                'match_fields'     => $matchFields,
            ];

            // Store in database for tracking
            $this->storeCandidate(
                $row['a'] ?? '',
                $row['b'] ?? '',
                $entityType,
                $score,
                $matchFields
            );
        }

        // Sort by similarity score descending
        usort($results, fn (array $a, array $b) => $b['similarity_score'] <=> $a['similarity_score']);

        return $results;
    }

    /**
     * Find duplicate agents using SPARQL-based similarity matching.
     *
     * Adapted from Heratio DedupeController::dashboard() which queries
     * actor_i18n for authority record duplicates.
     */
    public function findDuplicateAgents(array $params = []): array
    {
        $threshold = (float) ($params['threshold'] ?? 0.7);
        $limit = max(1, min(500, (int) ($params['limit'] ?? 100)));

        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
SELECT ?a ?b ?nameA ?nameB ?identA ?identB ?datesA ?datesB
WHERE {
  ?a a rico:Agent .
  ?b a rico:Agent .
  ?a rico:hasOrHadAgentName ?nameObjA .
  ?nameObjA rico:textualValue ?nameA .
  ?b rico:hasOrHadAgentName ?nameObjB .
  ?nameObjB rico:textualValue ?nameB .
  OPTIONAL { ?a rico:identifier ?identA . }
  OPTIONAL { ?b rico:identifier ?identB . }
  OPTIONAL { ?a rico:hasOrHadDatesOfExistence ?datesA . }
  OPTIONAL { ?b rico:hasOrHadDatesOfExistence ?datesB . }
  FILTER(STR(?a) < STR(?b))
  FILTER(
    LCASE(REPLACE(STR(?nameA), "\\\\s+", " ")) = LCASE(REPLACE(STR(?nameB), "\\\\s+", " "))
    || (STRLEN(STR(?nameA)) > 5 && CONTAINS(LCASE(STR(?nameA)), LCASE(STR(?nameB))))
    || (STRLEN(STR(?nameB)) > 5 && CONTAINS(LCASE(STR(?nameB)), LCASE(STR(?nameA))))
    || (BOUND(?identA) && BOUND(?identB) && ?identA = ?identB && STR(?identA) != "")
  )
}
LIMIT ' . $limit;

        $rows = $this->triplestore->select($sparql);

        $results = [];
        foreach ($rows as $row) {
            $nameA = (string) ($row['nameA'] ?? '');
            $nameB = (string) ($row['nameB'] ?? '');
            $identA = (string) ($row['identA'] ?? '');
            $identB = (string) ($row['identB'] ?? '');
            $datesA = (string) ($row['datesA'] ?? '');
            $datesB = (string) ($row['datesB'] ?? '');

            $score = $this->calculateSimilarityScore($nameA, $nameB, $identA, $identB, $datesA, $datesB);

            if ($score < $threshold) {
                continue;
            }

            $matchFields = [];
            if ($nameA !== '' && $nameB !== '') {
                similar_text(strtolower($nameA), strtolower($nameB), $namePct);
                $matchFields['name'] = ['a' => $nameA, 'b' => $nameB, 'score' => round($namePct / 100, 4)];
            }
            if ($identA !== '' && $identB !== '' && $identA === $identB) {
                $matchFields['identifier'] = ['a' => $identA, 'b' => $identB, 'score' => 1.0];
            }
            if ($datesA !== '' && $datesB !== '' && $datesA === $datesB) {
                $matchFields['dates'] = ['a' => $datesA, 'b' => $datesB, 'score' => 1.0];
            }

            $results[] = [
                'entity_a_iri'     => $row['a'] ?? '',
                'entity_b_iri'     => $row['b'] ?? '',
                'similarity_score' => $score,
                'match_fields'     => $matchFields,
            ];

            $this->storeCandidate(
                $row['a'] ?? '',
                $row['b'] ?? '',
                'Agent',
                $score,
                $matchFields
            );
        }

        usort($results, fn (array $a, array $b) => $b['similarity_score'] <=> $a['similarity_score']);

        return $results;
    }

    /**
     * Compare a pair of entities side-by-side.
     *
     * Adapted from Heratio DedupeController::compare() which queries
     * information_object + i18n + actor_i18n + term_i18n for both records
     * and builds a field-by-field comparison array.
     */
    public function comparePair(string $entityAIri, string $entityBIri): array
    {
        $entityA = $this->triplestore->getEntity($entityAIri);
        $entityB = $this->triplestore->getEntity($entityBIri);

        if ($entityA === null) {
            $entityA = [];
        }
        if ($entityB === null) {
            $entityB = [];
        }

        // Determine fields to compare based on entity type
        $fields = [
            'rico:title'                 => 'Title',
            'rico:identifier'            => 'Identifier',
            'rico:date'                  => 'Date',
            'rico:hasRecordSetType'      => 'Level of Description',
            'rico:hasExtent'             => 'Extent',
            'rico:scopeAndContent'       => 'Scope and Content',
            'rico:hasProvenance'         => 'Creator/Provenance',
            'rico:hasOrHadLanguage'      => 'Language',
            'rico:conditionsOfAccess'    => 'Access Conditions',
            'rico:descriptiveNote'       => 'Notes',
        ];

        $comparison = [];
        $matchCount = 0;
        $totalFields = 0;

        foreach ($fields as $property => $label) {
            $valA = $this->extractStringValue($entityA, $property);
            $valB = $this->extractStringValue($entityB, $property);

            $isMatch = trim($valA) !== '' && trim($valA) === trim($valB);
            if ($isMatch) {
                $matchCount++;
            }
            if (trim($valA) !== '' || trim($valB) !== '') {
                $totalFields++;
            }

            $comparison[] = [
                'label' => $label,
                'a'     => $valA,
                'b'     => $valB,
                'match' => $isMatch,
            ];
        }

        $similarityScore = $totalFields > 0 ? round($matchCount / $totalFields, 4) : 0.0;

        return [
            'entityA'         => array_merge(['iri' => $entityAIri], $entityA),
            'entityB'         => array_merge(['iri' => $entityBIri], $entityB),
            'comparison'      => $comparison,
            'similarityScore' => $similarityScore,
        ];
    }

    /**
     * Merge two records, keeping one as canonical.
     *
     * Adapted from Heratio DedupeController::mergeExecute() which flags
     * records for merge (status='merged') but defers actual data transfer
     * to a background task. OpenRiC performs the merge immediately:
     * 1. Transfer all relationships from duplicate to canonical
     * 2. Add owl:sameAs link from duplicate to canonical
     * 3. Mark duplicate as deprecated
     */
    public function mergeRecords(string $canonicalIri, string $duplicateIri, string $userId): bool
    {
        // Get all relationships where the duplicate is subject or object
        $relationships = $this->triplestore->getRelationships($duplicateIri);

        foreach ($relationships as $rel) {
            $subject = $rel['subject'] ?? '';
            $predicate = $rel['predicate'] ?? '';
            $object = $rel['object'] ?? '';

            if ($predicate === '' || $predicate === 'rdf:type') {
                continue;
            }

            if ($subject === $duplicateIri) {
                // Duplicate is subject — create same relationship for canonical
                $this->triplestore->createRelationship(
                    $canonicalIri,
                    $predicate,
                    $object,
                    $userId,
                    'Transferred relationship from merged duplicate'
                );
            } elseif ($object === $duplicateIri) {
                // Duplicate is object — repoint to canonical
                $this->triplestore->deleteRelationship(
                    $subject,
                    $predicate,
                    $duplicateIri,
                    $userId,
                    'Removed relationship to merged duplicate'
                );
                $this->triplestore->createRelationship(
                    $subject,
                    $predicate,
                    $canonicalIri,
                    $userId,
                    'Repointed relationship to canonical record after merge'
                );
            }
        }

        // Add owl:sameAs from duplicate to canonical for provenance
        $this->triplestore->createRelationship(
            $duplicateIri,
            'owl:sameAs',
            $canonicalIri,
            $userId,
            'Merged: duplicate record now points to canonical'
        );

        // Mark the duplicate as deprecated
        $this->triplestore->updateEntity($duplicateIri, [
            'rico:isOrWasDescribedBy' => 'DEPRECATED: merged into ' . $canonicalIri,
            'rico:hasModificationDate' => now()->toIso8601String(),
        ], $userId, 'Marked as deprecated after merge into ' . $canonicalIri);

        return true;
    }

    /**
     * Get deduplication statistics.
     *
     * Adapted from Heratio DedupeController::index() which queries
     * ahg_duplicate_detection for counts by status.
     */
    public function getStats(): array
    {
        $totalDetected = DB::table('duplicate_candidates')->count();
        $pending = DB::table('duplicate_candidates')->where('status', 'pending')->count();
        $merged = DB::table('duplicate_candidates')->where('status', 'merged')->count();
        $notDuplicate = DB::table('duplicate_candidates')->where('status', 'not_duplicate')->count();

        return [
            'totalDetected' => $totalDetected,
            'pending'       => $pending,
            'merged'        => $merged,
            'notDuplicate'  => $notDuplicate,
        ];
    }

    /**
     * Get pending duplicate candidates with pagination.
     *
     * Adapted from Heratio DedupeController::browse() which queries
     * ahg_duplicate_detection with optional status, method, and score filters,
     * joining information_object_i18n for titles.
     */
    public function getPendingDuplicates(array $params = []): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;
        $status = $params['status'] ?? 'pending';
        $entityType = $params['entityType'] ?? '';
        $minScore = (float) ($params['minScore'] ?? 0);

        $query = DB::table('duplicate_candidates')
            ->leftJoin('users as resolver', 'duplicate_candidates.resolved_by', '=', 'resolver.id');

        if ($status !== '') {
            $query->where('duplicate_candidates.status', $status);
        }
        if ($entityType !== '') {
            $query->where('duplicate_candidates.entity_type', $entityType);
        }
        if ($minScore > 0) {
            $query->where('duplicate_candidates.similarity_score', '>=', $minScore);
        }

        $total = $query->count();

        $rows = $query
            ->select([
                'duplicate_candidates.id',
                'duplicate_candidates.entity_a_iri',
                'duplicate_candidates.entity_b_iri',
                'duplicate_candidates.entity_type',
                'duplicate_candidates.similarity_score',
                'duplicate_candidates.match_fields',
                'duplicate_candidates.status',
                'duplicate_candidates.resolved_at',
                'duplicate_candidates.created_at',
                'resolver.name as resolved_by_name',
            ])
            ->orderByDesc('duplicate_candidates.similarity_score')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $arr = (array) $row;
                // Decode JSON match_fields
                if (isset($arr['match_fields']) && is_string($arr['match_fields'])) {
                    $arr['match_fields'] = json_decode($arr['match_fields'], true) ?? [];
                }
                return $arr;
            })
            ->toArray();

        return [
            'hits'  => $rows,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Resolve a duplicate candidate.
     *
     * Adapted from Heratio DedupeController::dismiss() and mergeExecute()
     * which update ahg_duplicate_detection status and reviewed_by/reviewed_at.
     */
    public function resolveDuplicate(int $candidateId, string $resolution, int $userId): void
    {
        $validResolutions = ['not_duplicate', 'merged'];
        if (!in_array($resolution, $validResolutions, true)) {
            throw new \InvalidArgumentException(
                'Resolution must be one of: ' . implode(', ', $validResolutions)
            );
        }

        $candidate = DB::table('duplicate_candidates')->where('id', $candidateId)->first();
        if ($candidate === null) {
            throw new \RuntimeException('Duplicate candidate not found: ' . $candidateId);
        }

        // If merging, perform the actual merge
        if ($resolution === 'merged') {
            $this->mergeRecords(
                $candidate->entity_a_iri,
                $candidate->entity_b_iri,
                (string) $userId
            );
        }

        DB::table('duplicate_candidates')
            ->where('id', $candidateId)
            ->update([
                'status'      => $resolution,
                'resolved_by' => $userId,
                'resolved_at' => now(),
                'updated_at'  => now(),
            ]);
    }

    /**
     * Calculate composite similarity score between two entities.
     *
     * Adapted from Heratio's PHP similar_text() approach in apiRealtime().
     * Uses weighted scoring: title (60%), identifier (25%), dates (15%).
     */
    private function calculateSimilarityScore(
        string $titleA,
        string $titleB,
        string $identA,
        string $identB,
        string $dateA,
        string $dateB
    ): float {
        $titleScore = 0.0;
        $identScore = 0.0;
        $dateScore = 0.0;

        // Title similarity (60% weight)
        if ($titleA !== '' && $titleB !== '') {
            similar_text(strtolower(trim($titleA)), strtolower(trim($titleB)), $titlePct);
            $titleScore = $titlePct / 100;
        }

        // Identifier match (25% weight)
        if ($identA !== '' && $identB !== '') {
            if ($identA === $identB) {
                $identScore = 1.0;
            } else {
                similar_text(strtolower(trim($identA)), strtolower(trim($identB)), $identPct);
                $identScore = $identPct / 100;
            }
        }

        // Date match (15% weight)
        if ($dateA !== '' && $dateB !== '') {
            $dateScore = ($dateA === $dateB) ? 1.0 : 0.0;
        }

        // Weighted composite
        $weights = ['title' => 0.60, 'identifier' => 0.25, 'date' => 0.15];
        $totalWeight = 0.0;
        $weightedScore = 0.0;

        if ($titleA !== '' && $titleB !== '') {
            $weightedScore += $titleScore * $weights['title'];
            $totalWeight += $weights['title'];
        }
        if ($identA !== '' && $identB !== '') {
            $weightedScore += $identScore * $weights['identifier'];
            $totalWeight += $weights['identifier'];
        }
        if ($dateA !== '' && $dateB !== '') {
            $weightedScore += $dateScore * $weights['date'];
            $totalWeight += $weights['date'];
        }

        if ($totalWeight === 0.0) {
            return 0.0;
        }

        return round($weightedScore / $totalWeight, 4);
    }

    /**
     * Store a candidate pair in the database for tracking.
     */
    private function storeCandidate(
        string $entityAIri,
        string $entityBIri,
        string $entityType,
        float $score,
        array $matchFields
    ): void {
        // Normalize ordering so A < B
        if ($entityAIri > $entityBIri) {
            [$entityAIri, $entityBIri] = [$entityBIri, $entityAIri];
        }

        // Upsert — update score if already exists
        $existing = DB::table('duplicate_candidates')
            ->where('entity_a_iri', $entityAIri)
            ->where('entity_b_iri', $entityBIri)
            ->first();

        if ($existing !== null) {
            // Only update if score changed or status is pending
            if ($existing->status === 'pending') {
                DB::table('duplicate_candidates')
                    ->where('id', $existing->id)
                    ->update([
                        'similarity_score' => $score,
                        'match_fields'     => json_encode($matchFields),
                        'updated_at'       => now(),
                    ]);
            }
        } else {
            DB::table('duplicate_candidates')->insert([
                'entity_a_iri'     => $entityAIri,
                'entity_b_iri'     => $entityBIri,
                'entity_type'      => $entityType,
                'similarity_score' => $score,
                'match_fields'     => json_encode($matchFields),
                'status'           => 'pending',
                'resolved_by'      => null,
                'resolved_at'      => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }

    /**
     * Extract a string value from an entity property map.
     */
    private function extractStringValue(array $entity, string $property): string
    {
        $value = $entity[$property] ?? '';
        if (is_array($value)) {
            return implode('; ', array_map('strval', $value));
        }
        return (string) $value;
    }
}
