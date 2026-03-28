<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Services;

use OpenRiC\RecordManage\Contracts\RecordServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Record service — adapted from Heratio InformationObjectService (495 lines).
 *
 * Maps ISAD(G) fields to RiC-O properties in the triplestore.
 * Heratio uses 21 i18n fields + 10 structural fields = 31 total.
 * OpenRiC maps all 31 to RiC-O properties stored as RDF in Fuseki.
 *
 * ISAD(G) → RiC-O property mapping:
 *   3.1.1 Reference code        → rico:identifier
 *   3.1.2 Title                 → rico:title
 *   3.1.3 Date(s)               → rico:date (linked rico:DateRange)
 *   3.1.4 Level of description  → rico:hasRecordSetType
 *   3.1.5 Extent and medium     → rico:hasExtent
 *   3.2.1 Name of creator       → rico:hasProvenance (linked Agent)
 *   3.2.2 Admin/biog history    → rico:history
 *   3.2.3 Archival history      → rico:hasOrHadCustodialHistory
 *   3.2.4 Immediate source      → rico:hasAccrual
 *   3.3.1 Scope and content     → rico:scopeAndContent
 *   3.3.2 Appraisal             → rico:appraisal
 *   3.3.3 Accruals              → rico:accruals
 *   3.3.4 System of arrangement → rico:hasOrHadClassificationSystem
 *   3.4.1 Conditions of access  → rico:conditionsOfAccess
 *   3.4.2 Conditions of repro   → rico:conditionsOfReproduction
 *   3.4.3 Language/scripts      → rico:hasOrHadLanguage
 *   3.4.4 Physical characteristics → rico:physicalCharacteristics
 *   3.4.5 Finding aids          → rico:hasOrHadFindingAid
 *   3.5.1 Existence/location originals → rico:existenceAndLocationOfOriginals
 *   3.5.2 Existence/location copies    → rico:existenceAndLocationOfCopies
 *   3.5.3 Related units         → rico:isAssociatedWithRecord
 *   3.5.4 Publication note      → rico:publicationNote
 *   3.6.1 Notes                 → rico:descriptiveNote
 *   3.7.1 Archivist's note      → rico:descriptorsNote
 *   3.7.2 Rules/conventions     → rico:rulesOrConventions
 *   3.7.3 Date(s) of descriptions → rico:dateOfDescription
 */
