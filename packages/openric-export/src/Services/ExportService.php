<?php

declare(strict_types=1);

namespace OpenRiC\Export\Services;

use OpenRiC\Export\Contracts\ExportServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Full implementation of archival entity export in standard interchange formats.
 *
 * Every method queries the triplestore via TriplestoreServiceInterface — no
 * direct Fuseki calls. Adapted from Heratio RicController::exportJsonLd and
 * extended with Turtle, RDF/XML, EAD3, EAC-CPF, and Dublin Core support.
 */
class ExportService implements ExportServiceInterface
{
    /**
     * RiC-O namespace URI.
     */
    private const RICO_NS = 'https://www.ica.org/standards/RiC/ontology#';

    /**
     * Standard RDF namespace URIs used in serialisations.
     *
     * @var array<string, string>
     */
    private const NAMESPACES = [
        'rico' => 'https://www.ica.org/standards/RiC/ontology#',
        'rdf'  => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
        'owl'  => 'http://www.w3.org/2002/07/owl#',
        'xsd'  => 'http://www.w3.org/2001/XMLSchema#',
        'dc'   => 'http://purl.org/dc/elements/1.1/',
        'dcterms' => 'http://purl.org/dc/terms/',
    ];

    /**
     * Mapping from RiC-O properties to Dublin Core elements.
     *
     * @var array<string, string>
     */
    private const RICO_TO_DC = [
        'rico:title'                   => 'dc:title',
        'rico:identifier'              => 'dc:identifier',
        'rico:scopeAndContent'         => 'dc:description',
        'rico:hasCreator'              => 'dc:creator',
        'rico:hasOrHadSubject'         => 'dc:subject',
        'rico:hasPublisher'            => 'dc:publisher',
        'rico:hasOrHadLanguage'        => 'dc:language',
        'rico:hasContentOfType'        => 'dc:type',
        'rico:isAssociatedWithDate'    => 'dc:date',
        'rico:hasExtent'               => 'dc:format',
        'rico:hasOrHadAllMembersWithContentOfType' => 'dc:type',
        'rico:conditionsOfAccess'      => 'dc:rights',
        'rico:conditionsOfUse'         => 'dc:rights',
        'rico:isPartOf'                => 'dc:relation',
        'rico:hasOrHadHolder'          => 'dc:contributor',
    ];

    /**
     * Mapping from RiC-O properties to EAD3 elements.
     *
     * @var array<string, string>
     */
    private const RICO_TO_EAD3 = [
        'rico:title'               => 'unittitle',
        'rico:identifier'          => 'unitid',
        'rico:scopeAndContent'     => 'scopecontent',
        'rico:conditionsOfAccess'  => 'accessrestrict',
        'rico:conditionsOfUse'     => 'userestrict',
        'rico:hasExtent'           => 'physdesc',
        'rico:history'             => 'bioghist',
        'rico:hasOrHadLanguage'    => 'langmaterial',
        'rico:structure'           => 'arrangement',
    ];

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    // =========================================================================
    // JSON-LD
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function exportJsonLd(string $iri): array
    {
        $context = [
            'rico'  => self::NAMESPACES['rico'],
            'rdf'   => self::NAMESPACES['rdf'],
            'rdfs'  => self::NAMESPACES['rdfs'],
            'owl'   => self::NAMESPACES['owl'],
            'xsd'   => self::NAMESPACES['xsd'],
            'title' => 'rico:title',
            'hasCreator'              => ['@id' => 'rico:hasCreator', '@type' => '@id'],
            'hasOrHadHolder'          => ['@id' => 'rico:hasOrHadHolder', '@type' => '@id'],
            'hasCreationDate'         => ['@id' => 'rico:hasCreationDate', '@type' => '@id'],
            'hasAccumulationDate'     => ['@id' => 'rico:hasAccumulationDate', '@type' => '@id'],
            'describesOrDescribed'    => ['@id' => 'rico:describesOrDescribed', '@type' => '@id'],
            'isAssociatedWith'        => ['@id' => 'rico:isAssociatedWith', '@type' => '@id'],
            'hasProvenanceOf'         => ['@id' => 'rico:hasProvenanceOf', '@type' => '@id'],
            'isEquivalentTo'          => ['@id' => 'rico:isEquivalentTo', '@type' => '@id'],
            'resultsOrResultedFrom'   => ['@id' => 'rico:resultsOrResultedFrom', '@type' => '@id'],
            'isPartOf'                => ['@id' => 'rico:isPartOf', '@type' => '@id'],
            'hasOrHadSubject'         => ['@id' => 'rico:hasOrHadSubject', '@type' => '@id'],
        ];

        $triples = $this->triplestore->describe($iri);

        $entity = $this->buildJsonLdEntityFromTriples($iri, $triples);

        $relatedIris = $this->extractRelatedIris($triples, $iri);
        $graph = [$entity];

        foreach ($relatedIris as $relatedIri) {
            $relatedTriples = $this->triplestore->describe($relatedIri);
            if (!empty($relatedTriples)) {
                $graph[] = $this->buildJsonLdEntityFromTriples($relatedIri, $relatedTriples);
            }
        }

        return [
            '@context' => $context,
            '@graph'   => $graph,
        ];
    }

