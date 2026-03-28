<?php

declare(strict_types=1);

namespace OpenRiC\Export\Contracts;

/**
 * Interface for exporting archival entities in standard interchange formats.
 *
 * All methods query the triplestore via TriplestoreServiceInterface — no raw
 * Fuseki calls. Every export serialisation is derived from RiC-O 1.1 triples.
 */
interface ExportServiceInterface
{
    /**
     * Export an entity as JSON-LD with @context and @graph.
     *
     * @param  string $iri  the entity IRI
     * @return array<string, mixed>  JSON-LD document as associative array
     */
    public function exportJsonLd(string $iri): array;

    /**
     * Export an entity in Turtle/N3 format.
     *
     * @param  string $iri  the entity IRI
     * @return string  Turtle serialisation
     */
    public function exportTurtle(string $iri): string;

    /**
     * Export an entity in RDF/XML format.
     *
     * @param  string $iri  the entity IRI
     * @return string  RDF/XML document
     */
    public function exportRdfXml(string $iri): string;

    /**
     * Export a RecordResource hierarchy as EAD3 XML.
     *
     * Walks the rico:hasOrHadPart tree recursively to build nested <c> elements.
     *
     * @param  string $iri  the top-level entity IRI
     * @return string  EAD3 XML document
     */
    public function exportEad3(string $iri): string;

    /**
     * Export an Agent entity as EAC-CPF 2.0 XML.
     *
     * @param  string $iri  the agent IRI
     * @return string  EAC-CPF XML document
     */
    public function exportEacCpf(string $iri): string;

    /**
     * Export an entity as Dublin Core XML (oai_dc).
     *
     * @param  string $iri  the entity IRI
     * @return string  Dublin Core XML document
     */
    public function exportDublinCore(string $iri): string;

    /**
     * Bulk export multiple entities in a chosen format.
     *
     * @param  array<int, string> $iris    list of entity IRIs
     * @param  string             $format  one of the keys from getAvailableFormats()
     * @return string  concatenated serialisation
     */
    public function bulkExport(array $iris, string $format): string;

    /**
     * List available export formats and their MIME types.
     *
     * @return array<string, array{label: string, mimeType: string, extension: string}>
     */
    public function getAvailableFormats(): array;
}
