<?php

declare(strict_types=1);

namespace OpenRiC\Provenance\Services;

use Illuminate\Support\Str;
use OpenRiC\Provenance\Contracts\ProvenanceServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Adapted from: /usr/share/nginx/heratio/packages/ahg-ric/tools/ric_provenance.py (454 lines)
 * Changes: PHP/Laravel, uses TriplestoreServiceInterface, OpenRiC base URIs, RDF-Star provenance.
 */
class ProvenanceService implements ProvenanceServiceInterface
{
    // Adapted from Heratio ACTIVITY_TYPES with icons and colors
    public const ACTIVITY_TYPES = [
        'Creation' => ['description' => 'The creation of a record or record set', 'icon' => 'bi-plus-circle', 'color' => '#28a745'],
        'Accumulation' => ['description' => 'The accumulation/collection of records over time', 'icon' => 'bi-collection', 'color' => '#17a2b8'],
        'Management' => ['description' => 'Custody, arrangement, or other management activities', 'icon' => 'bi-gear', 'color' => '#ffc107'],
        'Transfer' => ['description' => 'Transfer of custody or ownership', 'icon' => 'bi-arrow-left-right', 'color' => '#6f42c1'],
        'Modification' => ['description' => 'Modification, addition, or deletion of records', 'icon' => 'bi-pencil', 'color' => '#fd7e14'],
        'Description' => ['description' => 'Archival description or cataloguing', 'icon' => 'bi-card-text', 'color' => '#20c997'],
        'Digitization' => ['description' => 'Digital capture or conversion', 'icon' => 'bi-camera', 'color' => '#e83e8c'],
        'Preservation' => ['description' => 'Conservation or preservation actions', 'icon' => 'bi-shield-check', 'color' => '#6c757d'],
    ];

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    /**
     * Create activity — adapted from Heratio create_activity() Flask endpoint.
     * Builds a rico:Activity with date, participants, affected records, place, and provenance metadata.
     */
    public function createActivity(string $activityType, array $data, string $userId, string $reason): string
    {
        $baseUri = config('openric.base_uri', 'https://ric.theahg.co.za/entity');
        $activityIri = $baseUri . '/activity/' . Str::uuid()->toString();

        $properties = [
            'rico:hasActivityType' => ['value' => $activityType, 'datatype' => 'xsd:string'],
        ];

        if (! empty($data['description'])) {
            $properties['rico:descriptiveNote'] = ['value' => $data['description'], 'datatype' => 'xsd:string'];
        }

        // Create the activity entity
        $activityIri = $this->triplestore->createEntity('rico:Activity', $properties, $userId, $reason);

        // Add date information (DateRange with begin/end/expressed)
        if (! empty($data['date_start']) || ! empty($data['date_end']) || ! empty($data['date_expressed'])) {
            $dateIri = $baseUri . '/date/' . Str::uuid()->toString();
            $dateProps = [];

            if (! empty($data['date_expressed'])) {
                $dateProps['rico:expressedDate'] = ['value' => $data['date_expressed'], 'datatype' => 'xsd:string'];
            }
            if (! empty($data['date_start'])) {
                $dateProps['rico:hasBeginningDate'] = ['value' => $data['date_start'], 'datatype' => 'xsd:date'];
            }
            if (! empty($data['date_end'])) {
                $dateProps['rico:hasEndDate'] = ['value' => $data['date_end'], 'datatype' => 'xsd:date'];
            }

            $this->triplestore->createEntity('rico:DateRange', $dateProps, $userId, $reason);
            $this->triplestore->createRelationship($activityIri, 'rico:isOrWasAssociatedWithDate', $dateIri, $userId, $reason);
        }

        // Add participants (agents) with optional roles
        foreach ($data['participants'] ?? [] as $participant) {
            if (! empty($participant['uri'])) {
                $this->triplestore->createRelationship($activityIri, 'rico:hasOrHadParticipant', $participant['uri'], $userId, $reason);

                // Add role if specified (Heratio pattern: AgentRole entity)
                if (! empty($participant['role'])) {
                    $roleIri = $baseUri . '/agent-role/' . Str::uuid()->toString();
                    $this->triplestore->createEntity('rico:AgentRole', [
                        'rico:hasRoleType' => ['value' => $participant['role'], 'datatype' => 'xsd:string'],
                    ], $userId, $reason);
                    $this->triplestore->createRelationship($roleIri, 'rico:isOrWasAgentRoleOf', $participant['uri'], $userId, $reason);
                    $this->triplestore->createRelationship($activityIri, 'rico:hasOrHadAgentRole', $roleIri, $userId, $reason);
                }
            }
        }

        // Add affected/resulting records
        foreach ($data['records'] ?? [] as $record) {
            if (! empty($record['uri'])) {
                $relation = $record['relation'] ?? 'rico:resultsOrResultedIn';
                $this->triplestore->createRelationship($activityIri, $relation, $record['uri'], $userId, $reason);
            }
        }

        // Add entity_iri (single record link, simpler API)
        if (! empty($data['entity_iri'])) {
            $this->triplestore->createRelationship($activityIri, 'rico:resultsOrResultedIn', $data['entity_iri'], $userId, $reason);
        }

        // Add place
        if (! empty($data['place_uri'])) {
            $this->triplestore->createRelationship($activityIri, 'rico:hasOrHadLocation', $data['place_uri'], $userId, $reason);
        }

        return $activityIri;
    }

