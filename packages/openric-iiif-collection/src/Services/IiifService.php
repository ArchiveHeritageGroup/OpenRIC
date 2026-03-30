<?php

declare(strict_types=1);

namespace OpenRiC\IiifCollection\Services;

use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * IIIF Presentation API 3.0 Service for OpenRiC.
 * 
 * Generates IIIF Collections, Manifests, and Canvases from RiC-O instantiations.
 * Follows IIIF Presentation API 3.0 specification.
 * 
 * @see https://iiif.io/api/presentation/3.0/
 */
class IiifService
{
    private string $baseUri;
    private string $baseUrl;

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore
    ) {
        $this->baseUri = rtrim((string) config('openric.base_uri', 'https://ric.theahg.co.za/entity'), '/');
        $this->baseUrl = rtrim((string) config('app.url', 'https://ric.theahg.co.za'), '/');
    }

    /**
     * Generate a IIIF Collection for all top-level RecordSets.
     */
    public function getCollection(): array
    {
        $sparql = "
            SELECT ?iri ?title ?description WHERE {
                ?iri a rico:RecordSet .
                OPTIONAL { ?iri rico:title ?title }
                OPTIONAL { ?iri rico:generalDescription ?description }
            }
            ORDER BY ?title
        ";

        $results = $this->triplestore->select($sparql);
        
        $items = [];
        foreach ($results as $row) {
            $iri = $row['iri']['value'] ?? '';
            $manifestId = $this->buildManifestId($iri);
            
            $items[] = [
                'id' => $manifestId,
                'type' => 'Manifest',
                'label' => $this->buildLabel($row['title'] ?? null),
                'summary' => $this->buildLabel($row['description'] ?? null),
            ];
        }

        return [
            '@context' => [
                'http://iiif.io/api/presentation/3/context.json',
                'http://rucap.icarusrv.org/ns/iiif-context.json',
            ],
            'id' => $this->baseUrl . '/iiif/collection',
            'type' => 'Collection',
            'label' => [
                'en' => ['OpenRiC Archives Collection'],
            ],
            'summary' => [
                'en' => ['Records in Contexts — Archival descriptions rendered as IIIF resources'],
            ],
            'items' => $items,
        ];
    }

    /**
     * Generate a IIIF Manifest for a specific Record or RecordSet.
     */
    public function getManifest(string $iri): ?array
    {
        // Query the entity from triplestore
        $entity = $this->triplestore->getEntity($iri);
        
        if ($entity === null) {
            return null;
        }

        $properties = $entity['properties'] ?? [];
        
        // Get title
        $title = $this->extractFirstValue($properties, 'rico:title') 
            ?? $this->extractFirstValue($properties, 'rico:name')
            ?? 'Untitled';
        
        // Get description
        $description = $this->extractFirstValue($properties, 'rico:scopeAndContent')
            ?? $this->extractFirstValue($properties, 'rico:generalDescription')
            ?? '';

        // Get type
        $type = $this->extractFirstValue($properties, 'rdf:type') ?? 'rico:Record';

        // Build manifest
        $manifest = [
            '@context' => [
                'http://iiif.io/api/presentation/3/context.json',
            ],
            'id' => $this->buildManifestId($iri),
            'type' => 'Manifest',
            'label' => $this->buildLabel($title),
            'summary' => $this->buildLabel($description),
            'metadata' => $this->buildMetadata($properties),
            'items' => [],
        ];

        // Get child instantiations (pages/canvases)
        $instantiations = $this->getInstantiations($iri);
        foreach ($instantiations as $index => $instIri) {
            $manifest['items'][] = $this->buildCanvas($instIri, $index, $properties);
        }

        // If no instantiations, create a placeholder canvas
        if (empty($manifest['items'])) {
            $manifest['items'][] = $this->buildPlaceholderCanvas($iri, $properties);
        }

        return $manifest;
    }

    /**
     * Generate a IIIF Canvas for an Instantiation.
     */
    public function getCanvas(string $iri): ?array
    {
        $entity = $this->triplestore->getEntity($iri);
        
        if ($entity === null) {
            return null;
        }

        $properties = $entity['properties'] ?? [];
        
        return $this->buildCanvas($iri, 0, $properties);
    }

    /**
     * Build IIIF Annotation Page for an image on a canvas.
     */
    public function buildAnnotationPage(string $canvasId, string $imageUrl, array $imageService = [], int $width = 1000, int $height = 1000): array
    {
        $annotation = [
            'id' => $this->baseUrl . '/iiif/annotation/' . md5($imageUrl),
            'type' => 'Annotation',
            'motivation' => 'painting',
            'target' => $canvasId,
            'body' => [
                'id' => $imageUrl,
                'type' => 'Image',
                'format' => 'image/jpeg',
                'width' => $width,
                'height' => $height,
            ],
        ];

        if (!empty($imageService)) {
            $annotation['body']['service'] = [$imageService];
        }

        return [
            'id' => $this->baseUrl . '/iiif/annotation-page/' . md5($imageUrl),
            'type' => 'AnnotationPage',
            'items' => [$annotation],
        ];
    }

    /**
     * Get all instantiations for a record.
     */
    private function getInstantiations(string $iri): array
    {
        $sparql = "
            SELECT ?inst WHERE {
                <{$iri}> rico:hasInstantiation ?inst .
            }
            ORDER BY ?inst
        ";

        $results = $this->triplestore->select($sparql, ['iri' => $iri]);
        
        return array_map(function ($row) {
            return $row['inst']['value'] ?? '';
        }, $results);
    }

    /**
     * Build a canvas from an instantiation IRI.
     */
    private function buildCanvas(string $iri, int $index, array $parentProperties): array
    {
        $entity = $this->triplestore->getEntity($iri);
        $properties = $entity['properties'] ?? [];

        // Get instantiation properties
        $identifier = $this->extractFirstValue($properties, 'rico:identifier') ?? basename($iri);
        $title = $this->extractFirstValue($properties, 'rico:title') ?? $identifier;
        
        // Get dimensions (default to placeholder)
        $width = (int) ($properties['rico:width'][0]['value'] ?? 1000);
        $height = (int) ($properties['rico:height'][0]['value'] ?? 1000);
        
        // Get associated file/digital object URL
        $digitalObjectUrl = $this->extractFirstValue($properties, 'rico:digitallyShownBy')
            ?? $this->extractFirstValue($properties, 'rico:hasOrHadDigitalInstantiation');

        $canvasId = $this->baseUrl . '/iiif/canvas/' . urlencode($iri);
        
        $canvas = [
            'id' => $canvasId,
            'type' => 'Canvas',
            'label' => $this->buildLabel($title),
            'width' => $width,
            'height' => $height,
            'items' => [
                [
                    'id' => $this->baseUrl . '/iiif/annotation-page/' . urlencode($iri),
                    'type' => 'AnnotationPage',
                    'items' => [],
                ],
            ],
        ];

        // If we have a digital object, add the painting annotation
        if ($digitalObjectUrl) {
            $canvas['items'][0]['items'][] = [
                'id' => $this->baseUrl . '/iiif/annotation/' . md5($digitalObjectUrl),
                'type' => 'Annotation',
                'motivation' => 'painting',
                'target' => $canvasId,
                'body' => [
                    'id' => $digitalObjectUrl,
                    'type' => 'Image',
                    'format' => $this->inferMimeType($digitalObjectUrl),
                    'width' => $width,
                    'height' => $height,
                ],
            ];
        }

        return $canvas;
    }

    /**
     * Build a placeholder canvas when no instantiation exists.
     */
    private function buildPlaceholderCanvas(string $iri, array $properties): array
    {
        $title = $this->extractFirstValue($properties, 'rico:title') ?? 'No digital content';
        
        return [
            'id' => $this->baseUrl . '/iiif/canvas/' . urlencode($iri) . '/placeholder',
            'type' => 'Canvas',
            'label' => $this->buildLabel($title),
            'width' => 1000,
            'height' => 1000,
            'items' => [
                [
                    'id' => $this->baseUrl . '/iiif/annotation-page/' . urlencode($iri) . '/placeholder',
                    'type' => 'AnnotationPage',
                    'items' => [
                        [
                            'id' => $this->baseUrl . '/iiif/annotation/' . md5($iri) . '/placeholder',
                            'type' => 'Annotation',
                            'motivation' => 'painting',
                            'target' => $this->baseUrl . '/iiif/canvas/' . urlencode($iri) . '/placeholder',
                            'body' => [
                                'id' => $this->baseUrl . '/iiif/placeholder-image',
                                'type' => 'Image',
                                'format' => 'image/png',
                                'width' => 1000,
                                'height' => 1000,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build metadata array from RiC-O properties.
     */
    private function buildMetadata(array $properties): array
    {
        $metadata = [];

        // Identifier
        if ($id = $this->extractFirstValue($properties, 'rico:identifier')) {
            $metadata[] = ['label' => ['en' => ['Reference Code']], 'value' => ['en' => [$id]]];
        }

        // Date
        if ($date = $this->extractFirstValue($properties, 'rico:isAssociatedWithDate')) {
            $metadata[] = ['label' => ['en' => ['Date']], 'value' => ['en' => [$date]]];
        }

        // Extent
        if ($extent = $this->extractFirstValue($properties, 'rico:hasExtent')) {
            $metadata[] = ['label' => ['en' => ['Extent']], 'value' => ['en' => [$extent]]];
        }

        // Language
        if ($lang = $this->extractFirstValue($properties, 'rico:hasOrHadLanguage')) {
            $metadata[] = ['label' => ['en' => ['Language']], 'value' => ['en' => [$lang]]];
        }

        return $metadata;
    }

    /**
     * Build label structure for IIIF (multilingual).
     */
    private function buildLabel(?array $value): array
    {
        if ($value === null) {
            return ['none' => ['@none' => ['Untitled']]];
        }

        // Handle value objects from SPARQL
        $text = is_array($value) ? ($value['value'] ?? '') : $value;
        $lang = is_array($value) ? ($value['lang'] ?? 'en') : 'en';

        return [$lang => [$text]];
    }

    /**
     * Extract first value for a property.
     */
    private function extractFirstValue(array $properties, string $predicate): ?string
    {
        if (!isset($properties[$predicate]) || empty($properties[$predicate])) {
            return null;
        }

        $value = $properties[$predicate][0] ?? null;
        
        if ($value === null) {
            return null;
        }

        return is_array($value) ? ($value['value'] ?? null) : $value;
    }

    /**
     * Build manifest ID from entity IRI.
     */
    private function buildManifestId(string $iri): string
    {
        return $this->baseUrl . '/iiif/manifest/' . urlencode($iri);
    }

    /**
     * Infer MIME type from URL.
     */
    private function inferMimeType(string $url): string
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