    // =========================================================================
    // Turtle
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function exportTurtle(string $iri): string
    {
        $triples = $this->triplestore->describe($iri);

        $lines = [];

        foreach (self::NAMESPACES as $prefix => $namespace) {
            $lines[] = "@prefix {$prefix}: <{$namespace}> .";
        }
        $lines[] = '';

        $grouped = $this->groupTriplesBySubject($triples);

        foreach ($grouped as $subject => $predicates) {
            $lines[] = $this->formatTurtleSubject($subject);

            $predicateLines = [];
            foreach ($predicates as $predicate => $objects) {
                $objectStrings = array_map(fn (string $obj): string => $this->formatTurtleObject($obj), $objects);
                $predicateLines[] = '    ' . $this->compactIri($predicate) . ' ' . implode(' , ', $objectStrings);
            }

            $lines[] = implode(" ;\n", $predicateLines) . ' .';
            $lines[] = '';
        }

        return implode("\n", $lines) . "\n";
    }

    // =========================================================================
    // RDF/XML
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function exportRdfXml(string $iri): string
    {
        $triples = $this->triplestore->describe($iri);
        $grouped = $this->groupTriplesBySubject($triples);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rdf:RDF' . "\n";
        foreach (self::NAMESPACES as $prefix => $namespace) {
            $xml .= '    xmlns:' . $prefix . '="' . $this->escapeXmlAttribute($namespace) . '"' . "\n";
        }
        $xml .= '>' . "\n";

        foreach ($grouped as $subject => $predicates) {
            $rdfType = $predicates[self::NAMESPACES['rdf'] . 'type'][0] ?? null;
            $elementName = $rdfType !== null ? $this->compactIri($rdfType) : 'rdf:Description';

            $xml .= '  <' . $elementName . ' rdf:about="' . $this->escapeXmlAttribute($subject) . '">' . "\n";

            foreach ($predicates as $predicate => $objects) {
                if ($predicate === self::NAMESPACES['rdf'] . 'type') {
                    continue;
                }

                $prefixedPredicate = $this->compactIri($predicate);

                foreach ($objects as $object) {
                    if ($this->isIri($object)) {
                        $xml .= '    <' . $prefixedPredicate . ' rdf:resource="' . $this->escapeXmlAttribute($object) . '"/>' . "\n";
                    } else {
                        $xml .= '    <' . $prefixedPredicate . '>' . $this->escapeXmlText($object) . '</' . $prefixedPredicate . '>' . "\n";
                    }
                }
            }

            $xml .= '  </' . $elementName . '>' . "\n";
        }

        $xml .= '</rdf:RDF>' . "\n";

        return $xml;
    }

    // =========================================================================
    // EAD3
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function exportEad3(string $iri): string
    {
        $entity = $this->triplestore->getEntity($iri);

        if ($entity === null) {
            return $this->wrapEad3Xml('', $iri);
        }

        $controlXml = $this->buildEad3Control($iri, $entity);
        $archdescXml = $this->buildEad3Archdesc($iri, $entity);

        return $this->wrapEad3Xml($controlXml . "\n" . $archdescXml, $iri);
    }