class RecordService implements RecordServiceInterface
{
    /**
     * Full field map: form field name → RiC-O property.
     * Heratio has 21 i18n fields; we map them all to RiC-O.
     */
    public const FIELD_MAP = [
        // 3.1 Identity Statement Area
        'title'                    => ['property' => 'rico:title', 'datatype' => 'xsd:string'],
        'alternate_title'          => ['property' => 'rico:hasOrHadName', 'datatype' => 'xsd:string'],
        'identifier'               => ['property' => 'rico:identifier', 'datatype' => 'xsd:string'],
        'reference_code'           => ['property' => 'rico:referenceCode', 'datatype' => 'xsd:string'],
        'extent_and_medium'        => ['property' => 'rico:hasExtent', 'datatype' => 'xsd:string'],
        'level_of_description'     => ['property' => 'rico:hasRecordSetType', 'type' => 'uri'],

        // 3.2 Context Area
        'archival_history'         => ['property' => 'rico:hasOrHadCustodialHistory', 'datatype' => 'xsd:string'],
        'acquisition'              => ['property' => 'rico:hasAccrual', 'datatype' => 'xsd:string'],

        // 3.3 Content and Structure Area
        'scope_and_content'        => ['property' => 'rico:scopeAndContent', 'datatype' => 'xsd:string'],
        'appraisal'                => ['property' => 'rico:appraisal', 'datatype' => 'xsd:string'],
        'accruals'                 => ['property' => 'rico:accruals', 'datatype' => 'xsd:string'],
        'arrangement'              => ['property' => 'rico:hasOrHadClassificationSystem', 'datatype' => 'xsd:string'],

        // 3.4 Conditions of Access and Use Area
        'access_conditions'        => ['property' => 'rico:conditionsOfAccess', 'datatype' => 'xsd:string'],
        'reproduction_conditions'  => ['property' => 'rico:conditionsOfReproduction', 'datatype' => 'xsd:string'],
        'physical_characteristics' => ['property' => 'rico:physicalCharacteristics', 'datatype' => 'xsd:string'],
        'finding_aids'             => ['property' => 'rico:hasOrHadFindingAid', 'datatype' => 'xsd:string'],

        // 3.5 Allied Materials Area
        'location_of_originals'        => ['property' => 'rico:existenceAndLocationOfOriginals', 'datatype' => 'xsd:string'],
        'location_of_copies'           => ['property' => 'rico:existenceAndLocationOfCopies', 'datatype' => 'xsd:string'],
        'related_units_of_description' => ['property' => 'rico:isAssociatedWithRecord', 'datatype' => 'xsd:string'],
        'publication_note'             => ['property' => 'rico:publicationNote', 'datatype' => 'xsd:string'],

        // 3.6 Notes Area
        'notes'                    => ['property' => 'rico:descriptiveNote', 'datatype' => 'xsd:string'],

        // 3.7 Description Control Area
        'rules'                    => ['property' => 'rico:rulesOrConventions', 'datatype' => 'xsd:string'],
        'sources'                  => ['property' => 'rico:source', 'datatype' => 'xsd:string'],
        'revision_history'         => ['property' => 'rico:history', 'datatype' => 'xsd:string'],
        'institution_responsible_identifier' => ['property' => 'rico:isOrWasManagedBy', 'type' => 'uri'],
        'edition'                  => ['property' => 'rico:edition', 'datatype' => 'xsd:string'],

        // Structural / relationships
        'parent_iri'               => ['property' => 'rico:isOrWasIncludedIn', 'type' => 'uri'],
        'creator_iri'              => ['property' => 'rico:hasProvenance', 'type' => 'uri'],
        'publication_status'       => ['property' => 'rico:hasRecordState', 'datatype' => 'xsd:string'],
        'description_status'       => ['property' => 'rico:descriptionsStatus', 'datatype' => 'xsd:string'],
        'description_detail'       => ['property' => 'rico:descriptionsDetail', 'datatype' => 'xsd:string'],
        'source_standard'          => ['property' => 'rico:sourceStandard', 'datatype' => 'xsd:string'],
    ];

    /**
     * CamelCase to snake_case mapping — from Heratio InformationObjectService.
     */
    private const CAMEL_MAP = [
        'alternateTitle' => 'alternate_title',
        'extentAndMedium' => 'extent_and_medium',
        'archivalHistory' => 'archival_history',
        'scopeAndContent' => 'scope_and_content',
        'accessConditions' => 'access_conditions',
        'reproductionConditions' => 'reproduction_conditions',
        'physicalCharacteristics' => 'physical_characteristics',
        'findingAids' => 'finding_aids',
        'locationOfOriginals' => 'location_of_originals',
        'locationOfCopies' => 'location_of_copies',
        'relatedUnitsOfDescription' => 'related_units_of_description',
        'institutionResponsibleIdentifier' => 'institution_responsible_identifier',
        'revisionHistory' => 'revision_history',
        'referenceCode' => 'reference_code',
        'levelOfDescription' => 'level_of_description',
        'parentIri' => 'parent_iri',
        'creatorIri' => 'creator_iri',
        'publicationStatus' => 'publication_status',
        'publicationNote' => 'publication_note',
        'descriptionStatus' => 'description_status',
        'descriptionDetail' => 'description_detail',
        'sourceStandard' => 'source_standard',
    ];

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    /**
     * Browse records with filtering, sorting, and pagination.
     * Adapted from Heratio InformationObjectBrowseService (534 lines).
     */
    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $filterClauses = '';
        $bindings = [];

        // Text search filter
        if (!empty($filters['q'])) {
            $filterClauses .= "FILTER(CONTAINS(LCASE(STR(?title)), LCASE(?searchTerm)))\n";
            $bindings['searchTerm'] = $filters['q'];
        }

        // Level of description filter
        if (!empty($filters['level'])) {
            $filterClauses .= "?iri rico:hasRecordSetType ?levelFilter .\n";
            $bindings['levelFilter'] = $filters['level'];
        }

