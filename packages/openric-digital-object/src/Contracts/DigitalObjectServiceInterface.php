<?php

declare(strict_types=1);

namespace OpenRiC\DigitalObject\Contracts;

/**
 * Contract for digital object (rico:Instantiation) management.
 *
 * Adapted from Heratio DamService (874 lines).
 * All entity data stored as RDF in Fuseki via TriplestoreServiceInterface.
 * File operations via Laravel Storage facade.
 */
interface DigitalObjectServiceInterface
{
    /**
     * Browse digital objects with filters, sorting, and pagination.
     *
     * @param  array{
     *     page?: int,
     *     limit?: int,
     *     sort?: string,
     *     sortDir?: string,
     *     subquery?: string,
     *     mimeType?: string,
     *     recordIri?: string
     * } $params
     * @return array{hits: array<int, array<string, mixed>>, total: int, page: int, limit: int}
     */
    public function browse(array $params): array;

    /**
     * Find a digital object by its IRI.
     *
     * @param  string $iri  The full IRI of the digital object
     * @return array<string, mixed>|null  Property map or null if not found
     */
    public function find(string $iri): ?array;

    /**
     * Create a new digital object (rico:Instantiation) in the triplestore.
     *
     * @param  array<string, mixed> $data  Property values for the digital object
     * @param  string               $userId  User IRI for provenance
     * @return string  The IRI of the newly created digital object
     */
    public function create(array $data, string $userId): string;

    /**
     * Update an existing digital object's properties.
     *
     * @param  string               $iri     The digital object IRI
     * @param  array<string, mixed> $data    Updated property values
     * @param  string               $userId  User IRI for provenance
     */
    public function update(string $iri, array $data, string $userId): void;

    /**
     * Delete a digital object and its associated file.
     *
     * @param  string $iri     The digital object IRI
     * @param  string $userId  User IRI for provenance
     */
    public function delete(string $iri, string $userId): void;

    /**
     * Get dashboard statistics for digital objects.
     *
     * @return array{
     *     totalObjects: int,
     *     withFiles: int,
     *     byMimeType: array<int, array{mimeType: string, count: int}>,
     *     totalSizeBytes: int
     * }
     */
    public function getDashboardStats(): array;

    /**
     * Get recently created digital objects.
     *
     * @param  int $limit  Maximum number of results
     * @return array<int, array<string, mixed>>
     */
    public function getRecentAssets(int $limit = 10): array;

    /**
     * Get all digital objects linked to a record IRI.
     *
     * @param  string $recordIri  The record IRI to find linked digital objects for
     * @return array<int, array<string, mixed>>
     */
    public function getDigitalObjectsForRecord(string $recordIri): array;

    /**
     * Upload a file and associate it with a digital object.
     *
     * @param  string                            $iri     The digital object IRI
     * @param  \Illuminate\Http\UploadedFile     $file    The uploaded file
     * @param  string                            $userId  User IRI for provenance
     * @return array{path: string, mimeType: string, sizeBytes: int, filename: string}
     */
    public function uploadFile(string $iri, \Illuminate\Http\UploadedFile $file, string $userId): array;

    /**
     * Delete the file associated with a digital object.
     *
     * @param  string $iri     The digital object IRI
     * @param  string $userId  User IRI for provenance
     */
    public function deleteFile(string $iri, string $userId): void;

    /**
     * Get file metadata for a digital object.
     *
     * @param  string $iri  The digital object IRI
     * @return array{path: string, mimeType: string, sizeBytes: int, filename: string}|null
     */
    public function getFileMetadata(string $iri): ?array;
}
