<?php

declare(strict_types=1);

namespace OpenRiC\AgentManage\Services;

use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Base agent service — adapted from Heratio ActorService (1,531 lines).
 *
 * Maps all ISAAR(CPF) fields to RiC-O properties:
 *   5.1.2 Authorized form of name   → rico:hasOrHadAgentName (authorized)
 *   5.1.3 Parallel forms of name    → rico:hasOrHadAgentName (parallel)
 *   5.1.4 Standardized names        → rico:hasOrHadAgentName (standardized)
 *   5.1.5 Other forms of name       → rico:hasOrHadAgentName (other)
 *   5.2.1 Dates of existence        → rico:hasOrHadDemographicGroup / rico:existedFrom/To
 *   5.2.2 History                   → rico:history
 *   5.2.3 Places                    → rico:hasOrHadLocation
 *   5.2.4 Legal status              → rico:hasOrHadLegalStatus
 *   5.2.5 Functions/activities       → rico:performsOrPerformed
 *   5.2.6 Mandates/sources of authority → rico:isOrWasRegulatedBy
 *   5.2.7 Internal structures        → rico:hasOrHadInternalStructure
 *   5.2.8 General context            → rico:generalContext
 *   5.3   Relationships              → rico:isOrWasRelatedTo / specific relation types
 *   5.4.1 Description identifier     → rico:identifier
 *   5.4.2 Institution identifier     → rico:isOrWasManagedBy
 *   5.4.3 Rules/conventions          → rico:rulesOrConventions
 *   5.4.8 Sources                    → rico:source
 *   5.4.9 Maintenance notes          → rico:descriptiveNote
 *
 * Person, CorporateBody, and Family services extend this base.
 */
class AgentService
{
    /**
     * Full ISAAR(CPF) → RiC-O field map.
     */
    public const FIELD_MAP = [
        // 5.1 Identity Area
        'authorized_form_of_name'   => ['property' => 'rico:hasOrHadAgentName', 'datatype' => 'xsd:string', 'qualifier' => 'authorized'],
        'parallel_name'             => ['property' => 'rico:hasOrHadAgentName', 'datatype' => 'xsd:string', 'qualifier' => 'parallel'],
        'standardized_name'         => ['property' => 'rico:hasOrHadAgentName', 'datatype' => 'xsd:string', 'qualifier' => 'standardized'],
        'other_form_of_name'        => ['property' => 'rico:hasOrHadAgentName', 'datatype' => 'xsd:string', 'qualifier' => 'other'],
        'identifier'                => ['property' => 'rico:identifier', 'datatype' => 'xsd:string'],
        'corporate_body_identifiers' => ['property' => 'rico:hasOrHadCorporateBodyIdentifier', 'datatype' => 'xsd:string'],

        // 5.2 Description Area
        'dates_of_existence'        => ['property' => 'rico:hasOrHadDemographicGroup', 'datatype' => 'xsd:string'],
        'date_of_birth'             => ['property' => 'rico:birthDate', 'datatype' => 'xsd:date'],
        'date_of_death'             => ['property' => 'rico:deathDate', 'datatype' => 'xsd:date'],
        'date_of_establishment'     => ['property' => 'rico:beginningDate', 'datatype' => 'xsd:date'],
        'date_of_termination'       => ['property' => 'rico:endDate', 'datatype' => 'xsd:date'],
        'history'                   => ['property' => 'rico:history', 'datatype' => 'xsd:string'],
        'places'                    => ['property' => 'rico:hasOrHadLocation', 'datatype' => 'xsd:string'],
        'legal_status'              => ['property' => 'rico:hasOrHadLegalStatus', 'datatype' => 'xsd:string'],
        'functions'                 => ['property' => 'rico:performsOrPerformed', 'datatype' => 'xsd:string'],
        'mandates'                  => ['property' => 'rico:isOrWasRegulatedBy', 'datatype' => 'xsd:string'],
        'internal_structures'       => ['property' => 'rico:hasOrHadInternalStructure', 'datatype' => 'xsd:string'],
        'general_context'           => ['property' => 'rico:generalContext', 'datatype' => 'xsd:string'],

        // 5.3 Relationships (text descriptions, linked agents use separate methods)
        'relationship_description'  => ['property' => 'rico:isOrWasRelatedTo', 'datatype' => 'xsd:string'],

        // 5.4 Control Area
        'description_identifier'    => ['property' => 'rico:descriptionsIdentifier', 'datatype' => 'xsd:string'],
        'institution_responsible_identifier' => ['property' => 'rico:isOrWasManagedBy', 'type' => 'uri'],
        'rules'                     => ['property' => 'rico:rulesOrConventions', 'datatype' => 'xsd:string'],
        'sources'                   => ['property' => 'rico:source', 'datatype' => 'xsd:string'],
        'revision_history'          => ['property' => 'rico:history', 'datatype' => 'xsd:string'],
        'maintenance_notes'         => ['property' => 'rico:descriptiveNote', 'datatype' => 'xsd:string'],
        'description_status'        => ['property' => 'rico:descriptionsStatus', 'datatype' => 'xsd:string'],
        'description_detail'        => ['property' => 'rico:descriptionsDetail', 'datatype' => 'xsd:string'],
        'source_standard'           => ['property' => 'rico:sourceStandard', 'datatype' => 'xsd:string'],
    ];