        // Parent filter (for hierarchical browsing)
        if (!empty($filters['parent_iri'])) {
            $filterClauses .= "?iri rico:isOrWasIncludedIn ?parentFilter .\n";
            $bindings['parentFilter'] = $filters['parent_iri'];
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $filterClauses .= "FILTER(?date >= ?dateFrom)\n";
            $bindings['dateFrom'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $filterClauses .= "FILTER(?date <= ?dateTo)\n";
            $bindings['dateTo'] = $filters['date_to'];
        }

        // Creator filter
        if (!empty($filters['creator_iri'])) {
            $filterClauses .= "?iri rico:hasProvenance ?creatorFilter .\n";
            $bindings['creatorFilter'] = $filters['creator_iri'];
        }

        // Sort
        $sortField = match ($filters['sort'] ?? 'title') {
            'identifier' => '?identifier',
            'date' => '?date',
            'updated' => '?updated',
            default => '?title',
        };
        $sortDir = strtoupper($filters['direction'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $sparql = <<<SPARQL
            SELECT ?iri ?title ?identifier ?referenceCode ?levelOfDescription
                   ?scopeAndContent ?date ?extent ?parentIri ?creatorIri
                   ?publicationStatus ?updated
            WHERE {
                ?iri a rico:Record .
                ?iri rico:title ?title .
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:referenceCode ?referenceCode }
                OPTIONAL { ?iri rico:hasRecordSetType ?levelOfDescription }
                OPTIONAL { ?iri rico:scopeAndContent ?scopeAndContent }
                OPTIONAL { ?iri rico:date ?date }
                OPTIONAL { ?iri rico:hasExtent ?extent }
                OPTIONAL { ?iri rico:isOrWasIncludedIn ?parentIri }
                OPTIONAL { ?iri rico:hasProvenance ?creatorIri }
                OPTIONAL { ?iri rico:hasRecordState ?publicationStatus }
                OPTIONAL { ?iri rico:wasModifiedAtDate ?updated }
                {$filterClauses}
            }
            ORDER BY {$sortDir}({$sortField})
            LIMIT {$limit} OFFSET {$offset}
            SPARQL;

        $items = $this->triplestore->select($sparql, $bindings);

        // Count query
        $countSparql = <<<SPARQL
            SELECT (COUNT(DISTINCT ?iri) AS ?count) WHERE {
                ?iri a rico:Record .
                ?iri rico:title ?title .
                {$filterClauses}
            }
            SPARQL;

        $countResult = $this->triplestore->select($countSparql, $bindings);
        $total = (int) ($countResult[0]['count']['value'] ?? 0);

        // Get distinct values for facets
        $facets = $this->getFacets();

        return ['items' => $items, 'total' => $total, 'facets' => $facets];
    }

    /**
     * Find a single record by IRI with all properties.
     */
    public function find(string $iri): ?array
    {
        $entity = $this->triplestore->getEntity($iri);
        if ($entity === null) {
            return null;
        }

        // Enrich with relationship data
        $entity['children'] = $this->getChildren($iri);
        $entity['ancestors'] = $this->getAncestors($iri);

        return $entity;
    }

    /**
     * Create a new record with all ISAD(G) fields mapped to RiC-O.
     * Adapted from Heratio InformationObjectService::create() (92 lines).
     */
    public function create(array $data, string $userId, string $reason): string
    {
        $data = $this->normalizeKeys($data);
        $properties = $this->mapFieldsToProperties($data);

        return $this->triplestore->createEntity('rico:Record', $properties, $userId, $reason);
    }

    /**
     * Update an existing record.
     * Adapted from Heratio InformationObjectService::update() (65 lines).
     */
    public function update(string $iri, array $data, string $userId, string $reason): bool
    {
        $data = $this->normalizeKeys($data);
        $properties = $this->mapFieldsToProperties($data);

        return $this->triplestore->updateEntity($iri, $properties, $userId, $reason);
    }

    /**
     * Delete a record and handle cascade (children, relationships).
     * Adapted from Heratio InformationObjectService::delete() (115 lines).
     * In RiC-O, cascade means removing child rico:isOrWasIncludedIn references.
     */
    public function delete(string $iri, string $userId, string $reason): bool
    {
        // Get children first — they need re-parenting or cascade delete
        $children = $this->getChildren($iri);

        // Delete the entity itself
        $result = $this->triplestore->deleteEntity($iri, $userId, $reason);

        // Delete children recursively if cascade is desired
        foreach ($children as $child) {
            $childIri = $child['iri']['value'] ?? null;
            if ($childIri) {
                $this->triplestore->deleteEntity($childIri, $userId, 'Cascade delete from parent: ' . $iri);
            }
        }

        return $result;
    }

    /**
     * Get direct children of a record.
     */
    public function getChildren(string $parentIri, int $limit = 100): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?iri ?title ?identifier ?levelOfDescription ?date
            WHERE {
                ?iri rico:isOrWasIncludedIn ?parent .
                ?iri rico:title ?title .
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:hasRecordSetType ?levelOfDescription }
                OPTIONAL { ?iri rico:date ?date }
            }
            ORDER BY ?identifier ?title
            SPARQL;

