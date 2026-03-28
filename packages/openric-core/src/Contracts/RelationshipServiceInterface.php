<?php

declare(strict_types=1);

namespace OpenRiC\Core\Contracts;

interface RelationshipServiceInterface
{
    public function getRelationships(string $iri, int $limit = 100): array;

    public function createRelationship(string $subjectIri, string $predicate, string $objectIri, string $userId, string $reason): bool;

    public function deleteRelationship(string $subjectIri, string $predicate, string $objectIri, string $userId, string $reason): bool;

    public function getAvailablePredicates(): array;
}
