<?php

declare(strict_types=1);

namespace OpenRiC\Provenance\Contracts;

interface DescriptionHistoryServiceInterface
{
    /**
     * Get combined history from RDF-Star annotations + PostgreSQL audit_log.
     * @return array<int, array{source: string, timestamp: string, user: string, action: string, details: array}>
     */
    public function getHistory(string $entityIri, int $limit = 50): array;

    /**
     * Get RDF-Star provenance annotations for an entity's triples.
     */
    public function getRdfStarProvenance(string $entityIri, int $limit = 50): array;

    /**
     * Get PostgreSQL audit log entries for an entity.
     */
    public function getAuditHistory(string $entityId, int $limit = 50): array;

    /**
     * Get description records (rico:Record with rico:describesOrDescribed) for an entity.
     */
    public function getDescriptionRecords(string $entityIri): array;
}
