<?php

declare(strict_types=1);

namespace OpenRiC\Export\Services;

use Illuminate\Support\Facades\Log;
use OpenRiC\Export\Contracts\IiifServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * IIIF Presentation API 3.0 service for OpenRiC.
 *
 * Generates IIIF Manifests for rico:Instantiation entities and IIIF Collections
 * for rico:RecordSet entities. All data is sourced from the triplestore via
 * TriplestoreServiceInterface — adapted from Heratio's IiifCollectionService.
 */
class IiifService implements IiifServiceInterface
{
    /**
     * IIIF Presentation API 3.0 context URI.
     */
    private const IIIF_CONTEXT = 'http://iiif.io/api/presentation/3/context.json';

    /**
     * RiC-O namespace.
     */
    private const RICO_NS = 'https://www.ica.org/standards/RiC/ontology#';

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    // =========================================================================
    // Manifest Generation
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function generateManifest(string $instantiationIri): array
    {
        $entity = $this->triplestore->getEntity($instantiationIri);

        if ($entity === null) {
            Log::warning('IIIF manifest: Instantiation not found', ['iri' => $instantiationIri]);

            return $this->emptyManifest($instantiationIri);
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        $manifestId = $baseUrl . '/iiif/' . urlencode($instantiationIri) . '/manifest';

        $label = $this->extractValue($entity, 'rico:title')
            ?? $this->extractValue($entity, 'rico:identifier')
            ?? 'Untitled';

        $manifest = [
            '@context' => self::IIIF_CONTEXT,
            'id'       => $manifestId,
            'type'     => 'Manifest',
            'label'    => ['en' => [$label]],
        ];

        $summary = $this->extractValue($entity, 'rico:scopeAndContent');
        if ($summary !== null) {
            $manifest['summary'] = ['en' => [$summary]];
        }

        $manifest['metadata'] = $this->buildManifestMetadata($entity);

        $rights = $this->extractValue($entity, 'rico:conditionsOfUse');
        if ($rights !== null) {
            $manifest['rights'] = $rights;
        }

        $attribution = $this->extractValue($entity, 'rico:hasOrHadHolder');
        if ($attribution !== null) {
            $manifest['requiredStatement'] = [
                'label' => ['en' => ['Attribution']],
                'value' => ['en' => [$attribution]],
            ];
        }

        $canvases = $this->buildCanvases($instantiationIri, $entity, $baseUrl);
        $manifest['items'] = $canvases;

        if (!empty($canvases)) {
            $firstCanvas = $canvases[0];
            $manifest['thumbnail'] = $this->buildThumbnailFromCanvas($firstCanvas);
        }

        $parentIri = $this->extractValue($entity, 'rico:isInstantiationOf');
        if ($parentIri !== null) {
            $manifest['seeAlso'] = [
                [
                    'id'     => $baseUrl . '/export/' . urlencode($parentIri) . '/jsonld',
                    'type'   => 'Dataset',
                    'format' => 'application/ld+json',
                    'label'  => ['en' => ['RiC-O JSON-LD']],
                ],
            ];
        }

        return $manifest;
    }

    // =========================================================================
    // Collection Generation
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function generateCollection(string $recordSetIri): array
    {
        $entity = $this->triplestore->getEntity($recordSetIri);

        if ($entity === null) {
            Log::warning('IIIF collection: RecordSet not found', ['iri' => $recordSetIri]);

            return $this->emptyCollection($recordSetIri);
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        $collectionId = $baseUrl . '/iiif/' . urlencode($recordSetIri) . '/collection';

        $label = $this->extractValue($entity, 'rico:title') ?? 'Untitled Collection';

        $collection = [
            '@context' => self::IIIF_CONTEXT,
            'id'       => $collectionId,
            'type'     => 'Collection',
            'label'    => ['en' => [$label]],
        ];

        $summary = $this->extractValue($entity, 'rico:scopeAndContent');
        if ($summary !== null) {
            $collection['summary'] = ['en' => [$summary]];
        }

        $attribution = $this->extractValue($entity, 'rico:hasOrHadHolder');
        if ($attribution !== null) {
            $collection['requiredStatement'] = [
                'label' => ['en' => ['Attribution']],
                'value' => ['en' => [$attribution]],
            ];
        }

        $collection['metadata'] = $this->buildCollectionMetadata($entity);
        $collection['items'] = $this->buildCollectionItems($recordSetIri, $baseUrl);

        return $collection;
    }

    // =========================================================================
    // Canvas Building
    // =========================================================================

    /**
     * Build IIIF canvases for an Instantiation's digital representations.
     *
     * Queries the triplestore for rico:hasRepresentation links and builds
     * a canvas with an annotation page for each digital file.
     *
     * @param  string $instantiationIri
     * @param  array<string, mixed> $entity
     * @param  string $baseUrl
     * @return array<int, array<string, mixed>>
     */
    private function buildCanvases(string $instantiationIri, array $entity, string $baseUrl): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
            SELECT ?representation ?mimeType ?width ?height ?fileUrl ?label WHERE {
                ?instantiation rico:hasRepresentation ?representation .
                OPTIONAL { ?representation rico:hasContentOfType ?mimeType . }
                OPTIONAL { ?representation rico:width ?width . }
                OPTIONAL { ?representation rico:height ?height . }
                OPTIONAL { ?representation rico:hasOrHadDigitalFileIdentifier ?fileUrl . }
                OPTIONAL { ?representation rico:title ?label . }
            }
            LIMIT 200
        ';

        $results = $this->triplestore->select($sparql, ['instantiation' => $instantiationIri]);

        if (empty($results)) {
            return $this->buildSingleCanvasFromEntity($instantiationIri, $entity, $baseUrl);
        }

        $canvases = [];
        $index = 1;

        foreach ($results as $row) {
            $repIri = $row['representation'] ?? '';
            $mimeType = $row['mimeType'] ?? 'image/jpeg';
            $width = (int) ($row['width'] ?? 1000);
            $height = (int) ($row['height'] ?? 1000);
            $fileUrl = $row['fileUrl'] ?? ($baseUrl . '/files/' . urlencode($repIri));
            $canvasLabel = $row['label'] ?? 'Canvas ' . $index;

            $canvasId = $baseUrl . '/iiif/' . urlencode($instantiationIri) . '/canvas/' . $index;
            $annotationPageId = $canvasId . '/page';
            $annotationId = $canvasId . '/annotation';

            $canvases[] = [
                'id'     => $canvasId,
                'type'   => 'Canvas',
                'label'  => ['en' => [$canvasLabel]],
                'width'  => $width,
                'height' => $height,
                'items'  => [
                    [
                        'id'    => $annotationPageId,
                        'type'  => 'AnnotationPage',
                        'items' => [
                            [
                                'id'         => $annotationId,
                                'type'       => 'Annotation',
                                'motivation' => 'painting',
                                'body'       => [
                                    'id'     => $fileUrl,
                                    'type'   => $this->iiifBodyType($mimeType),
                                    'format' => $mimeType,
                                    'width'  => $width,
                                    'height' => $height,
                                ],
                                'target' => $canvasId,
                            ],
                        ],
                    ],
                ],
            ];

            $index++;
        }

        return $canvases;
    }

    /**
     * Build a single fallback canvas from the Instantiation entity itself.
     *
     * Used when no rico:hasRepresentation triples exist, but the entity
     * may carry a file identifier directly.
     *
     * @param  string $instantiationIri
     * @param  array<string, mixed> $entity
     * @param  string $baseUrl
     * @return array<int, array<string, mixed>>
     */
    private function buildSingleCanvasFromEntity(string $instantiationIri, array $entity, string $baseUrl): array
    {
        $fileUrl = $this->extractValue($entity, 'rico:hasOrHadDigitalFileIdentifier');
        if ($fileUrl === null) {
            return [];
        }

        $mimeType = $this->extractValue($entity, 'rico:hasContentOfType') ?? 'image/jpeg';
        $width = (int) ($this->extractValue($entity, 'rico:width') ?? '1000');
        $height = (int) ($this->extractValue($entity, 'rico:height') ?? '1000');
        $label = $this->extractValue($entity, 'rico:title') ?? 'Image 1';

        $canvasId = $baseUrl . '/iiif/' . urlencode($instantiationIri) . '/canvas/1';

        return [
            [
                'id'     => $canvasId,
                'type'   => 'Canvas',
                'label'  => ['en' => [$label]],
                'width'  => $width,
                'height' => $height,
                'items'  => [
                    [
                        'id'    => $canvasId . '/page',
                        'type'  => 'AnnotationPage',
                        'items' => [
                            [
                                'id'         => $canvasId . '/annotation',
                                'type'       => 'Annotation',
                                'motivation' => 'painting',
                                'body'       => [
                                    'id'     => $fileUrl,
                                    'type'   => $this->iiifBodyType($mimeType),
                                    'format' => $mimeType,
                                    'width'  => $width,
                                    'height' => $height,
                                ],
                                'target' => $canvasId,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    // =========================================================================
    // Collection Items Building
    // =========================================================================

    /**
     * Build IIIF Collection items from RecordSet children and their instantiations.
     *
     * First includes sub-RecordSets as nested Collections, then includes
     * Records/RecordParts that have Instantiations as Manifests.
     *
     * @param  string $recordSetIri
     * @param  string $baseUrl
     * @return array<int, array<string, mixed>>
     */
    private function buildCollectionItems(string $recordSetIri, string $baseUrl): array
    {
        $prefixes = $this->triplestore->getPrefixes();
        $items = [];

        $subsetsSparql = $prefixes . '
            SELECT ?subset ?title WHERE {
                ?parent rico:hasOrHadPart ?subset .
                ?subset rdf:type rico:RecordSet .
                OPTIONAL { ?subset rico:title ?title . }
            }
            ORDER BY ?title
            LIMIT 500
        ';

        $subsets = $this->triplestore->select($subsetsSparql, ['parent' => $recordSetIri]);

        foreach ($subsets as $row) {
            $subIri = $row['subset'] ?? '';
            $subTitle = $row['title'] ?? 'Sub-collection';

            $items[] = [
                'id'    => $baseUrl . '/iiif/' . urlencode($subIri) . '/collection',
                'type'  => 'Collection',
                'label' => ['en' => [$subTitle]],
            ];
        }

        $manifestsSparql = $prefixes . '
            SELECT ?record ?title ?instantiation WHERE {
                ?parent rico:hasOrHadPart ?record .
                ?record rico:hasInstantiation ?instantiation .
                OPTIONAL { ?record rico:title ?title . }
            }
            ORDER BY ?title
            LIMIT 500
        ';

        $manifests = $this->triplestore->select($manifestsSparql, ['parent' => $recordSetIri]);

        foreach ($manifests as $row) {
            $instIri = $row['instantiation'] ?? '';
            $recTitle = $row['title'] ?? 'Untitled';

            if ($instIri === '') {
                continue;
            }

            $items[] = [
                'id'    => $baseUrl . '/iiif/' . urlencode($instIri) . '/manifest',
                'type'  => 'Manifest',
                'label' => ['en' => [$recTitle]],
            ];
        }

        return $items;
    }

    // =========================================================================
    // Metadata Builders
    // =========================================================================

    /**
     * Build IIIF metadata array from entity properties for a Manifest.
     *
     * @param  array<string, mixed> $entity
     * @return array<int, array{label: array<string, array<string>>, value: array<string, array<string>>}>
     */
    private function buildManifestMetadata(array $entity): array
    {
        return $this->buildMetadataFromMapping($entity, [
            'rico:identifier'          => 'Identifier',
            'rico:hasCreator'          => 'Creator',
            'rico:hasOrHadHolder'      => 'Holder',
            'rico:isAssociatedWithDate' => 'Date',
            'rico:hasOrHadLanguage'    => 'Language',
            'rico:hasExtent'           => 'Extent',
            'rico:hasContentOfType'    => 'Type',
            'rico:conditionsOfAccess'  => 'Access Conditions',
        ]);
    }

    /**
     * Build IIIF metadata array from entity properties for a Collection.
     *
     * @param  array<string, mixed> $entity
     * @return array<int, array{label: array<string, array<string>>, value: array<string, array<string>>}>
     */
    private function buildCollectionMetadata(array $entity): array
    {
        return $this->buildMetadataFromMapping($entity, [
            'rico:identifier'          => 'Identifier',
            'rico:hasCreator'          => 'Creator',
            'rico:hasOrHadHolder'      => 'Holder',
            'rico:isAssociatedWithDate' => 'Date',
            'rico:hasExtent'           => 'Extent',
            'rico:scopeAndContent'     => 'Description',
        ]);
    }

    /**
     * Build IIIF metadata entries from an entity using a property-to-label mapping.
     *
     * @param  array<string, mixed> $entity
     * @param  array<string, string> $mapping  rico:property => display label
     * @return array<int, array{label: array<string, array<string>>, value: array<string, array<string>>}>
     */
    private function buildMetadataFromMapping(array $entity, array $mapping): array
    {
        $metadata = [];

        foreach ($mapping as $property => $displayLabel) {
            $value = $this->extractValue($entity, $property);
            if ($value !== null) {
                $metadata[] = [
                    'label' => ['en' => [$displayLabel]],
                    'value' => ['en' => [$value]],
                ];
            }
        }

        return $metadata;
    }

    // =========================================================================
    // Thumbnail
    // =========================================================================

    /**
     * Build a IIIF thumbnail reference from a canvas.
     *
     * @param  array<string, mixed> $canvas
     * @return array<int, array<string, mixed>>
     */
    private function buildThumbnailFromCanvas(array $canvas): array
    {
        $annotationPages = $canvas['items'] ?? [];
        foreach ($annotationPages as $page) {
            $annotations = $page['items'] ?? [];
            foreach ($annotations as $annotation) {
                $body = $annotation['body'] ?? [];
                if (isset($body['id'])) {
                    return [
                        [
                            'id'     => $body['id'],
                            'type'   => $body['type'] ?? 'Image',
                            'format' => $body['format'] ?? 'image/jpeg',
                        ],
                    ];
                }
            }
        }

        return [];
    }

    // =========================================================================
    // Empty Response Builders
    // =========================================================================

    /**
     * Return a minimal empty manifest when the entity is not found.
     *
     * @param  string $iri
     * @return array<string, mixed>
     */
    private function emptyManifest(string $iri): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return [
            '@context' => self::IIIF_CONTEXT,
            'id'       => $baseUrl . '/iiif/' . urlencode($iri) . '/manifest',
            'type'     => 'Manifest',
            'label'    => ['en' => ['Not found']],
            'items'    => [],
        ];
    }

    /**
     * Return a minimal empty collection when the entity is not found.
     *
     * @param  string $iri
     * @return array<string, mixed>
     */
    private function emptyCollection(string $iri): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return [
            '@context' => self::IIIF_CONTEXT,
            'id'       => $baseUrl . '/iiif/' . urlencode($iri) . '/collection',
            'type'     => 'Collection',
            'label'    => ['en' => ['Not found']],
            'items'    => [],
        ];
    }

    // =========================================================================
    // Utility Methods
    // =========================================================================

    /**
     * Determine the IIIF body type from a MIME type.
     */
    private function iiifBodyType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'Image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'Video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'Sound';
        }

        return 'Dataset';
    }

    /**
     * Extract a single string value from an entity property map.
     *
     * @param  array<string, mixed> $entity
     * @param  string $property
     * @return string|null
     */
    private function extractValue(array $entity, string $property): ?string
    {
        $value = $entity[$property] ?? null;

        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $first = $value[0] ?? null;

            return $first !== null ? (string) $first : null;
        }

        return (string) $value;
    }
}
