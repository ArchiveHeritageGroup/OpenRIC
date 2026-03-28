<?php

declare(strict_types=1);

namespace OpenRiC\Provenance\Services;

use OpenRiC\Provenance\Contracts\ProvenanceServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

class ProvenanceService implements ProvenanceServiceInterface
{
    public const ACTIVITY_TYPES = [
        'Creation' => 'The creation of a record',
        'Accumulation' => 'Accumulation over time',
        'Management' => 'Custody, arrangement',
        'Transfer' => 'Transfer of custody',
        'Modification' => 'Modification of records',
        'Description' => 'Archival description',
        'Digitization' => 'Digital capture',
        'Preservation' => 'Conservation',
    ];

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore,
    ) {}

    public function createActivity(string $activityType, array $data, string $userId, string $reason): string
    {
        $properties = [
            'rico:hasActivityType' => ['value' => $activityType, 'datatype' => 'xsd:string'],
        ];

        if (! empty($data['description'])) {
            $properties['rico:descriptiveNote'] = ['value' => $data['description'], 'datatype' => 'xsd:string'];
        }

        $activityIri = $this->triplestore->createEntity('rico:Activity', $properties, $userId, $reason);

        if (! empty($data['entity_iri'])) {
            $this->triplestore->createRelationship(
                $activityIri,
                'rico:resultsOrResultedIn',
                $data['entity_iri'],
                $userId,
                $reason
            );
        }

        if (! empty($data['participant_iri'])) {
            $this->triplestore->createRelationship(
                $activityIri,
                'rico:hasOrHadParticipant',
                $data['participant_iri'],
                $userId,
                $reason
            );
        }

        return $activityIri;
    }

    public function getTimeline(string $entityIri, int $limit = 50): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?activity ?activityType ?date ?description WHERE {
                ?activity rico:resultsOrResultedIn ?entityIri .
                ?activity a rico:Activity .
                OPTIONAL { ?activity rico:hasActivityType ?activityType }
                OPTIONAL { ?activity rico:isOrWasAssociatedWithDate ?dateNode . ?dateNode rico:expressedDate ?date }
                OPTIONAL { ?activity rico:descriptiveNote ?description }
            }
            ORDER BY DESC(?date)
            LIMIT ?limit
            SPARQL;

        return $this->triplestore->select($sparql, [
            'entityIri' => $entityIri,
            'limit' => (string) $limit,
        ]);
    }

    public function getCustodyChain(string $entityIri): array
    {
        $sparql = <<<'SPARQL'
            SELECT ?activity ?holder ?holderName ?date WHERE {
                ?activity rico:resultsOrResultedIn ?entityIri .
                ?activity rico:hasActivityType "Transfer" .
                OPTIONAL { ?activity rico:hasOrHadParticipant ?holder . ?holder rico:title ?holderName }
                OPTIONAL { ?activity rico:isOrWasAssociatedWithDate ?dateNode . ?dateNode rico:expressedDate ?date }
            }
            ORDER BY ?date
            LIMIT 100
            SPARQL;

        return $this->triplestore->select($sparql, ['entityIri' => $entityIri]);
    }

    public function getActivitiesForEntity(string $entityIri, int $limit = 50): array
    {
        return $this->getTimeline($entityIri, $limit);
    }

    /**
     * Per RiC-CM 1.0 Section 6 and ICA/EGAD guidance:
     * Create a rico:Record with rico:describesOrDescribed linking to the entity,
     * with rico:hasDocumentaryFormType FindingAid.
     */
    public function createDescriptionRecord(string $describedEntityIri, string $userId, string $reason): string
    {
        $findingAidType = config('openric.provenance.documentary_form_types.finding_aid',
            'https://www.ica.org/standards/RiC/vocabularies/documentaryFormTypes#FindingAid');

        $properties = [
            'rico:title' => ['value' => 'Description record', 'datatype' => 'xsd:string'],
            'rico:hasDocumentaryFormType' => ['value' => $findingAidType, 'type' => 'uri'],
        ];

        $descriptionIri = $this->triplestore->createEntity('rico:Record', $properties, $userId, $reason);

        $this->triplestore->createRelationship(
            $descriptionIri,
            'rico:describesOrDescribed',
            $describedEntityIri,
            $userId,
            $reason
        );

        return $descriptionIri;
    }

    public function getActivityTypes(): array
    {
        return self::ACTIVITY_TYPES;
    }
}