    /**
     * Get provenance timeline — adapted from Heratio get_record_timeline().
     * Returns activities linked to a record, ordered by date.
     */
    public function getTimeline(string $entityIri, int $limit = 50): array
    {
        $sparql = <<<'SPARQL'
            SELECT DISTINCT ?activity ?activityType ?description ?date ?dateStart ?dateEnd
                   ?participant ?participantLabel
            WHERE {
                { ?activity rico:resultsOrResultedIn ?entityIri }
                UNION { ?activity rico:resultsIn ?entityIri }
                UNION { ?entityIri rico:isOrWasAffectedBy ?activity }

                OPTIONAL { ?activity rico:hasActivityType ?activityType }
                OPTIONAL { ?activity rico:descriptiveNote ?description }
                OPTIONAL {
                    ?activity rico:isOrWasAssociatedWithDate ?dateNode .
                    OPTIONAL { ?dateNode rico:expressedDate ?date }
                    OPTIONAL { ?dateNode rico:hasBeginningDate ?dateStart }
                    OPTIONAL { ?dateNode rico:hasEndDate ?dateEnd }
                }
                OPTIONAL {
                    ?activity rico:hasOrHadParticipant ?participant .
                    OPTIONAL { ?participant rico:hasAgentName ?nameNode . ?nameNode rico:textualValue ?participantLabel }
                    OPTIONAL { ?participant rico:title ?participantLabel }
                }
            }
            ORDER BY ?dateStart ?date
            LIMIT ?limit
            SPARQL;

        $results = $this->triplestore->select($sparql, [
            'entityIri' => $entityIri,
            'limit' => (string) $limit,
        ]);

        // Deduplicate by activity URI (Heratio pattern)
        $seen = [];
        $timeline = [];

        foreach ($results as $row) {
            $uri = $row['activity']['value'] ?? '';
            if ($uri === '' || isset($seen[$uri])) {
                continue;
            }
            $seen[$uri] = true;

            $timeline[] = [
                'uri' => $uri,
                'type' => $row['activityType']['value'] ?? 'Activity',
                'description' => $row['description']['value'] ?? '',
                'date' => $row['date']['value'] ?? $row['dateStart']['value'] ?? '',
                'dateStart' => $row['dateStart']['value'] ?? '',
                'dateEnd' => $row['dateEnd']['value'] ?? '',
                'participant' => $row['participantLabel']['value'] ?? '',
            ];
        }

        return $timeline;
    }

