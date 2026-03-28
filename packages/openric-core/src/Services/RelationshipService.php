<?php

declare(strict_types=1);

namespace OpenRiC\Core\Services;

use OpenRiC\Core\Contracts\RelationshipServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class RelationshipService implements RelationshipServiceInterface
{
    public const PREDICATES = [
        'rico:hasOrHadCreator' => 'Has/had creator',
        'rico:hasOrHadSubject' => 'Has/had subject',
        'rico:hasOrHadInstantiation' => 'Has/had instantiation',
        'rico:describesOrDescribed' => 'Describes/described',
        'rico:isOrWasRelatedTo' => 'Is/was related to',
        'rico:hasOrHadPart' => 'Has/had part',
        'rico:isOrWasIncludedIn' => 'Is/was included in',
        'rico:hasOrHadHolder' => 'Has/had holder',
        'rico:isAssociatedWithDate' => 'Is associated with date',
        'rico:isAssociatedWithPlace' => 'Is associated with place',
        'rico:isOrWasRegulatedBy' => 'Is/was regulated by',
        'rico:performsOrPerformed' => 'Performs/performed',
        'rico:resultsOrResultedIn' => 'Results/resulted in',
        'rico:hasOrHadParticipant' => 'Has/had participant',
        'rico:isAgentAssociatedWithAgent' => 'Agent associated with agent',
        'rico:hasOrHadLocation' => 'Has/had location',
        'owl:sameAs' => 'Same as (external authority)',
    ];

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function getRelationships(string $iri, int $limit = 100): array
    {
        return $this->triplestore->getRelationships($iri, $limit);
    }

    public function createRelationship(string $subjectIri, string $predicate, string $objectIri, string $userId, string $reason): bool
    {
        return $this->triplestore->createRelationship($subjectIri, $predicate, $objectIri, $userId, $reason);
    }

    public function deleteRelationship(string $subjectIri, string $predicate, string $objectIri, string $userId, string $reason): bool
    {
        return $this->triplestore->deleteRelationship($subjectIri, $predicate, $objectIri, $userId, $reason);
    }

    public function getAvailablePredicates(): array
    {
        return self::PREDICATES;
    }
}
