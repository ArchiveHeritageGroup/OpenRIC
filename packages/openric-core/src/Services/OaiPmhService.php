<?php

declare(strict_types=1);

namespace OpenRiC\Core\Services;

use OpenRiC\Core\Contracts\OaiPmhServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * OAI-PMH data retrieval service.
 *
 * Queries the triplestore via TriplestoreServiceInterface for all
 * archival record data and maps RiC-O properties to Dublin Core.
 */
class OaiPmhService implements OaiPmhServiceInterface
{
    /**
     * RiC-O to Dublin Core mapping.
     *
     * Keys are RiC-O property prefixed names; values are DC element names.
     */
    private const RICO_TO_DC = [
        'rico:title'               => 'dc:title',
        'rico:identifier'          => 'dc:identifier',
        'rico:scopeAndContent'     => 'dc:description',
        'rico:hasOrHadCreator'     => 'dc:creator',
        'rico:isAssociatedWithDate' => 'dc:date',
        'rico:hasOrHadLanguage'    => 'dc:language',
        'rico:hasOrHadSubject'     => 'dc:subject',
        'rico:hasOrHadHolder'      => 'dc:relation',
        'rico:conditionsOfAccess'  => 'dc:rights',
        'rico:conditionsOfUse'     => 'dc:rights',
        'rico:extentAndMedium'     => 'dc:format',
        'rico:hasOrHadPublisher'   => 'dc:publisher',
        'rico:hasOrHadContributor' => 'dc:contributor',
        'rico:locationOfOriginals' => 'dc:source',
        'rico:isAssociatedWithPlace' => 'dc:coverage',
        'rico:type'                => 'dc:type',
    ];

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getEarliestDatestamp(): string
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
            SELECT (MIN(?modified) AS ?earliest)
            WHERE {
                ?s a rico:Record .
                ?s rico:modificationDate ?modified .
            }
            LIMIT 1
        ';

        $results = $this->triplestore->select($sparql);

        if (!empty($results) && !empty($results[0]['earliest']['value'])) {
            return $this->toUtcDatestring($results[0]['earliest']['value']);
        }