    /**
     * Get custody chain — adapted from Heratio get_provenance_chain().
     * Shows custody/ownership history filtered to Creation, Transfer, Accumulation, Management.
     */
    public function getCustodyChain(string $entityIri): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?activity ?activityType ?date ?agent ?agentLabel ?place ?placeLabel
            WHERE {
                { ?activity rico:resultsOrResultedIn ?entityIri }
                UNION { ?activity rico:resultsIn ?entityIri }

                ?activity rico:hasActivityType ?activityType .
                FILTER(?activityType IN ("Creation", "Transfer", "Accumulation", "Management"))

                OPTIONAL {
                    ?activity rico:isOrWasAssociatedWithDate ?dateNode .
                    ?dateNode rico:hasBeginningDate ?date .
                }
                OPTIONAL {
                    ?activity rico:hasOrHadParticipant ?agent .
                    OPTIONAL { ?agent rico:hasAgentName ?nameNode . ?nameNode rico:textualValue ?agentLabel }
                    OPTIONAL { ?agent rico:title ?agentLabel }
                }
                OPTIONAL {
                    ?activity rico:hasOrHadLocation ?place .
                    OPTIONAL { ?place rico:hasPlaceName ?pNameNode . ?pNameNode rico:textualValue ?placeLabel }
                    OPTIONAL { ?place rico:title ?placeLabel }
                }
            }
            ORDER BY ?date
            LIMIT 100
            SPARQL;

        $results = $this->triplestore->select($sparql, ['entityIri' => $entityIri]);