    /**
     * CamelCase → snake_case map from Heratio.
     */
    private const CAMEL_MAP = [
        'authorizedFormOfName' => 'authorized_form_of_name',
        'parallelName' => 'parallel_name',
        'standardizedName' => 'standardized_name',
        'otherFormOfName' => 'other_form_of_name',
        'corporateBodyIdentifiers' => 'corporate_body_identifiers',
        'datesOfExistence' => 'dates_of_existence',
        'dateOfBirth' => 'date_of_birth',
        'dateOfDeath' => 'date_of_death',
        'dateOfEstablishment' => 'date_of_establishment',
        'dateOfTermination' => 'date_of_termination',
        'legalStatus' => 'legal_status',
        'internalStructures' => 'internal_structures',
        'generalContext' => 'general_context',
        'descriptionIdentifier' => 'description_identifier',
        'institutionResponsibleIdentifier' => 'institution_responsible_identifier',
        'revisionHistory' => 'revision_history',
        'maintenanceNotes' => 'maintenance_notes',
        'descriptionStatus' => 'description_status',
        'descriptionDetail' => 'description_detail',
        'sourceStandard' => 'source_standard',
        'relationshipDescription' => 'relationship_description',
    ];

    public function __construct(
        protected readonly TriplestoreServiceInterface $triplestore,
    ) {}

