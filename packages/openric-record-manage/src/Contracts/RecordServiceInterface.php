<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Contracts;

/**
 * Record service interface — adapted from Heratio InformationObjectService + BrowseService.
 *
 * All 31 ISAD(G) fields mapped to RiC-O properties.
 * Supports: browse with facets, CRUD, hierarchy, autocomplete.
 */
interface RecordServiceInterface
{
    /** ISAD(G) → RiC-O field map constant */
    public const FIELD_MAP = \OpenRiC\RecordManage\Services\RecordService::FIELD_MAP;

    /**
     * Browse records with filtering, sorting, pagination, and facets.
     *
     * @return array{items: array, total: int, facets: array}
     */
    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array;

    /** Find a single record by IRI with all properties, children, and ancestors. */
    public function find(string $iri): ?array;

    /** Create a record with all ISAD(G) fields mapped to RiC-O. Returns the new IRI. */
    public function create(array $data, string $userId, string $reason): string;

    /** Update a record. Only provided fields are changed. */
    public function update(string $iri, array $data, string $userId, string $reason): bool;

    /** Delete a record and cascade to children. */
    public function delete(string $iri, string $userId, string $reason): bool;

    /** Get direct children of a record. */
    public function getChildren(string $parentIri, int $limit = 100): array;

    /** Get ancestor chain (breadcrumb) for a record. */
    public function getAncestors(string $iri, int $maxDepth = 20): array;

    /** Get child count for a record. */
    public function getChildCount(string $iri): int;

    /** Autocomplete search by title or identifier. */
    public function autocomplete(string $query, int $limit = 10): array;
}
