<?php

declare(strict_types=1);

namespace OpenRiC\Provenance\Contracts;

interface CertaintyServiceInterface
{
    public const CERTAIN = 'certain';
    public const PROBABLE = 'probable';
    public const POSSIBLE = 'possible';
    public const UNCERTAIN = 'uncertain';

    /**
     * Annotate a relationship with a certainty level using RDF-Star.
     */
    public function setCertainty(string $subjectIri, string $predicate, string $objectIri, string $certainty, string $userId, ?string $justification = null): bool;

    /**
     * Get the certainty annotation for a specific triple.
     */
    public function getCertainty(string $subjectIri, string $predicate, string $objectIri): ?array;

    /**
     * Get all certainty annotations for an entity's relationships.
     */
    public function getEntityCertainties(string $iri): array;

    public function getAvailableLevels(): array;
}