        return gmdate('Y-m-d\TH:i:s\Z');
    }

    /**
     * {@inheritDoc}
     */
    public function getSets(int $offset = 0, int $limit = 100): array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $countSparql = $prefixes . '
            SELECT (COUNT(DISTINCT ?s) AS ?total)
            WHERE {
                ?s a rico:RecordSet .
                ?s rico:hasRecordSetType <https://www.ica.org/standards/RiC/vocabularies/recordSetTypes#Fonds> .
                ?s rico:title ?title .
            }
            LIMIT 1
        ';

        $countResults = $this->triplestore->select($countSparql);
        $total = 0;

        if (!empty($countResults) && !empty($countResults[0]['total']['value'])) {
            $total = (int) $countResults[0]['total']['value'];
        }

        $sparql = $prefixes . '
            SELECT ?s ?title
            WHERE {
                ?s a rico:RecordSet .
                ?s rico:hasRecordSetType <https://www.ica.org/standards/RiC/vocabularies/recordSetTypes#Fonds> .
                ?s rico:title ?title .
            }
            ORDER BY ?title
            LIMIT ' . $limit . '
            OFFSET ' . $offset . '
        ';

        $results = $this->triplestore->select($sparql);
        $items = [];

        foreach ($results as $row) {
            $items[] = [
                'iri'   => $row['s']['value'] ?? '',
                'title' => $row['title']['value'] ?? '',
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRecordHeaders(
        ?string $from = null,
        ?string $until = null,
        ?string $setIri = null,
        int $offset = 0,
        int $limit = 100,
    ): array {
        $prefixes = $this->triplestore->getPrefixes();
        $dateFilter = $this->buildDateFilter($from, $until);
        $setFilter = $this->buildSetFilter($setIri);

        $countSparql = $prefixes . '
            SELECT (COUNT(DISTINCT ?s) AS ?total)
            WHERE {
                ?s a rico:Record .
                ?s rico:modificationDate ?modified .
                ' . $setFilter . '
                ' . $dateFilter . '
            }
            LIMIT 1
        ';

        $countResults = $this->triplestore->select($countSparql);
        $total = 0;

        if (!empty($countResults) && !empty($countResults[0]['total']['value'])) {
            $total = (int) $countResults[0]['total']['value'];
        }

        $sparql = $prefixes . '
            SELECT ?s ?modified
            WHERE {
                ?s a rico:Record .
                ?s rico:modificationDate ?modified .
                ' . $setFilter . '
                ' . $dateFilter . '
            }
            ORDER BY ?s
            LIMIT ' . $limit . '
            OFFSET ' . $offset . '
        ';

        $results = $this->triplestore->select($sparql);
        $items = [];

        foreach ($results as $row) {
            $iri = $row['s']['value'] ?? '';
            $items[] = [
                'iri'       => $iri,
                'datestamp' => $this->toUtcDatestring($row['modified']['value'] ?? ''),
                'setIri'    => $this->getSetForRecord($iri),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRecords(
        ?string $from = null,
        ?string $until = null,
        ?string $setIri = null,
        int $offset = 0,
        int $limit = 100,
    ): array {
        $prefixes = $this->triplestore->getPrefixes();
        $dateFilter = $this->buildDateFilter($from, $until);
        $setFilter = $this->buildSetFilter($setIri);

        $countSparql = $prefixes . '
            SELECT (COUNT(DISTINCT ?s) AS ?total)
            WHERE {
                ?s a rico:Record .
                ?s rico:modificationDate ?modified .
                ' . $setFilter . '
                ' . $dateFilter . '
            }
            LIMIT 1
        ';

        $countResults = $this->triplestore->select($countSparql);
        $total = 0;

        if (!empty($countResults) && !empty($countResults[0]['total']['value'])) {
            $total = (int) $countResults[0]['total']['value'];
        }

        $sparql = $prefixes . '
            SELECT ?s ?modified ?title ?identifier ?scopeAndContent
                   ?creator ?date ?language ?subject ?holder
                   ?accessConditions ?useConditions ?extentAndMedium
                   ?publisher ?contributor ?locationOfOriginals
                   ?place ?type
            WHERE {
                ?s a rico:Record .
                ?s rico:modificationDate ?modified .
                ' . $setFilter . '
                ' . $dateFilter . '
                OPTIONAL { ?s rico:title ?title . }
                OPTIONAL { ?s rico:identifier ?identifier . }
                OPTIONAL { ?s rico:scopeAndContent ?scopeAndContent . }
                OPTIONAL { ?s rico:hasOrHadCreator ?creatorIri .
                           ?creatorIri rico:hasOrHadAgentName ?creatorNameNode .
                           ?creatorNameNode rico:textualValue ?creator . }
                OPTIONAL { ?s rico:isAssociatedWithDate ?dateNode .
                           ?dateNode rico:expressedDate ?date . }
                OPTIONAL { ?s rico:hasOrHadLanguage ?langIri .
                           ?langIri rico:hasOrHadName ?langNameNode .
                           ?langNameNode rico:textualValue ?language . }
                OPTIONAL { ?s rico:hasOrHadSubject ?subjectIri .
                           ?subjectIri rico:hasOrHadName ?subjectNameNode .
                           ?subjectNameNode rico:textualValue ?subject . }
                OPTIONAL { ?s rico:hasOrHadHolder ?holderIri .
                           ?holderIri rico:hasOrHadAgentName ?holderNameNode .
                           ?holderNameNode rico:textualValue ?holder . }
                OPTIONAL { ?s rico:conditionsOfAccess ?accessConditions . }
                OPTIONAL { ?s rico:conditionsOfUse ?useConditions . }
                OPTIONAL { ?s rico:extentAndMedium ?extentAndMedium . }
                OPTIONAL { ?s rico:hasOrHadPublisher ?publisherIri .
                           ?publisherIri rico:hasOrHadAgentName ?publisherNameNode .
                           ?publisherNameNode rico:textualValue ?publisher . }
                OPTIONAL { ?s rico:hasOrHadContributor ?contributorIri .
                           ?contributorIri rico:hasOrHadAgentName ?contributorNameNode .
                           ?contributorNameNode rico:textualValue ?contributor . }
                OPTIONAL { ?s rico:locationOfOriginals ?locationOfOriginals . }
                OPTIONAL { ?s rico:isAssociatedWithPlace ?placeIri .
                           ?placeIri rico:hasOrHadName ?placeNameNode .
                           ?placeNameNode rico:textualValue ?place . }
                OPTIONAL { ?s rico:type ?type . }
            }
            ORDER BY ?s
            LIMIT ' . $limit . '
            OFFSET ' . $offset . '
        ';

        $results = $this->triplestore->select($sparql);
        $items = $this->aggregateRecordRows($results);

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRecord(string $iri): ?array
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
            SELECT ?modified ?title ?identifier ?scopeAndContent
                   ?creator ?date ?language ?subject ?holder
                   ?accessConditions ?useConditions ?extentAndMedium
                   ?publisher ?contributor ?locationOfOriginals
                   ?place ?type
            WHERE {
                BIND(?RECORD_IRI AS ?s)
                ?s a rico:Record .
                ?s rico:modificationDate ?modified .
                OPTIONAL { ?s rico:title ?title . }
                OPTIONAL { ?s rico:identifier ?identifier . }
                OPTIONAL { ?s rico:scopeAndContent ?scopeAndContent . }
                OPTIONAL { ?s rico:hasOrHadCreator ?creatorIri .
                           ?creatorIri rico:hasOrHadAgentName ?creatorNameNode .
                           ?creatorNameNode rico:textualValue ?creator . }
                OPTIONAL { ?s rico:isAssociatedWithDate ?dateNode .
                           ?dateNode rico:expressedDate ?date . }
                OPTIONAL { ?s rico:hasOrHadLanguage ?langIri .
                           ?langIri rico:hasOrHadName ?langNameNode .
                           ?langNameNode rico:textualValue ?language . }
                OPTIONAL { ?s rico:hasOrHadSubject ?subjectIri .
                           ?subjectIri rico:hasOrHadName ?subjectNameNode .
                           ?subjectNameNode rico:textualValue ?subject . }
                OPTIONAL { ?s rico:hasOrHadHolder ?holderIri .
                           ?holderIri rico:hasOrHadAgentName ?holderNameNode .
                           ?holderNameNode rico:textualValue ?holder . }
                OPTIONAL { ?s rico:conditionsOfAccess ?accessConditions . }
                OPTIONAL { ?s rico:conditionsOfUse ?useConditions . }
                OPTIONAL { ?s rico:extentAndMedium ?extentAndMedium . }
                OPTIONAL { ?s rico:hasOrHadPublisher ?publisherIri .
                           ?publisherIri rico:hasOrHadAgentName ?publisherNameNode .
                           ?publisherNameNode rico:textualValue ?publisher . }
                OPTIONAL { ?s rico:hasOrHadContributor ?contributorIri .
                           ?contributorIri rico:hasOrHadAgentName ?contributorNameNode .
                           ?contributorNameNode rico:textualValue ?contributor . }
                OPTIONAL { ?s rico:locationOfOriginals ?locationOfOriginals . }
                OPTIONAL { ?s rico:isAssociatedWithPlace ?placeIri .
                           ?placeIri rico:hasOrHadName ?placeNameNode .
                           ?placeNameNode rico:textualValue ?place . }
                OPTIONAL { ?s rico:type ?type . }
            }
            LIMIT 100
        ';

        $results = $this->triplestore->select($sparql, [
            'RECORD_IRI' => '<' . $iri . '>',
        ]);

        if (empty($results)) {
            return null;
        }

        $items = $this->aggregateRecordRows($results, $iri);

        if (empty($items)) {
            return null;
        }

        return $items[0];
    }

    /**
     * {@inheritDoc}
     */
    public function mapToDublinCore(array $record): array
    {
        $dc = [
            'dc:title'       => [],
            'dc:creator'     => [],
            'dc:subject'     => [],
            'dc:description' => [],
            'dc:publisher'   => [],
            'dc:contributor' => [],
            'dc:date'        => [],
            'dc:type'        => [],
            'dc:format'      => [],
            'dc:identifier'  => [],
            'dc:source'      => [],
            'dc:language'    => [],
            'dc:relation'    => [],
            'dc:coverage'    => [],
            'dc:rights'      => [],
        ];

        if (!empty($record['title'])) {
            $dc['dc:title'] = is_array($record['title']) ? $record['title'] : [$record['title']];
        }

        if (!empty($record['identifier'])) {
            $dc['dc:identifier'] = is_array($record['identifier']) ? $record['identifier'] : [$record['identifier']];
        }

        if (!empty($record['iri'])) {
            $dc['dc:identifier'][] = $record['iri'];
        }

        if (!empty($record['scopeAndContent'])) {
            $values = is_array($record['scopeAndContent']) ? $record['scopeAndContent'] : [$record['scopeAndContent']];
            $dc['dc:description'] = array_map(fn (string $v): string => strip_tags($v), $values);
        }

        if (!empty($record['creator'])) {
            $dc['dc:creator'] = is_array($record['creator']) ? $record['creator'] : [$record['creator']];
        }

        if (!empty($record['date'])) {
            $dc['dc:date'] = is_array($record['date']) ? $record['date'] : [$record['date']];
        }

        if (!empty($record['language'])) {
            $dc['dc:language'] = is_array($record['language']) ? $record['language'] : [$record['language']];
        }

        if (!empty($record['subject'])) {
            $dc['dc:subject'] = is_array($record['subject']) ? $record['subject'] : [$record['subject']];
        }

        if (!empty($record['holder'])) {
            $dc['dc:relation'] = is_array($record['holder']) ? $record['holder'] : [$record['holder']];
        }

        if (!empty($record['accessConditions'])) {
            $values = is_array($record['accessConditions']) ? $record['accessConditions'] : [$record['accessConditions']];
            $dc['dc:rights'] = array_merge($dc['dc:rights'], array_map(fn (string $v): string => strip_tags($v), $values));
        }

        if (!empty($record['useConditions'])) {
            $values = is_array($record['useConditions']) ? $record['useConditions'] : [$record['useConditions']];
            $dc['dc:rights'] = array_merge($dc['dc:rights'], array_map(fn (string $v): string => strip_tags($v), $values));
        }

        if (!empty($record['extentAndMedium'])) {
            $values = is_array($record['extentAndMedium']) ? $record['extentAndMedium'] : [$record['extentAndMedium']];
            $dc['dc:format'] = array_map(fn (string $v): string => strip_tags($v), $values);
        }

        if (!empty($record['publisher'])) {
            $dc['dc:publisher'] = is_array($record['publisher']) ? $record['publisher'] : [$record['publisher']];
        }

        if (!empty($record['contributor'])) {
            $dc['dc:contributor'] = is_array($record['contributor']) ? $record['contributor'] : [$record['contributor']];
        }

        if (!empty($record['locationOfOriginals'])) {
            $values = is_array($record['locationOfOriginals']) ? $record['locationOfOriginals'] : [$record['locationOfOriginals']];
            $dc['dc:source'] = array_map(fn (string $v): string => strip_tags($v), $values);
        }

        if (!empty($record['place'])) {
            $dc['dc:coverage'] = is_array($record['place']) ? $record['place'] : [$record['place']];
        }

        if (!empty($record['type'])) {
            $dc['dc:type'] = is_array($record['type']) ? $record['type'] : [$record['type']];
        }

        return $dc;
    }

    /**
     * {@inheritDoc}
     */
    public function getSetForRecord(string $recordIri): ?string
    {
        $prefixes = $this->triplestore->getPrefixes();

        $sparql = $prefixes . '
            SELECT ?fonds
            WHERE {
                BIND(?RECORD_IRI AS ?record)
                ?record rico:isOrWasIncludedIn+ ?fonds .
                ?fonds a rico:RecordSet .
                ?fonds rico:hasRecordSetType <https://www.ica.org/standards/RiC/vocabularies/recordSetTypes#Fonds> .
            }
            LIMIT 1
        ';

        $results = $this->triplestore->select($sparql, [
            'RECORD_IRI' => '<' . $recordIri . '>',
        ]);

        if (!empty($results) && !empty($results[0]['fonds']['value'])) {
            return $results[0]['fonds']['value'];
        }

        return null;
    }

    /**
     * Build a SPARQL FILTER clause for date range filtering.
     *
     * @param  string|null $from   ISO 8601 from date
     * @param  string|null $until  ISO 8601 until date
     * @return string  SPARQL FILTER clause or empty string
     */
    private function buildDateFilter(?string $from, ?string $until): string
    {
        $clauses = [];

        if ($from !== null && $from !== '') {
            $fromDate = $this->escSparqlLiteral($from);
            $clauses[] = '?modified >= "' . $fromDate . '"^^xsd:dateTime';
        }

        if ($until !== null && $until !== '') {
            $untilDate = $this->escSparqlLiteral($until);
            $clauses[] = '?modified <= "' . $untilDate . '"^^xsd:dateTime';
        }

        if (empty($clauses)) {
            return '';
        }

        return 'FILTER(' . implode(' && ', $clauses) . ')';
    }

    /**
     * Build a SPARQL pattern clause for set (fonds) filtering.
     *
     * @param  string|null $setIri  IRI of the set to filter by
     * @return string  SPARQL pattern or empty string
     */
    private function buildSetFilter(?string $setIri): string
    {
        if ($setIri === null || $setIri === '') {
            return '';
        }

        $escapedIri = $this->escSparqlIri($setIri);

        return '?s rico:isOrWasIncludedIn+ <' . $escapedIri . '> .';
    }

    /**
     * Aggregate SPARQL result rows into distinct records, collecting multi-valued
     * properties into arrays.
     *
     * @param  array<int, array<string, mixed>> $rows       SPARQL result rows
     * @param  string|null                      $fixedIri   if set, use this IRI for all rows
     * @return array<int, array<string, mixed>>  aggregated records
     */
    private function aggregateRecordRows(array $rows, ?string $fixedIri = null): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $iri = $fixedIri ?? ($row['s']['value'] ?? '');

            if ($iri === '') {
                continue;
            }

            if (!isset($grouped[$iri])) {
                $grouped[$iri] = [
                    'iri'                 => $iri,
                    'datestamp'           => $this->toUtcDatestring($row['modified']['value'] ?? ''),
                    'title'               => [],
                    'identifier'          => [],
                    'scopeAndContent'     => [],
                    'creator'             => [],
                    'date'                => [],
                    'language'            => [],
                    'subject'             => [],
                    'holder'              => [],
                    'accessConditions'    => [],
                    'useConditions'       => [],
                    'extentAndMedium'     => [],
                    'publisher'           => [],
                    'contributor'         => [],
                    'locationOfOriginals' => [],
                    'place'               => [],
                    'type'                => [],
                ];
            }

            $record = &$grouped[$iri];

            $multiValueFields = [
                'title', 'identifier', 'scopeAndContent', 'creator', 'date',
                'language', 'subject', 'holder', 'accessConditions', 'useConditions',
                'extentAndMedium', 'publisher', 'contributor', 'locationOfOriginals',
                'place', 'type',
            ];

            foreach ($multiValueFields as $field) {
                if (!empty($row[$field]['value'])) {
                    $val = $row[$field]['value'];

                    if (!in_array($val, $record[$field], true)) {
                        $record[$field][] = $val;
                    }
                }
            }

            unset($record);
        }

        return array_values($grouped);
    }

    /**
     * Convert a date value to UTC ISO 8601 format.
     *
     * @param  string $date  input date string
     * @return string  formatted UTC date
     */
    private function toUtcDatestring(string $date): string
    {
        if ($date === '') {
            return gmdate('Y-m-d\TH:i:s\Z');
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return gmdate('Y-m-d\TH:i:s\Z');
        }

        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }

    /**
     * Escape a string for use as a SPARQL literal value.
     *
     * @param  string $value  raw value
     * @return string  escaped value safe for SPARQL string literal
     */
    private function escSparqlLiteral(string $value): string
    {
        return addcslashes($value, "\\\"\n\r\t");
    }

    /**
     * Escape a string for use as a SPARQL IRI (strip angle brackets and dangerous chars).
     *
     * @param  string $iri  raw IRI value
     * @return string  safe IRI string (without angle bracket wrappers)
     */
    private function escSparqlIri(string $iri): string
    {
        return str_replace(['<', '>', '"', ' ', '{', '}', '|', '\\', '^', '`'], '', $iri);
    }
}