    /**
     * Browse agents of a specific RDF type with filters.
     */
    public function browseByType(string $rdfType, array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $filterClauses = '';
        $bindings = [];

        if (!empty($filters['q'])) {
            $filterClauses .= "FILTER(CONTAINS(LCASE(STR(?name)), LCASE(?searchTerm)))\n";
            $bindings['searchTerm'] = $filters['q'];
        }

        if (!empty($filters['entity_type'])) {
            $filterClauses .= "?iri rico:hasAgentType ?entityTypeFilter .\n";
            $bindings['entityTypeFilter'] = $filters['entity_type'];
        }

        $sortField = match ($filters['sort'] ?? 'name') {
            'identifier' => '?identifier',
            'date' => '?datesOfExistence',
            default => '?name',
        };
        $sortDir = strtoupper($filters['direction'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $sparql = <<<SPARQL
            SELECT ?iri ?name ?identifier ?datesOfExistence ?entityType
                   ?history ?legalStatus ?places ?functions
            WHERE {
                ?iri a {$rdfType} .
                OPTIONAL { ?iri rico:hasOrHadAgentName ?nameNode .
                           ?nameNode rico:textualValue ?name .
                           OPTIONAL { ?nameNode rico:isOrWasNameType ?nameType }
                           FILTER(!BOUND(?nameType) || ?nameType = "authorized") }
                OPTIONAL { ?iri rico:title ?name }
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:hasOrHadDemographicGroup ?datesOfExistence }
                OPTIONAL { ?iri rico:hasAgentType ?entityType }
                OPTIONAL { ?iri rico:history ?history }
                OPTIONAL { ?iri rico:hasOrHadLegalStatus ?legalStatus }
                OPTIONAL { ?iri rico:hasOrHadLocation ?places }
                OPTIONAL { ?iri rico:performsOrPerformed ?functions }
                {$filterClauses}
            }
            ORDER BY {$sortDir}({$sortField})
            LIMIT {$limit} OFFSET {$offset}
            SPARQL;

        $items = $this->triplestore->select($sparql, $bindings);

        $countSparql = <<<SPARQL
            SELECT (COUNT(DISTINCT ?iri) AS ?count) WHERE {
                ?iri a {$rdfType} .
                {$filterClauses}
            }
            SPARQL;

        $countResult = $this->triplestore->select($countSparql, $bindings);
        $total = (int) ($countResult[0]['count']['value'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Find a single agent by IRI with all ISAAR(CPF) properties + relationships.
     */
    public function findAgent(string $iri): ?array
    {
        $entity = $this->triplestore->getEntity($iri);
        if ($entity === null) {
            return null;
        }

        // Enrich with relationship data
        $entity['other_names'] = $this->getOtherNames($iri);
        $entity['related_agents'] = $this->getRelatedAgents($iri);
        $entity['related_records'] = $this->getRelatedRecords($iri);
        $entity['related_functions'] = $this->getRelatedFunctions($iri);
        $entity['occupations'] = $this->getOccupations($iri);

        return $entity;
    }

    /**
     * Create an agent with all ISAAR(CPF) fields.
     */
    public function createAgent(string $rdfType, array $data, string $userId, string $reason): string
    {
        $data = $this->normalizeKeys($data);
        $properties = $this->mapFieldsToProperties($data);

        return $this->triplestore->createEntity($rdfType, $properties, $userId, $reason);
    }

    /**
     * Update an agent.
     */
    public function updateAgent(string $iri, array $data, string $userId, string $reason): bool
    {
        $data = $this->normalizeKeys($data);
        $properties = $this->mapFieldsToProperties($data);

        return $this->triplestore->updateEntity($iri, $properties, $userId, $reason);
    }

    /**
     * Delete an agent.
     */
    public function deleteAgent(string $iri, string $userId, string $reason): bool
    {
        return $this->triplestore->deleteEntity($iri, $userId, $reason);
    }

    // =========================================================================
    // Other Names (ISAAR 5.1.3-5.1.5) — from Heratio getOtherNames()
    // =========================================================================

    /**
     * Get all names (parallel, standardized, other forms) for an agent.
     */
    public function getOtherNames(string $agentIri): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?name ?nameType ?startDate ?endDate ?note
            WHERE {
                ?agent rico:hasOrHadAgentName ?nameNode .
                ?nameNode rico:textualValue ?name .
                OPTIONAL { ?nameNode rico:isOrWasNameType ?nameType }
                OPTIONAL { ?nameNode rico:hasBeginningDate ?startDate }
                OPTIONAL { ?nameNode rico:hasEndDate ?endDate }
                OPTIONAL { ?nameNode rico:descriptiveNote ?note }
            }
            ORDER BY ?nameType ?name
            SPARQL;

        return $this->triplestore->select($sparql, ['agent' => $agentIri]);
    }

    // =========================================================================
    // Related Agents (ISAAR 5.3) — from Heratio getRelatedActors()
    // =========================================================================

    /**
     * Get related authority records (agent-to-agent relationships).
     */
    public function getRelatedAgents(string $agentIri): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?relatedAgent ?name ?relationType ?startDate ?endDate ?description
            WHERE {
                {
                    ?agent rico:isOrWasRelatedTo ?relatedAgent .
                }
                UNION
                {
                    ?relatedAgent rico:isOrWasRelatedTo ?agent .
                }
                ?relatedAgent rico:title ?name .
                OPTIONAL { ?agent rico:hasRelationType ?relationType }
                OPTIONAL { ?agent rico:hasBeginningDate ?startDate }
                OPTIONAL { ?agent rico:hasEndDate ?endDate }
                OPTIONAL { ?agent rico:descriptiveNote ?description }
                FILTER(?relatedAgent != ?agent)
            }
            ORDER BY ?name
            SPARQL;

        return $this->triplestore->select($sparql, ['agent' => $agentIri]);
    }

    // =========================================================================
    // Related Records — from Heratio getRelatedResources()
    // =========================================================================

    /**
     * Get records related to this agent (via provenance or other relationships).
     */
    public function getRelatedRecords(string $agentIri, int $limit = 50): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?record ?title ?identifier ?relationType
            WHERE {
                ?record rico:hasProvenance ?agent .
                ?record rico:title ?title .
                OPTIONAL { ?record rico:identifier ?identifier }
                OPTIONAL { ?record rico:hasRelationType ?relationType }
            }
            ORDER BY ?title
            SPARQL;

        return $this->triplestore->select($sparql, ['agent' => $agentIri]);
    }

    // =========================================================================
    // Related Functions — from Heratio getRelatedFunctions()
    // =========================================================================

    /**
     * Get functions performed by this agent.
     */
    public function getRelatedFunctions(string $agentIri): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?function ?name
            WHERE {
                ?agent rico:performsOrPerformed ?function .
                ?function rico:title ?name .
            }
            ORDER BY ?name
            SPARQL;

        return $this->triplestore->select($sparql, ['agent' => $agentIri]);
    }