        return $this->triplestore->select($sparql, ['parent' => $parentIri]);
    }

    /**
     * Get ancestor chain (breadcrumb) for a record.
     */
    public function getAncestors(string $iri, int $maxDepth = 20): array
    {
        $ancestors = [];
        $current = $iri;

        for ($i = 0; $i < $maxDepth; $i++) {
            $sparql = <<<'SPARQL'
                SELECT ?parent ?title ?identifier
                WHERE {
                    ?child rico:isOrWasIncludedIn ?parent .
                    ?parent rico:title ?title .
                    OPTIONAL { ?parent rico:identifier ?identifier }
                }
                LIMIT 1
                SPARQL;

            $result = $this->triplestore->select($sparql, ['child' => $current]);
            if (empty($result)) {
                break;
            }

            $parent = $result[0];
            $ancestors[] = $parent;
            $current = $parent['parent']['value'];
        }

        return array_reverse($ancestors);
    }

    /**
     * Get child count for a record.
     */
    public function getChildCount(string $iri): int
    {
        $sparql = <<<'SPARQL'
            SELECT (COUNT(?child) AS ?count) WHERE {
                ?child rico:isOrWasIncludedIn ?parent .
            }
            SPARQL;

        $result = $this->triplestore->select($sparql, ['parent' => $iri]);
        return (int) ($result[0]['count']['value'] ?? 0);
    }

    /**
     * Autocomplete search for records by title or identifier.
     */
    public function autocomplete(string $query, int $limit = 10): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?iri ?title ?identifier ?levelOfDescription
            WHERE {
                ?iri a rico:Record .
                ?iri rico:title ?title .
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:hasRecordSetType ?levelOfDescription }
                FILTER(CONTAINS(LCASE(STR(?title)), LCASE(?searchTerm))
                    || CONTAINS(LCASE(STR(?identifier)), LCASE(?searchTerm)))
            }
            ORDER BY ?title
            SPARQL;

        return $this->triplestore->select($sparql, ['searchTerm' => $query]);
    }

    /**
     * Get facet values for browse filtering (levels, creators, date ranges).
     */
    private function getFacets(): array
    {
        $levelsSparql = <<<'SPARQL'
            SELECT ?level (COUNT(?iri) AS ?count) WHERE {
                ?iri a rico:Record .
                ?iri rico:hasRecordSetType ?level .
            }
            GROUP BY ?level
            ORDER BY DESC(?count)
            LIMIT 20
            SPARQL;

        $levels = $this->triplestore->select($levelsSparql);

        return ['levels' => $levels];
    }

    /**
     * Map form field data to RiC-O property structures for the triplestore.
     */
    private function mapFieldsToProperties(array $data): array
    {
        $properties = [];

        foreach ($data as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $mapping = self::FIELD_MAP[$field] ?? null;
            if ($mapping === null) {
                continue;
            }

            $prop = ['value' => $value];

            if (isset($mapping['type']) && $mapping['type'] === 'uri') {
                $prop['type'] = 'uri';
            } elseif (isset($mapping['datatype'])) {
                $prop['datatype'] = $mapping['datatype'];
            }

            $properties[$mapping['property']] = $prop;
        }

        return $properties;
    }

    /**
     * Normalize keys from camelCase to snake_case.
     * Adapted from Heratio InformationObjectService::normalizeKeys() (28 lines).
     */
    private function normalizeKeys(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[self::CAMEL_MAP[$key] ?? $key] = $value;
        }
        return $normalized;
    }
}
