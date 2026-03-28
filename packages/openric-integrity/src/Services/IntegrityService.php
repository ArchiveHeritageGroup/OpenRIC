<?php

declare(strict_types=1);

namespace OpenRiC\Integrity\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use OpenRiC\Integrity\Contracts\IntegrityServiceInterface;

/**
 * Integrity service -- adapted from Heratio AhgIntegrity\Controllers\IntegrityController (173 lines).
 *
 * Runs SPARQL queries against the Fuseki triplestore to find:
 * - Entities without titles
 * - Orphan relationships (broken IRI references)
 * - Duplicate IRIs
 * - Entities without rdf:type
 * Results are stored in-memory and cached for display.
 */
class IntegrityService implements IntegrityServiceInterface
{
    /**
     * In-memory store for the most recent check results, keyed by run_id.
     *
     * @var array<string, array>
     */
    private static array $resultStore = [];

    private function getFusekiUrl(): string
    {
        return rtrim(config('triplestore.fuseki_url', 'http://localhost:3030'), '/');
    }

    private function getDataset(): string
    {
        return config('triplestore.dataset', 'openric');
    }

    /**
     * Execute a SPARQL query against Fuseki and return parsed results.
     */
    private function sparqlQuery(string $sparql): array
    {
        $url = $this->getFusekiUrl() . '/' . $this->getDataset() . '/sparql';

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Accept' => 'application/sparql-results+json'])
                ->get($url, ['query' => $sparql]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['results']['bindings'] ?? [];
            }

            return [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function runChecks(): array
    {
        $runId     = Str::uuid()->toString();
        $startedAt = now()->toIso8601String();
        $checks    = [];

        // Check 1: Entities without titles (rico:title)
        $checks['missing_titles'] = $this->checkMissingTitles();

        // Check 2: Orphan relationships (broken IRI references)
        $checks['orphan_relationships'] = $this->checkOrphanRelationships();

        // Check 3: Duplicate IRIs
        $checks['duplicate_iris'] = $this->checkDuplicateIris();

        // Check 4: Entities without rdf:type
        $checks['missing_type'] = $this->checkMissingType();

        // Check 5: Index consistency (triplestore vs PostgreSQL)
        $checks['index_consistency'] = $this->checkIndexConsistency();

        $completedAt = now()->toIso8601String();

        $result = [
            'run_id'       => $runId,
            'started_at'   => $startedAt,
            'completed_at' => $completedAt,
            'checks'       => $checks,
        ];

        self::$resultStore[$runId] = $result;

        // Keep only the last 50 runs in memory
        if (count(self::$resultStore) > 50) {
            self::$resultStore = array_slice(self::$resultStore, -50, 50, true);
        }

        return $result;
    }

    public function getResults(?string $runId = null): ?array
    {
        if ($runId !== null) {
            return self::$resultStore[$runId] ?? null;
        }

        if (empty(self::$resultStore)) {
            return null;
        }

        return end(self::$resultStore) ?: null;
    }

    public function getStats(): array
    {
        $totalRuns  = count(self::$resultStore);
        $lastRun    = null;
        $passCount  = 0;
        $totalIssues = 0;

        foreach (self::$resultStore as $result) {
            $lastRun = $result['completed_at'] ?? $result['started_at'];

            foreach ($result['checks'] as $check) {
                if ($check['passed']) {
                    $passCount++;
                }
                $totalIssues += $check['count'];
            }
        }

        $totalChecks = $totalRuns * 5; // 5 checks per run

        return [
            'total_runs'  => $totalRuns,
            'last_run'    => $lastRun,
            'pass_rate'   => $totalChecks > 0 ? round(($passCount / $totalChecks) * 100, 1) : 0.0,
            'open_issues' => $totalIssues,
        ];
    }

    /**
     * Check for entities that have no rico:title or rdfs:label.
     */
    private function checkMissingTitles(): array
    {
        $sparql = <<<'SPARQL'
PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?entity ?type WHERE {
    ?entity rdf:type ?type .
    FILTER(STRSTARTS(STR(?type), "https://www.ica.org/standards/RiC/ontology#"))
    FILTER NOT EXISTS { ?entity rico:title ?title }
    FILTER NOT EXISTS { ?entity rdfs:label ?label }
}
LIMIT 100
SPARQL;

        $bindings = $this->sparqlQuery($sparql);
        $details  = [];

        foreach ($bindings as $row) {
            $details[] = [
                'entity' => $row['entity']['value'] ?? '',
                'type'   => $row['type']['value'] ?? '',
                'issue'  => 'Entity has no rico:title or rdfs:label',
            ];
        }

        return [
            'passed'  => empty($details),
            'count'   => count($details),
            'details' => $details,
            'label'   => 'Entities without titles',
        ];
    }

    /**
     * Check for relationships pointing to non-existent entities.
     */
    private function checkOrphanRelationships(): array
    {
        $sparql = <<<'SPARQL'
PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?subject ?predicate ?object WHERE {
    ?subject ?predicate ?object .
    FILTER(STRSTARTS(STR(?predicate), "https://www.ica.org/standards/RiC/ontology#"))
    FILTER(isIRI(?object))
    FILTER NOT EXISTS { ?object ?p ?o }
}
LIMIT 100
SPARQL;

        $bindings = $this->sparqlQuery($sparql);
        $details  = [];

        foreach ($bindings as $row) {
            $details[] = [
                'subject'   => $row['subject']['value'] ?? '',
                'predicate' => $row['predicate']['value'] ?? '',
                'object'    => $row['object']['value'] ?? '',
                'issue'     => 'Relationship target does not exist',
            ];
        }

        return [
            'passed'  => empty($details),
            'count'   => count($details),
            'details' => $details,
            'label'   => 'Orphan relationships',
        ];
    }

    /**
     * Check for duplicate IRIs (same IRI with multiple rdf:type declarations of the same type).
     */
    private function checkDuplicateIris(): array
    {
        $sparql = <<<'SPARQL'
PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?entity (COUNT(?type) AS ?typeCount) WHERE {
    ?entity rdf:type ?type .
    FILTER(STRSTARTS(STR(?type), "https://www.ica.org/standards/RiC/ontology#"))
}
GROUP BY ?entity
HAVING (COUNT(?type) > 1)
LIMIT 100
SPARQL;

        $bindings = $this->sparqlQuery($sparql);
        $details  = [];

        foreach ($bindings as $row) {
            $details[] = [
                'entity'     => $row['entity']['value'] ?? '',
                'type_count' => (int) ($row['typeCount']['value'] ?? 0),
                'issue'      => 'Entity has multiple RiC-O types',
            ];
        }

        return [
            'passed'  => empty($details),
            'count'   => count($details),
            'details' => $details,
            'label'   => 'Duplicate/multiple type IRIs',
        ];
    }

    /**
     * Check for subjects that have triples but no rdf:type.
     */
    private function checkMissingType(): array
    {
        $sparql = <<<'SPARQL'
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

SELECT DISTINCT ?entity WHERE {
    ?entity ?p ?o .
    FILTER(isIRI(?entity))
    FILTER(STRSTARTS(STR(?entity), "https://"))
    FILTER NOT EXISTS { ?entity rdf:type ?type }
}
LIMIT 100
SPARQL;

        $bindings = $this->sparqlQuery($sparql);
        $details  = [];

        foreach ($bindings as $row) {
            $details[] = [
                'entity' => $row['entity']['value'] ?? '',
                'issue'  => 'Entity has no rdf:type declaration',
            ];
        }

        return [
            'passed'  => empty($details),
            'count'   => count($details),
            'details' => $details,
            'label'   => 'Entities without rdf:type',
        ];
    }

    /**
     * Check consistency between triplestore entity count and PostgreSQL index tables.
     */
    private function checkIndexConsistency(): array
    {
        $details = [];

        // Count entities in triplestore
        $sparql = <<<'SPARQL'
PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

SELECT ?type (COUNT(?entity) AS ?cnt) WHERE {
    ?entity rdf:type ?type .
    FILTER(STRSTARTS(STR(?type), "https://www.ica.org/standards/RiC/ontology#"))
}
GROUP BY ?type
SPARQL;

        $bindings = $this->sparqlQuery($sparql);

        $typeMapping = [
            'https://www.ica.org/standards/RiC/ontology#RecordResource' => 'record_resources',
            'https://www.ica.org/standards/RiC/ontology#Agent'          => 'agents',
            'https://www.ica.org/standards/RiC/ontology#Place'          => 'places',
            'https://www.ica.org/standards/RiC/ontology#Instantiation'  => 'instantiations',
        ];

        foreach ($bindings as $row) {
            $typeIri        = $row['type']['value'] ?? '';
            $triplestoreCnt = (int) ($row['cnt']['value'] ?? 0);

            if (isset($typeMapping[$typeIri])) {
                $table = $typeMapping[$typeIri];
                if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                    $pgCount = DB::table($table)->count();
                    $diff    = abs($triplestoreCnt - $pgCount);

                    if ($diff > 0) {
                        $shortType = str_replace('https://www.ica.org/standards/RiC/ontology#', 'rico:', $typeIri);
                        $details[] = [
                            'type'            => $shortType,
                            'triplestore_count' => $triplestoreCnt,
                            'postgres_count'    => $pgCount,
                            'difference'        => $diff,
                            'issue'             => "Mismatch: {$triplestoreCnt} in triplestore vs {$pgCount} in PostgreSQL",
                        ];
                    }
                }
            }
        }

        return [
            'passed'  => empty($details),
            'count'   => count($details),
            'details' => $details,
            'label'   => 'Triplestore/PostgreSQL index consistency',
        ];
    }
}