    // =========================================================================
    // Occupations — from Heratio getOccupations()
    // =========================================================================

    /**
     * Get occupations for this agent.
     */
    public function getOccupations(string $agentIri): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?occupation ?name ?note
            WHERE {
                ?agent rico:hasOrHadOccupation ?occupation .
                ?occupation rico:title ?name .
                OPTIONAL { ?occupation rico:descriptiveNote ?note }
            }
            ORDER BY ?name
            SPARQL;

        return $this->triplestore->select($sparql, ['agent' => $agentIri]);
    }

    // =========================================================================
    // Autocomplete — from Heratio ActorController
    // =========================================================================

    /**
     * Autocomplete search by name.
     */
    public function autocomplete(string $query, string $rdfType, int $limit = 10): array
    {
        $sparql = <<<SPARQL
            SELECT ?iri ?name ?identifier ?entityType
            WHERE {
                ?iri a {$rdfType} .
                {
                    ?iri rico:title ?name .
                }
                UNION
                {
                    ?iri rico:hasOrHadAgentName ?nameNode .
                    ?nameNode rico:textualValue ?name .
                }
                OPTIONAL { ?iri rico:identifier ?identifier }
                OPTIONAL { ?iri rico:hasAgentType ?entityType }
                FILTER(CONTAINS(LCASE(STR(?name)), LCASE(?searchTerm)))
            }
            ORDER BY ?name
            LIMIT {$limit}
            SPARQL;

        return $this->triplestore->select($sparql, ['searchTerm' => $query]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Map form fields to RiC-O properties for the triplestore.
     */
    protected function mapFieldsToProperties(array $data): array
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

        // Also map the name as rico:title for backwards compatibility
        if (!empty($data['authorized_form_of_name']) && !isset($properties['rico:title'])) {
            $properties['rico:title'] = ['value' => $data['authorized_form_of_name'], 'datatype' => 'xsd:string'];
        }

        return $properties;
    }

    /**
     * Normalize keys from camelCase to snake_case.
     */
    protected function normalizeKeys(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[self::CAMEL_MAP[$key] ?? $key] = $value;
        }
        return $normalized;
    }
}
