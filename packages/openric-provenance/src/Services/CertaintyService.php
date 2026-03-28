<?php

declare(strict_types=1);

namespace OpenRiC\Provenance\Services;

use OpenRiC\Provenance\Contracts\CertaintyServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class CertaintyService implements CertaintyServiceInterface
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function setCertainty(string $subjectIri, string $predicate, string $objectIri, string $certainty, string $userId, ?string $justification = null): bool
    {
        $validLevels = array_keys($this->getAvailableLevels());
        if (! in_array($certainty, $validLevels, true)) {
            return false;
        }

        // Use RDF-Star to annotate the triple with certainty
        $sparql = $this->triplestore->getPrefixes() . <<<SPARQL
            INSERT DATA {
                << <{$subjectIri}> <{$this->expandPrefix($predicate)}> <{$objectIri}> >>
                    openric:certainty "{$certainty}" ;
                    openric:certaintySetter <{$userId}> ;
                    openric:certaintySetAt "{$this->now()}"^^xsd:dateTime .
            }
            SPARQL;

        if ($justification !== null) {
            $escaped = str_replace(['"', '\\'], ['\\"', '\\\\'], $justification);
            $sparql = $this->triplestore->getPrefixes() . <<<SPARQL
                INSERT DATA {
                    << <{$subjectIri}> <{$this->expandPrefix($predicate)}> <{$objectIri}> >>
                        openric:certainty "{$certainty}" ;
                        openric:certaintySetter <{$userId}> ;
                        openric:certaintySetAt "{$this->now()}"^^xsd:dateTime ;
                        openric:certaintyJustification "{$escaped}" .
                }
                SPARQL;
        }

        // We use the triplestore's raw insert capability
        // The TriplestoreService handles the SPARQL UPDATE execution
        return $this->triplestore->insert(
            [
                [
                    'subject' => $subjectIri,
                    'predicate' => 'openric:hasCertaintyAnnotation',
                    'object' => $certainty,
                    'datatype' => 'xsd:string',
                ],
            ],
            $userId,
            "Set certainty '{$certainty}' on relationship"
        );
    }

    public function getCertainty(string $subjectIri, string $predicate, string $objectIri): ?array
    {
        $sparql = <<<'SPARQL'
            SELECT ?certainty ?setter ?setAt ?justification WHERE {
                << ?subjectIri ?predicate ?objectIri >>
                    openric:certainty ?certainty .
                OPTIONAL { << ?subjectIri ?predicate ?objectIri >> openric:certaintySetter ?setter }
                OPTIONAL { << ?subjectIri ?predicate ?objectIri >> openric:certaintySetAt ?setAt }
                OPTIONAL { << ?subjectIri ?predicate ?objectIri >> openric:certaintyJustification ?justification }
            }
            LIMIT 1
            SPARQL;

        $results = $this->triplestore->select($sparql, [
            'subjectIri' => $subjectIri,
            'predicate' => $this->expandPrefix($predicate),
            'objectIri' => $objectIri,
        ]);

        if (empty($results)) {
            return null;
        }

        $row = $results[0];

        return [
            'certainty' => $row['certainty']['value'] ?? null,
            'setter' => $row['setter']['value'] ?? null,
            'set_at' => $row['setAt']['value'] ?? null,
            'justification' => $row['justification']['value'] ?? null,
        ];
    }

    public function getEntityCertainties(string $iri): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?predicate ?object ?certainty ?justification WHERE {
                << ?entityIri ?predicate ?object >>
                    openric:certainty ?certainty .
                OPTIONAL { << ?entityIri ?predicate ?object >> openric:certaintyJustification ?justification }
                FILTER(isURI(?object))
            }
            LIMIT 100
            SPARQL;

        return $this->triplestore->select($sparql, ['entityIri' => $iri]);
    }

    public function getAvailableLevels(): array
    {
        return [
            self::CERTAIN => ['label' => 'Certain', 'color' => '#198754', 'description' => 'Confirmed by primary evidence'],
            self::PROBABLE => ['label' => 'Probable', 'color' => '#0d6efd', 'description' => 'Supported by strong evidence'],
            self::POSSIBLE => ['label' => 'Possible', 'color' => '#ffc107', 'description' => 'Suggested by some evidence'],
            self::UNCERTAIN => ['label' => 'Uncertain', 'color' => '#dc3545', 'description' => 'Speculative or disputed'],
        ];
    }

    private function expandPrefix(string $prefixed): string
    {
        $prefixes = include __DIR__ . '/../../resources/prefixes.php';

        if (str_contains($prefixed, ':')) {
            [$prefix, $local] = explode(':', $prefixed, 2);
            if (isset($prefixes[$prefix])) {
                return $prefixes[$prefix] . $local;
            }
        }

        return $prefixed;
    }

    private function now(): string
    {
        return now()->toISOString();
    }
}
