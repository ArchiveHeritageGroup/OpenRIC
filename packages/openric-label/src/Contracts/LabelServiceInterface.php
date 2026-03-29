<?php

declare(strict_types=1);

namespace OpenRiC\Label\Contracts;

/**
 * Label service interface — adapted from Heratio ahg-label LabelController (372 lines).
 *
 * Provides methods for resolving entity data for label generation, extracting
 * barcode sources, and preparing batch label datasets. In OpenRiC, entities live
 * in the Fuseki triplestore (accessed via TriplestoreServiceInterface), with
 * operational data (accessions, donors) in PostgreSQL.
 */
interface LabelServiceInterface
{
    /**
     * Resolve an entity by IRI and return all label-relevant data.
     *
     * Returns null if the entity cannot be found. Otherwise returns:
     *   - iri: string
     *   - title: string
     *   - identifier: string
     *   - entity_type: string (RecordResource, Agent, Accession, etc.)
     *   - sector: string (archive, library, museum, gallery)
     *   - sector_label: string (human-readable sector)
     *   - barcode_sources: array<string, array{label: string, value: string}>
     *   - default_barcode_data: string
     *   - repository_name: string
     *   - qr_url: string
     *
     * @param  string $iri  Entity IRI
     * @return array<string, mixed>|null
     */
    public function resolveEntity(string $iri): ?array;

    /**
     * Extract barcode sources from entity properties.
     *
     * Inspects identifier, ISBN, ISSN, LCCN, barcode, call number,
     * accession number, and title to build an ordered list of barcode sources.
     *
     * @param  array<string, mixed> $properties  Entity properties from triplestore
     * @param  string               $entityType  Entity type
     * @param  string               $title       Entity title
     * @param  string               $identifier  Entity identifier
     * @return array<string, array{label: string, value: string}>
     */
    public function extractBarcodeSources(array $properties, string $entityType, string $title, string $identifier): array;

    /**
     * Determine the preferred barcode data from barcode sources.
     *
     * Priority: isbn > issn > barcode > accession > identifier > title
     *
     * @param  array<string, array{label: string, value: string}> $barcodeSources
     * @return string
     */
    public function selectDefaultBarcodeData(array $barcodeSources): string;

    /**
     * Resolve the repository/holding institution name for a record entity.
     *
     * Walks up the hierarchy if the record itself has no direct repository link.
     *
     * @param  array<string, mixed> $properties  Entity properties
     * @param  string               $entityType  Entity type
     * @return string
     */
    public function resolveRepositoryName(array $properties, string $entityType): string;

    /**
     * Prepare label data for a batch of entities.
     *
     * @param  array<int, string> $iris           Array of entity IRIs
     * @param  string|null        $barcodeSource  Override barcode source key (null = auto-detect)
     * @return array<int, array<string, mixed>>  Array of label data arrays
     */
    public function prepareBatchLabels(array $iris, ?string $barcodeSource = null): array;

    /**
     * Detect the sector (archive, library, museum, gallery) for an entity.
     *
     * @param  array<string, mixed> $properties  Entity properties
     * @return string  Sector identifier
     */
    public function detectSector(array $properties): string;
}