    // =========================================================================
    // EAC-CPF
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function exportEacCpf(string $iri): string
    {
        $entity = $this->triplestore->getEntity($iri);

        if ($entity === null) {
            return $this->wrapEacCpfXml('', $iri);
        }

        $controlXml = $this->buildEacCpfControl($iri, $entity);
        $cpfDescriptionXml = $this->buildEacCpfDescription($iri, $entity);

        return $this->wrapEacCpfXml($controlXml . "\n" . $cpfDescriptionXml, $iri);
    }

    // =========================================================================
    // Dublin Core
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function exportDublinCore(string $iri): string
    {
        $triples = $this->triplestore->describe($iri);

        $dcElements = [];

        foreach ($triples as $triple) {
            $predicate = $triple['predicate'] ?? '';
            $object = $triple['object'] ?? '';
            $compactPredicate = $this->compactIri($predicate);

            if (isset(self::RICO_TO_DC[$compactPredicate])) {
                $dcElement = self::RICO_TO_DC[$compactPredicate];
                $value = $this->isIri($object) ? $object : $object;
                $dcElements[$dcElement][] = $value;
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<oai_dc:dc' . "\n";
        $xml .= '    xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"' . "\n";
        $xml .= '    xmlns:dc="http://purl.org/dc/elements/1.1/"' . "\n";
        $xml .= '    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        $xml .= '    xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">' . "\n";

        foreach ($dcElements as $element => $values) {
            foreach ($values as $value) {
                $xml .= '  <' . $element . '>' . $this->escapeXmlText($value) . '</' . $element . '>' . "\n";
            }
        }

        if (empty($dcElements)) {
            $entity = $this->triplestore->getEntity($iri);
            if ($entity !== null) {
                if (isset($entity['rico:title'])) {
                    $xml .= '  <dc:title>' . $this->escapeXmlText((string) $entity['rico:title']) . '</dc:title>' . "\n";
                }
                if (isset($entity['rico:identifier'])) {
                    $xml .= '  <dc:identifier>' . $this->escapeXmlText((string) $entity['rico:identifier']) . '</dc:identifier>' . "\n";
                }
                if (isset($entity['rico:scopeAndContent'])) {
                    $xml .= '  <dc:description>' . $this->escapeXmlText((string) $entity['rico:scopeAndContent']) . '</dc:description>' . "\n";
                }
            }
        }

        $xml .= '</oai_dc:dc>' . "\n";

        return $xml;
    }

    // =========================================================================
    // Bulk Export
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function bulkExport(array $iris, string $format): string
    {
        $formats = $this->getAvailableFormats();
        if (!isset($formats[$format])) {
            throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        $parts = [];

        foreach ($iris as $iri) {
            $parts[] = match ($format) {
                'jsonld'  => json_encode($this->exportJsonLd($iri), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'turtle'  => $this->exportTurtle($iri),
                'rdfxml'  => $this->exportRdfXml($iri),
                'ead3'    => $this->exportEad3($iri),
                'eaccpf'  => $this->exportEacCpf($iri),
                'dc'      => $this->exportDublinCore($iri),
                default   => '',
            };
        }

        if ($format === 'jsonld') {
            $combined = array_map(
                fn (string $json): array => json_decode($json, true),
                array_filter($parts, fn (string $p): bool => $p !== '')
            );

            return json_encode($combined, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return implode("\n", $parts);
    }

    // =========================================================================
    // Available Formats
    // =========================================================================

    /**
     * {@inheritDoc}
     */
    public function getAvailableFormats(): array
    {
        return [
            'jsonld' => [
                'label'     => 'JSON-LD',
                'mimeType'  => 'application/ld+json',
                'extension' => 'jsonld',
            ],
            'turtle' => [
                'label'     => 'Turtle',
                'mimeType'  => 'text/turtle',
                'extension' => 'ttl',
            ],
            'rdfxml' => [
                'label'     => 'RDF/XML',
                'mimeType'  => 'application/rdf+xml',
                'extension' => 'rdf',
            ],
            'ead3' => [
                'label'     => 'EAD3',
                'mimeType'  => 'application/xml',
                'extension' => 'xml',
            ],
            'eaccpf' => [
                'label'     => 'EAC-CPF',
                'mimeType'  => 'application/xml',
                'extension' => 'xml',
            ],
            'dc' => [
                'label'     => 'Dublin Core',
                'mimeType'  => 'application/xml',
                'extension' => 'xml',
            ],
        ];
    }

    // =========================================================================
    // JSON-LD Helpers
    // =========================================================================

    /**
     * Build a JSON-LD entity object from triples.
     *
     * @param  string $iri     the entity IRI
     * @param  array<int, array<string, string>> $triples
     * @return array<string, mixed>
     */
    private function buildJsonLdEntityFromTriples(string $iri, array $triples): array
    {
        $entity = ['@id' => $iri];
        $rdfType = null;

        foreach ($triples as $triple) {
            if (($triple['subject'] ?? '') !== $iri) {
                continue;
            }

            $predicate = $triple['predicate'] ?? '';
            $object = $triple['object'] ?? '';

            if ($predicate === self::NAMESPACES['rdf'] . 'type') {
                $rdfType = $object;
                continue;
            }

            $compactPred = $this->compactIri($predicate);

            if ($this->isIri($object)) {
                $value = ['@id' => $object];
            } else {
                $value = $object;
            }

            if (isset($entity[$compactPred])) {
                if (!is_array($entity[$compactPred]) || !isset($entity[$compactPred][0])) {
                    $entity[$compactPred] = [$entity[$compactPred]];
                }
                $entity[$compactPred][] = $value;
            } else {
                $entity[$compactPred] = $value;
            }
        }

        if ($rdfType !== null) {
            $entity['@type'] = $this->compactIri($rdfType);
        }

        return $entity;
    }

    /**
     * Extract IRIs of related entities from triples (objects that are IRIs, excluding the root).
     *
     * @param  array<int, array<string, string>> $triples
     * @param  string $rootIri
     * @return array<int, string>
     */
    private function extractRelatedIris(array $triples, string $rootIri): array
    {
        $iris = [];

        foreach ($triples as $triple) {
            $object = $triple['object'] ?? '';
            if ($this->isIri($object) && $object !== $rootIri) {
                $iris[$object] = true;
            }
        }

        return array_keys($iris);
    }

    // =========================================================================
    // Turtle Helpers
    // =========================================================================

    /**
     * Group triples by subject, then by predicate.
     *
     * @param  array<int, array<string, string>> $triples
     * @return array<string, array<string, array<int, string>>>
     */
    private function groupTriplesBySubject(array $triples): array
    {
        $grouped = [];

        foreach ($triples as $triple) {
            $subject = $triple['subject'] ?? '';
            $predicate = $triple['predicate'] ?? '';
            $object = $triple['object'] ?? '';

            $grouped[$subject][$predicate][] = $object;
        }

        return $grouped;
    }

    /**
     * Format a subject IRI for Turtle output.
     */
    private function formatTurtleSubject(string $subject): string
    {
        $compact = $this->compactIri($subject);

        return $compact !== $subject ? $compact : '<' . $subject . '>';
    }

    /**
     * Format an object value for Turtle output (IRI or literal).
     */
    private function formatTurtleObject(string $object): string
    {
        if ($this->isIri($object)) {
            $compact = $this->compactIri($object);

            return $compact !== $object ? $compact : '<' . $object . '>';
        }

        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $object);

        return '"' . $escaped . '"';
    }

    // =========================================================================
    // EAD3 Helpers
    // =========================================================================

    /**
     * Wrap EAD3 content in a complete <ead> document.
     */
    private function wrapEad3Xml(string $innerXml, string $iri): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<ead xmlns="http://ead3.archivists.org/schema/"' . "\n";
        $xml .= '     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        $xml .= '     xsi:schemaLocation="http://ead3.archivists.org/schema/ http://www.loc.gov/ead/ead3.xsd">' . "\n";
        $xml .= $innerXml;
        $xml .= '</ead>' . "\n";

        return $xml;
    }

    /**
     * Build the <control> section of an EAD3 document.
     *
     * @param  string $iri
     * @param  array<string, mixed> $entity
     * @return string
     */
    private function buildEad3Control(string $iri, array $entity): string
    {
        $identifier = $this->entityValue($entity, 'rico:identifier', $iri);
        $title = $this->entityValue($entity, 'rico:title', 'Untitled');

        $xml = '  <control>' . "\n";
        $xml .= '    <recordid>' . $this->escapeXmlText($identifier) . '</recordid>' . "\n";
        $xml .= '    <filedesc>' . "\n";
        $xml .= '      <titlestmt>' . "\n";
        $xml .= '        <titleproper>' . $this->escapeXmlText($title) . '</titleproper>' . "\n";
        $xml .= '      </titlestmt>' . "\n";
        $xml .= '    </filedesc>' . "\n";
        $xml .= '    <maintenancestatus value="derived"/>' . "\n";
        $xml .= '    <maintenanceagency>' . "\n";
        $xml .= '      <agencyname>OpenRiC</agencyname>' . "\n";
        $xml .= '    </maintenanceagency>' . "\n";
        $xml .= '    <maintenancehistory>' . "\n";
        $xml .= '      <maintenanceevent>' . "\n";
        $xml .= '        <eventtype value="derived"/>' . "\n";
        $xml .= '        <eventdatetime standarddatetime="' . date('Y-m-d\TH:i:s') . '"/>' . "\n";
        $xml .= '        <agenttype value="machine"/>' . "\n";
        $xml .= '        <agent>OpenRiC Export Service</agent>' . "\n";
        $xml .= '      </maintenanceevent>' . "\n";
        $xml .= '    </maintenancehistory>' . "\n";
        $xml .= '  </control>' . "\n";

        return $xml;
    }

    /**
     * Build the <archdesc> section of an EAD3 document, including recursive <c> children.
     *
     * @param  string $iri
     * @param  array<string, mixed> $entity
     * @return string
     */
    private function buildEad3Archdesc(string $iri, array $entity): string
    {
        $level = $this->determineEadLevel($entity);

        $xml = '  <archdesc level="' . $level . '">' . "\n";
        $xml .= '    <did>' . "\n";
        $xml .= $this->buildEad3Did($entity);
        $xml .= '    </did>' . "\n";

        $xml .= $this->buildEad3DescriptiveElements($entity, '    ');

        $children = $this->getChildEntities($iri);
        if (!empty($children)) {
            $xml .= '    <dsc>' . "\n";
            foreach ($children as $child) {
                $xml .= $this->buildEad3Component($child['iri'], $child['entity'], 6);
            }
            $xml .= '    </dsc>' . "\n";
        }

        $xml .= '  </archdesc>' . "\n";

        return $xml;
    }

    /**
     * Build a recursive <c> component element for EAD3.
     *
     * @param  string $iri
     * @param  array<string, mixed> $entity
     * @param  int    $indent  number of leading spaces
     * @return string
     */
    private function buildEad3Component(string $iri, array $entity, int $indent): string
    {
        $pad = str_repeat(' ', $indent);
        $level = $this->determineEadLevel($entity);

        $xml = $pad . '<c level="' . $level . '">' . "\n";
        $xml .= $pad . '  <did>' . "\n";
        $xml .= $this->buildEad3Did($entity, $indent + 4);
        $xml .= $pad . '  </did>' . "\n";

        $xml .= $this->buildEad3DescriptiveElements($entity, $pad . '  ');

        $children = $this->getChildEntities($iri);
        foreach ($children as $child) {
            $xml .= $this->buildEad3Component($child['iri'], $child['entity'], $indent + 2);
        }

        $xml .= $pad . '</c>' . "\n";

        return $xml;
    }

    /**
     * Build <did> inner elements from an entity.
     *
     * @param  array<string, mixed> $entity
     * @param  int $indent
     * @return string
     */
    private function buildEad3Did(array $entity, int $indent = 6): string
    {
        $pad = str_repeat(' ', $indent);
        $xml = '';

        $title = $this->entityValue($entity, 'rico:title');
        if ($title !== null) {
            $xml .= $pad . '<unittitle>' . $this->escapeXmlText($title) . '</unittitle>' . "\n";
        }

        $identifier = $this->entityValue($entity, 'rico:identifier');
        if ($identifier !== null) {
            $xml .= $pad . '<unitid>' . $this->escapeXmlText($identifier) . '</unitid>' . "\n";
        }

        $extent = $this->entityValue($entity, 'rico:hasExtent');
        if ($extent !== null) {
            $xml .= $pad . '<physdesc>' . $this->escapeXmlText($extent) . '</physdesc>' . "\n";
        }

        $language = $this->entityValue($entity, 'rico:hasOrHadLanguage');
        if ($language !== null) {
            $xml .= $pad . '<langmaterial>' . "\n";
            $xml .= $pad . '  <language>' . $this->escapeXmlText($language) . '</language>' . "\n";
            $xml .= $pad . '</langmaterial>' . "\n";
        }

        return $xml;
    }

    /**
     * Build EAD3 descriptive elements (scopecontent, accessrestrict, etc.) from entity properties.
     *
     * @param  array<string, mixed> $entity
     * @param  string $pad  indent prefix
     * @return string
     */
    private function buildEad3DescriptiveElements(array $entity, string $pad): string
    {
        $xml = '';
        $mapping = [
            'rico:scopeAndContent'     => 'scopecontent',
            'rico:conditionsOfAccess'  => 'accessrestrict',
            'rico:conditionsOfUse'     => 'userestrict',
            'rico:history'             => 'bioghist',
            'rico:structure'           => 'arrangement',
        ];

        foreach ($mapping as $ricoProperty => $ead3Element) {
            $value = $this->entityValue($entity, $ricoProperty);
            if ($value !== null) {
                $xml .= $pad . '<' . $ead3Element . '>' . "\n";
                $xml .= $pad . '  <p>' . $this->escapeXmlText($value) . '</p>' . "\n";
                $xml .= $pad . '</' . $ead3Element . '>' . "\n";
            }
        }

        return $xml;
    }

    /**
     * Determine the EAD level attribute from a RiC-O entity's type.
     *
     * @param  array<string, mixed> $entity
     * @return string
     */
    private function determineEadLevel(array $entity): string
    {
        $type = $this->entityValue($entity, 'rdf:type', '');
        $cleanType = str_replace(self::RICO_NS, '', (string) $type);

        return match ($cleanType) {
            'RecordSet'  => 'fonds',
            'Record'     => 'file',
            'RecordPart' => 'item',
            default      => 'otherlevel',
        };
    }

    // =========================================================================
    // EAC-CPF Helpers
    // =========================================================================

    /**
     * Wrap EAC-CPF content in a complete document.
     */
    private function wrapEacCpfXml(string $innerXml, string $iri): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<eac-cpf xmlns="urn:isbn:1-931666-33-4"' . "\n";
        $xml .= '         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        $xml .= '         xsi:schemaLocation="urn:isbn:1-931666-33-4 https://eac.staatsbibliothek-berlin.de/schema/cpf.xsd">' . "\n";
        $xml .= $innerXml;
        $xml .= '</eac-cpf>' . "\n";

        return $xml;
    }

    /**
     * Build the <control> section of an EAC-CPF document.
     *
     * @param  string $iri
     * @param  array<string, mixed> $entity
     * @return string
     */
    private function buildEacCpfControl(string $iri, array $entity): string
    {
        $identifier = $this->entityValue($entity, 'rico:identifier', $iri);

        $xml = '  <control>' . "\n";
        $xml .= '    <recordId>' . $this->escapeXmlText($identifier) . '</recordId>' . "\n";
        $xml .= '    <maintenanceStatus>derived</maintenanceStatus>' . "\n";
        $xml .= '    <maintenanceAgency>' . "\n";
        $xml .= '      <agencyName>OpenRiC</agencyName>' . "\n";
        $xml .= '    </maintenanceAgency>' . "\n";
        $xml .= '    <maintenanceHistory>' . "\n";
        $xml .= '      <maintenanceEvent>' . "\n";
        $xml .= '        <eventType>derived</eventType>' . "\n";
        $xml .= '        <eventDateTime standardDateTime="' . date('Y-m-d\TH:i:s') . '"/>' . "\n";
        $xml .= '        <agentType>machine</agentType>' . "\n";
        $xml .= '        <agent>OpenRiC Export Service</agent>' . "\n";
        $xml .= '      </maintenanceEvent>' . "\n";
        $xml .= '    </maintenanceHistory>' . "\n";
        $xml .= '  </control>' . "\n";

        return $xml;
    }

    /**
     * Build the <cpfDescription> section of an EAC-CPF document.
     *
     * @param  string $iri
     * @param  array<string, mixed> $entity
     * @return string
     */
    private function buildEacCpfDescription(string $iri, array $entity): string
    {
        $xml = '  <cpfDescription>' . "\n";

        $xml .= $this->buildEacCpfIdentity($iri, $entity);
        $xml .= $this->buildEacCpfDescriptionSection($entity);
        $xml .= $this->buildEacCpfRelations($iri);

        $xml .= '  </cpfDescription>' . "\n";

        return $xml;
    }

    /**
     * Build the <identity> section of EAC-CPF.
     *
     * @param  string $iri
     * @param  array<string, mixed> $entity
     * @return string
     */
    private function buildEacCpfIdentity(string $iri, array $entity): string
    {
        $type = $this->entityValue($entity, 'rdf:type', '');
        $cleanType = str_replace(self::RICO_NS, '', (string) $type);

        $entityType = match ($cleanType) {
            'Person'         => 'person',
            'CorporateBody'  => 'corporateBody',
            'Family'         => 'family',
            default          => 'corporateBody',
        };

        $xml = '    <identity>' . "\n";
        $xml .= '      <entityType>' . $entityType . '</entityType>' . "\n";

        $agentName = $this->entityValue($entity, 'rico:hasAgentName');
        $title = $this->entityValue($entity, 'rico:title');
        $nameValue = $agentName ?? $title ?? 'Unknown';

        $xml .= '      <nameEntry>' . "\n";
        $xml .= '        <part>' . $this->escapeXmlText($nameValue) . '</part>' . "\n";
        $xml .= '      </nameEntry>' . "\n";

        $identifier = $this->entityValue($entity, 'rico:identifier');
        if ($identifier !== null) {
            $xml .= '      <entityId>' . $this->escapeXmlText($identifier) . '</entityId>' . "\n";
        }

        $xml .= '    </identity>' . "\n";

        return $xml;
    }

    /**
     * Build the <description> section of EAC-CPF.
     *
     * @param  array<string, mixed> $entity
     * @return string
     */
    private function buildEacCpfDescriptionSection(array $entity): string
    {
        $xml = '    <description>' . "\n";

        $history = $this->entityValue($entity, 'rico:history');
        if ($history !== null) {
            $xml .= '      <biogHist>' . "\n";
            $xml .= '        <p>' . $this->escapeXmlText($history) . '</p>' . "\n";
            $xml .= '      </biogHist>' . "\n";
        }

        $beginDate = $this->entityValue($entity, 'rico:hasBeginningDate');
        $endDate = $this->entityValue($entity, 'rico:hasEndDate');
        if ($beginDate !== null || $endDate !== null) {
            $xml .= '      <existDates>' . "\n";
            $xml .= '        <dateRange>' . "\n";
            if ($beginDate !== null) {
                $xml .= '          <fromDate standardDate="' . $this->escapeXmlAttribute($beginDate) . '">' . $this->escapeXmlText($beginDate) . '</fromDate>' . "\n";
            }
            if ($endDate !== null) {
                $xml .= '          <toDate standardDate="' . $this->escapeXmlAttribute($endDate) . '">' . $this->escapeXmlText($endDate) . '</toDate>' . "\n";
            }
            $xml .= '        </dateRange>' . "\n";
            $xml .= '      </existDates>' . "\n";
        }

        $places = $this->entityValue($entity, 'rico:hasOrHadLocation');
        if ($places !== null) {
            $xml .= '      <places>' . "\n";
            $xml .= '        <place>' . "\n";
            $xml .= '          <placeEntry>' . $this->escapeXmlText($places) . '</placeEntry>' . "\n";
            $xml .= '        </place>' . "\n";
            $xml .= '      </places>' . "\n";
        }

        $mandate = $this->entityValue($entity, 'rico:hasOrHadMandate');
        if ($mandate !== null) {
            $xml .= '      <mandates>' . "\n";
            $xml .= '        <mandate>' . "\n";
            $xml .= '          <term>' . $this->escapeXmlText($mandate) . '</term>' . "\n";
            $xml .= '        </mandate>' . "\n";
            $xml .= '      </mandates>' . "\n";
        }

        $legalStatus = $this->entityValue($entity, 'rico:hasOrHadLegalStatus');
        if ($legalStatus !== null) {
            $xml .= '      <legalStatuses>' . "\n";
            $xml .= '        <legalStatus>' . "\n";
            $xml .= '          <term>' . $this->escapeXmlText($legalStatus) . '</term>' . "\n";
            $xml .= '        </legalStatus>' . "\n";
            $xml .= '      </legalStatuses>' . "\n";
        }

        $functions = $this->entityValue($entity, 'rico:performsOrPerformed');
        if ($functions !== null) {
            $xml .= '      <functions>' . "\n";
            $xml .= '        <function>' . "\n";
            $xml .= '          <term>' . $this->escapeXmlText($functions) . '</term>' . "\n";
            $xml .= '        </function>' . "\n";
            $xml .= '      </functions>' . "\n";
        }

        $xml .= '    </description>' . "\n";

        return $xml;
    }

    /**
     * Build the <relations> section of EAC-CPF from triplestore relationships.
     *
     * @param  string $iri
     * @return string
     */
    private function buildEacCpfRelations(string $iri): string
    {
        $relationships = $this->triplestore->getRelationships($iri, 100);

        if (empty($relationships)) {
            return '';
        }

        $xml = '    <relations>' . "\n";

        foreach ($relationships as $rel) {
            $predicate = $rel['predicate'] ?? '';
            $object = $rel['object'] ?? '';
            $subject = $rel['subject'] ?? '';

            $relatedIri = ($subject === $iri) ? $object : $subject;

            if (!$this->isIri($relatedIri)) {
                continue;
            }

            $relType = $this->mapRicoPredicateToEacRelationType($predicate);

            $xml .= '      <cpfRelation cpfRelationType="' . $relType . '">' . "\n";
            $xml .= '        <relationEntry>' . $this->escapeXmlText($relatedIri) . '</relationEntry>' . "\n";
            $xml .= '      </cpfRelation>' . "\n";
        }

        $xml .= '    </relations>' . "\n";

        return $xml;
    }

    /**
     * Map a RiC-O predicate to an EAC-CPF relation type.
     */
    private function mapRicoPredicateToEacRelationType(string $predicate): string
    {
        $compact = $this->compactIri($predicate);

        return match ($compact) {
            'rico:isPartOf', 'rico:hasOrHadPart'          => 'hierarchical',
            'rico:hasCreator', 'rico:isCreatorOf'          => 'associative',
            'rico:hasOrHadHolder', 'rico:isHolderOf'       => 'associative',
            'rico:hasSuccessor', 'rico:isSuccessorOf'      => 'temporal',
            'rico:hasPredecessor', 'rico:isPredecessorOf'  => 'temporal',
            'rico:isRelatedTo'                             => 'associative',
            default                                        => 'associative',
        };
    }

    // =========================================================================
    // Hierarchy Helpers
    // =========================================================================

    /**
     * Get child entities for an IRI via the triplestore (rico:hasOrHadPart).
     *
     * @param  string $parentIri
     * @return array<int, array{iri: string, entity: array<string, mixed>}>
     */
    private function getChildEntities(string $parentIri): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
            SELECT ?child WHERE {
                ?parent rico:hasOrHadPart ?child .
            }
            LIMIT 500
        ';

        $results = $this->triplestore->select($sparql, ['parent' => $parentIri]);
        $children = [];

        foreach ($results as $row) {
            $childIri = $row['child'] ?? '';
            if ($childIri === '') {
                continue;
            }

            $childEntity = $this->triplestore->getEntity($childIri);
            if ($childEntity !== null) {
                $children[] = [
                    'iri'    => $childIri,
                    'entity' => $childEntity,
                ];
            }
        }

        return $children;
    }

    // =========================================================================
    // Shared Utility Methods
    // =========================================================================

    /**
     * Compact a full IRI to a prefixed form (e.g. rico:title) if a known prefix matches.
     */
    private function compactIri(string $iri): string
    {
        foreach (self::NAMESPACES as $prefix => $namespace) {
            if (str_starts_with($iri, $namespace)) {
                return $prefix . ':' . substr($iri, strlen($namespace));
            }
        }

        return $iri;
    }

    /**
     * Determine if a string is an IRI (starts with http:// or https://).
     */
    private function isIri(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }

    /**
     * Safely extract a single value from an entity property map.
     *
     * @param  array<string, mixed> $entity
     * @param  string $property
     * @param  string|null $default
     * @return string|null
     */
    private function entityValue(array $entity, string $property, ?string $default = null): ?string
    {
        $value = $entity[$property] ?? null;

        if ($value === null) {
            return $default;
        }

        if (is_array($value)) {
            return (string) ($value[0] ?? $default);
        }

        return (string) $value;
    }

    /**
     * Escape a string for use in XML text content.
     */
    private function escapeXmlText(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape a string for use in an XML attribute value.
     */
    private function escapeXmlAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
