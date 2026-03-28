<?php

declare(strict_types=1);

namespace OpenRiC\Core\Contracts;

/**
 * Interface for OAI-PMH data retrieval operations.
 *
 * All methods query the triplestore via TriplestoreServiceInterface
 * and return structured data for XML rendering by the controller.
 */
interface OaiPmhServiceInterface
{
    /**
     * Get the earliest datestamp across all Record Resources in the triplestore.
     *
     * @return string ISO 8601 UTC datestamp (YYYY-MM-DDThh:mm:ssZ)
     */
    public function getEarliestDatestamp(): string;

    /**
     * Retrieve top-level Record Sets (collections/fonds) for the ListSets verb.
     *
     * @param  int $offset  zero-based cursor offset
     * @param  int $limit   maximum number of sets to return
     * @return array{items: array<int, array{iri: string, title: string}>, total: int}
     */
    public function getSets(int $offset = 0, int $limit = 100): array;

    /**
     * Retrieve record headers (identifier + datestamp) for the ListIdentifiers verb.
     *
     * @param  string|null $from           ISO 8601 from date filter
     * @param  string|null $until          ISO 8601 until date filter
     * @param  string|null $setIri         IRI of the set to filter by
     * @param  int         $offset         zero-based cursor offset
     * @param  int         $limit          maximum number of records to return
     * @return array{items: array<int, array{iri: string, datestamp: string, setIri: string|null}>, total: int}
     */
    public function getRecordHeaders(
        ?string $from = null,
        ?string $until = null,
        ?string $setIri = null,
        int $offset = 0,
        int $limit = 100,
    ): array;

    /**
     * Retrieve full records with Dublin Core metadata for the ListRecords verb.
     *
     * @param  string|null $from           ISO 8601 from date filter
     * @param  string|null $until          ISO 8601 until date filter
     * @param  string|null $setIri         IRI of the set to filter by
     * @param  int         $offset         zero-based cursor offset
     * @param  int         $limit          maximum number of records to return
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function getRecords(
        ?string $from = null,
        ?string $until = null,
        ?string $setIri = null,
        int $offset = 0,
        int $limit = 100,
    ): array;

    /**
     * Retrieve a single record by its IRI for the GetRecord verb.
     *
     * @param  string $iri  the record IRI
     * @return array<string, mixed>|null  record data or null if not found
     */
    public function getRecord(string $iri): ?array;

    /**
     * Map a RiC-O record's properties to Dublin Core elements.
     *
     * @param  array<string, mixed> $record  raw triplestore record data
     * @return array<string, array<int, string>>  DC element name => array of values
     */
    public function mapToDublinCore(array $record): array;

    /**
     * Resolve the parent set IRI for a given record IRI.
     *
     * @param  string $recordIri  the record IRI
     * @return string|null  the top-level set IRI or null
     */
    public function getSetForRecord(string $recordIri): ?string;
}