        return array_map(fn ($row) => [
            'activity' => $row['activity']['value'] ?? '',
            'type' => $row['activityType']['value'] ?? '',
            'date' => $row['date']['value'] ?? '',
            'agent' => $row['agentLabel']['value'] ?? '',
            'agentUri' => $row['agent']['value'] ?? '',
            'place' => $row['placeLabel']['value'] ?? '',
        ], $results);
    }

    /**
     * Get activities for an entity — alias for getTimeline.
     */
    public function getActivitiesForEntity(string $entityIri, int $limit = 50): array
    {
        return $this->getTimeline($entityIri, $limit);
    }

    /**
     * Get activities for an agent — adapted from Heratio get_agent_activities().
     */
    public function getAgentActivities(string $agentIri, int $limit = 100): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?activity ?activityType ?description ?date ?record ?recordLabel
            WHERE {
                ?activity rico:hasOrHadParticipant ?agentIri .
                OPTIONAL { ?activity rico:hasActivityType ?activityType }
                OPTIONAL { ?activity rico:descriptiveNote ?description }
                OPTIONAL {
                    ?activity rico:isOrWasAssociatedWithDate ?dateNode .
                    ?dateNode rico:expressedDate ?date .
                }
                OPTIONAL {
                    { ?activity rico:resultsOrResultedIn ?record } UNION { ?activity rico:resultsIn ?record }
                    ?record rico:title ?recordLabel
                }
            }
            ORDER BY DESC(?date)
            LIMIT ?limit
            SPARQL;

        $results = $this->triplestore->select($sparql, [
            'agentIri' => $agentIri,
            'limit' => (string) $limit,
        ]);

        $seen = [];
        $activities = [];

        foreach ($results as $row) {
            $uri = $row['activity']['value'] ?? '';
            if ($uri === '' || isset($seen[$uri])) {
                continue;
            }
            $seen[$uri] = true;

            $activities[] = [
                'uri' => $uri,
                'type' => $row['activityType']['value'] ?? 'Activity',
                'description' => $row['description']['value'] ?? '',
                'date' => $row['date']['value'] ?? '',
                'record' => $row['recordLabel']['value'] ?? '',
            ];
        }

        return $activities;
    }

    /**
     * Create a description record per RiC-CM Section 6 and ICA/EGAD guidance.
     * A rico:Record with rico:describesOrDescribed linking to the described entity,
     * with rico:hasDocumentaryFormType FindingAid.
     */
    public function createDescriptionRecord(string $describedEntityIri, string $userId, string $reason): string
    {
        $findingAidType = config(
            'openric.provenance.documentary_form_types.finding_aid',
            'https://www.ica.org/standards/RiC/vocabularies/documentaryFormTypes#FindingAid'
        );

        $properties = [
            'rico:title' => ['value' => 'Description record', 'datatype' => 'xsd:string'],
            'rico:hasDocumentaryFormType' => ['value' => $findingAidType, 'type' => 'uri'],
            'rico:hasCreationDate' => ['value' => now()->toDateString(), 'datatype' => 'xsd:date'],
        ];

        $descriptionIri = $this->triplestore->createEntity('rico:Record', $properties, $userId, $reason);

        $this->triplestore->createRelationship(
            $descriptionIri,
            'rico:describesOrDescribed',
            $describedEntityIri,
            $userId,
            $reason
        );

        // Also add PROV-O provenance
        $this->triplestore->createRelationship($descriptionIri, 'prov:wasAttributedTo', $userId, $userId, $reason);

        return $descriptionIri;
    }

    /**
     * Get a specific activity's full details — adapted from Heratio get_activity().
     */
    public function getActivity(string $activityIri): ?array
    {
        $sparql = <<<'SPARQL'
            SELECT ?p ?o ?oLabel WHERE {
                ?activityIri ?p ?o .
                OPTIONAL { ?o rico:title ?oLabel }
                OPTIONAL { ?o rico:hasAgentName ?nameNode . ?nameNode rico:textualValue ?oLabel }
                OPTIONAL { ?o rico:expressedDate ?oLabel }
            }
            LIMIT 200
            SPARQL;

        $results = $this->triplestore->select($sparql, ['activityIri' => $activityIri]);

        if (empty($results)) {
            return null;
        }

        $activity = ['uri' => $activityIri, 'properties' => [], 'relations' => []];

        foreach ($results as $row) {
            $predicate = $row['p']['value'] ?? '';
            $shortPred = str_replace('https://www.ica.org/standards/RiC/ontology#', '', $predicate);
            $objectType = $row['o']['type'] ?? 'literal';

            if ($objectType === 'uri') {
                $activity['relations'][] = [
                    'predicate' => $shortPred,
                    'targetUri' => $row['o']['value'] ?? '',
                    'targetLabel' => $row['oLabel']['value'] ?? basename($row['o']['value'] ?? ''),
                ];
            } else {
                $activity['properties'][$shortPred] = $row['o']['value'] ?? '';
            }
        }

        return $activity;
    }

    /**
     * List all activities with optional filtering — adapted from Heratio list_activities().
     */
    public function listActivities(?string $activityType = null, ?string $recordIri = null, ?string $agentIri = null, int $limit = 200): array
    {
        $filters = [];
        $params = ['limit' => (string) $limit];

        if ($activityType !== null) {
            $filters[] = '?activity rico:hasActivityType ?filterType .';
            $params['filterType'] = $activityType;
        }
        if ($recordIri !== null) {
            $filters[] = '{ ?activity rico:resultsOrResultedIn ?filterRecord } UNION { ?activity rico:resultsIn ?filterRecord }';
            $params['filterRecord'] = $recordIri;
        }
        if ($agentIri !== null) {
            $filters[] = '?activity rico:hasOrHadParticipant ?filterAgent .';
            $params['filterAgent'] = $agentIri;
        }

        $filterClause = implode("\n", $filters);

        $sparql = <<<SPARQL
            SELECT DISTINCT ?activity ?activityType ?description ?date ?dateStart ?dateEnd
            WHERE {
                ?activity a rico:Activity .
                OPTIONAL { ?activity rico:hasActivityType ?activityType }
                OPTIONAL { ?activity rico:descriptiveNote ?description }
                OPTIONAL {
                    ?activity rico:isOrWasAssociatedWithDate ?dateNode .
                    OPTIONAL { ?dateNode rico:expressedDate ?date }
                    OPTIONAL { ?dateNode rico:hasBeginningDate ?dateStart }
                    OPTIONAL { ?dateNode rico:hasEndDate ?dateEnd }
                }
                {$filterClause}
            }
            ORDER BY DESC(?dateStart) DESC(?date)
            LIMIT ?limit
            SPARQL;

        $results = $this->triplestore->select($sparql, $params);

        $seen = [];
        $activities = [];

        foreach ($results as $row) {
            $uri = $row['activity']['value'] ?? '';
            if ($uri === '' || isset($seen[$uri])) {
                continue;
            }
            $seen[$uri] = true;

            $activities[] = [
                'uri' => $uri,
                'type' => $row['activityType']['value'] ?? 'Activity',
                'description' => $row['description']['value'] ?? '',
                'date' => $row['date']['value'] ?? $row['dateStart']['value'] ?? '',
                'dateStart' => $row['dateStart']['value'] ?? '',
                'dateEnd' => $row['dateEnd']['value'] ?? '',
            ];
        }

        return $activities;
    }

    public function getActivityTypes(): array
    {
        return self::ACTIVITY_TYPES;
    }
}
