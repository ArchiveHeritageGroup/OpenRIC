<?php

declare(strict_types=1);

namespace OpenRiC\Export\Contracts;

/**
 * Interface for generating IIIF Presentation API 3.0 resources from RiC-O entities.
 *
 * All SPARQL queries go through TriplestoreServiceInterface.
 */
interface IiifServiceInterface
{
    /**
     * Generate a IIIF Presentation API 3.0 manifest for an Instantiation.
     *
     * @param  string $instantiationIri  the rico:Instantiation IRI
     * @return array<string, mixed>  IIIF manifest as associative array
     */
    public function generateManifest(string $instantiationIri): array;

    /**
     * Generate a IIIF Presentation API 3.0 Collection for a RecordSet.
     *
     * @param  string $recordSetIri  the rico:RecordSet IRI
     * @return array<string, mixed>  IIIF collection as associative array
     */
    public function generateCollection(string $recordSetIri): array;
}
