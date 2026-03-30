<?php

declare(strict_types=1);

namespace OpenRic\AccessRequest\Services;

use OpenRic\AccessRequest\Contracts\AccessRequestServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Access Request Service for OpenRiC using RiC-O data model.
 * 
 * Adapts relational DB queries to SPARQL queries against Apache Jena Fuseki.
 * Uses rico:Activity to represent access request activities.
 */
class AccessRequestService implements AccessRequestServiceInterface
{
    private const ACCESS_REQUEST_TYPE = 'AccessRequest';

    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore
    ) {}

    /**
     * Browse all access requests (admin view).
     * Maps to SPARQL SELECT for all rico:AccessRequest instances.
     */
    public function getAllRequests(int $perPage = 25): array
    {
        $offset = 0; // Pagination handled at controller level for now
        $limit = $perPage;

        $sparql = <<<SPARQL
SELECT ?request ?type ?status ?createdAt ?userName ?userEmail
WHERE {
    ?request a rico:AccessRequest .
    ?request rico:hasOrHadType ?type .
    ?request rico:hasOrHadStatus ?status .
    ?request dcterms:created ?createdAt .
    OPTIONAL { ?request rico:hasOrHadCreator ?creator . ?creator rico:hasOrHadName ?userName }
    OPTIONAL { ?request rico:hasOrHadContributor ?contributor . ?contributor rico:hasOrHadEmail ?userEmail }
}
ORDER BY DESC(?createdAt)
LIMIT {$limit}
OFFSET {$offset}
SPARQL;

        return $this->triplestore->select($sparql);
    }

    /**
     * Get pending access requests for admins/approvers.
     */
    public function getPendingRequests(int $perPage = 25): array
    {
        $offset = 0;
        $limit = $perPage;

        $sparql = <<<SPARQL
SELECT ?request ?type ?status ?createdAt ?userName
WHERE {
    ?request a rico:AccessRequest .
    ?request rico:hasOrHadStatus "pending"@en .
    ?request dcterms:created ?createdAt .
    OPTIONAL { ?request rico:hasOrHadCreator ?creator . ?creator rico:hasOrHadName ?userName }
}
ORDER BY DESC(?createdAt)
LIMIT {$limit}
OFFSET {$offset}
SPARQL;

        return $this->triplestore->select($sparql);
    }

    /**
     * Get requests for the current authenticated user.
     */
    public function getMyRequests(string $userIri, int $perPage = 25): array
    {
        $offset = 0;
        $limit = $perPage;
        $escapedUserIri = $this->escapeIri($userIri);

        $sparql = <<<SPARQL
SELECT ?request ?type ?status ?createdAt
WHERE {
    ?request a rico:AccessRequest .
    ?request rico:hasOrHadStatus ?status .
    ?request rico:hasOrHadCreator {$escapedUserIri} .
    ?request dcterms:created ?createdAt .
}
ORDER BY DESC(?createdAt)
LIMIT {$limit}
OFFSET {$offset}
SPARQL;

        return $this->triplestore->select($sparql);
    }

    /**
     * Get a single access request by IRI.
     */
    public function getRequest(string $requestIri): ?array
    {
        $escapedIri = $this->escapeIri($requestIri);

        $sparql = <<<SPARQL
SELECT ?predicate ?object
WHERE {
    {$escapedIri} ?predicate ?object .
}
LIMIT 500
SPARQL;

        $results = $this->triplestore->select($sparql);

        if (empty($results)) {
            return null;
        }

        // Convert bindings to entity format
        $entity = [
            'iri' => $requestIri,
            'type' => null,
            'status' => null,
            'createdAt' => null,
            'creator' => null,
            'description' => null,
            'reason' => null,
        ];

        foreach ($results as $row) {
            $predicate = $row['predicate']['value'] ?? $row['predicate'] ?? '';
            $object = $row['object']['value'] ?? $row['object'] ?? '';

            // Map predicates to entity properties
            if (str_contains($predicate, 'hasOrHadType') || str_contains($predicate, 'type')) {
                $entity['type'] = $object;
            } elseif (str_contains($predicate, 'hasOrHadStatus') || str_contains($predicate, 'status')) {
                $entity['status'] = $object;
            } elseif (str_contains($predicate, 'created')) {
                $entity['createdAt'] = $object;
            } elseif (str_contains($predicate, 'hasOrHadCreator') || str_contains($predicate, 'creator')) {
                $entity['creator'] = $object;
            } elseif (str_contains($predicate, 'description')) {
                $entity['description'] = $object;
            } elseif (str_contains($predicate, 'reason')) {
                $entity['reason'] = $object;
            }
        }

        return $entity;
    }

    /**
     * Get configured approvers.
     */
    public function getApprovers(): array
    {
        $sparql = <<<SPARQL
SELECT ?user ?name ?email
WHERE {
    ?user a rico:Agent .
    ?user rico:hasOrHadRole ?role .
    FILTER(CONTAINS(LCASE(?role), "approver") || CONTAINS(LCASE(?role), "access"))
    OPTIONAL { ?user rico:hasOrHadName ?name }
    OPTIONAL { ?user rico:hasOrHadEmail ?email }
}
ORDER BY ?name
LIMIT 100
SPARQL;

        return $this->triplestore->select($sparql);
    }

    /**
     * Create a new access request.
     */
    public function createRequest(string $userIri, array $data): string
    {
        $requestIri = $this->triplestore->generateIri(self::ACCESS_REQUEST_TYPE);
        $now = (new \DateTime())->format(\DateTime::ATOM);

        $triples = [
            [
                'subject' => $requestIri,
                'predicate' => 'a',
                'object' => 'rico:AccessRequest',
            ],
            [
                'subject' => $requestIri,
                'predicate' => 'rico:hasOrHadType',
                'object' => $data['request_type'] ?? 'access',
            ],
            [
                'subject' => $requestIri,
                'predicate' => 'rico:hasOrHadStatus',
                'object' => 'pending@en',
            ],
            [
                'subject' => $requestIri,
                'predicate' => 'rico:hasOrHadCreator',
                'object' => $userIri,
            ],
            [
                'subject' => $requestIri,
                'predicate' => 'dcterms:description',
                'object' => $data['description'] ?? '',
            ],
            [
                'subject' => $requestIri,
                'predicate' => 'rico:hasOrHadJustification',
                'object' => $data['justification'] ?? '',
            ],
            [
                'subject' => $requestIri,
                'predicate' => 'dcterms:created',
                'object' => '"' . $now . '"^^xsd:dateTime',
            ],
        ];

        // Add subject if provided
        if (!empty($data['subject'])) {
            $triples[] = [
                'subject' => $requestIri,
                'predicate' => 'dcterms:title',
                'object' => $data['subject'],
            ];
        }

        // Add object reference if provided
        if (!empty($data['object_id'])) {
            $triples[] = [
                'subject' => $requestIri,
                'predicate' => 'rico:isOrWasRelatedTo',
                'object' => $data['object_id'],
            ];
        }

        $this->triplestore->insert($triples, $this->extractUserId($userIri), 'Created access request');

        return $requestIri;
    }

    /**
     * Approve an access request.
     */
    public function approveRequest(string $requestIri, string $reviewerIri, ?string $notes = null): bool
    {
        $oldTriples = [
            ['subject' => $requestIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'pending@en'],
        ];

        $newTriples = [
            ['subject' => $requestIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'approved@en'],
            ['subject' => $requestIri, 'predicate' => 'rico:wasReviewedBy', 'object' => $reviewerIri],
        ];

        if ($notes) {
            $newTriples[] = ['subject' => $requestIri, 'predicate' => 'rico:hasOrHadNote', 'object' => $notes];
        }

        return $this->triplestore->update($requestIri, $oldTriples, $newTriples, $this->extractUserId($reviewerIri), 'Approved access request');
    }

    /**
     * Deny an access request.
     */
    public function denyRequest(string $requestIri, string $reviewerIri, ?string $reason = null): bool
    {
        $oldTriples = [
            ['subject' => $requestIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'pending@en'],
        ];

        $newTriples = [
            ['subject' => $requestIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'denied@en'],
            ['subject' => $requestIri, 'predicate' => 'rico:wasReviewedBy', 'object' => $reviewerIri],
        ];

        if ($reason) {
            $newTriples[] = ['subject' => $requestIri, 'predicate' => 'rico:hasOrHadNote', 'object' => $reason];
        }

        return $this->triplestore->update($requestIri, $oldTriples, $newTriples, $this->extractUserId($reviewerIri), 'Denied access request');
    }

    /**
     * Add an approver role to a user.
     */
    public function addApprover(string $userIri): string
    {
        $approverIri = $this->triplestore->generateIri('AccessApprover');

        $triples = [
            ['subject' => $approverIri, 'predicate' => 'a', 'object' => 'rico:AccessApprover'],
            ['subject' => $approverIri, 'predicate' => 'rico:hasOrHadRole', 'object' => 'access-approver@en'],
            ['subject' => $approverIri, 'predicate' => 'rico:isOrWasRelatedTo', 'object' => $userIri],
            ['subject' => $approverIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'active@en'],
            ['subject' => $approverIri, 'predicate' => 'dcterms:created', 'object' => '"' . (new \DateTime())->format(\DateTime::ATOM) . '"^^xsd:dateTime'],
        ];

        $this->triplestore->insert($triples, 'system', 'Added access approver');

        return $approverIri;
    }

    /**
     * Remove an approver (deactivate).
     */
    public function removeApprover(string $approverIri): bool
    {
        $oldTriples = [
            ['subject' => $approverIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'active@en'],
        ];

        $newTriples = [
            ['subject' => $approverIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'inactive@en'],
        ];

        return $this->triplestore->update($approverIri, $oldTriples, $newTriples, 'system', 'Deactivated access approver');
    }

    /**
     * Cancel an access request (by the requesting user).
     */
    public function cancelRequest(string $requestIri, string $userIri): bool
    {
        $oldTriples = [
            ['subject' => $requestIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'pending@en'],
        ];

        $newTriples = [
            ['subject' => $requestIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'cancelled@en'],
        ];

        return $this->triplestore->update($requestIri, $oldTriples, $newTriples, $this->extractUserId($userIri), 'Cancelled access request');
    }

    /**
     * Extract user ID from IRI.
     */
    private function extractUserId(string $iri): string
    {
        if (preg_match('/\/user\/([^\/]+)$/', $iri, $matches)) {
            return $matches[1];
        }
        return 'unknown';
    }

    /**
     * Escape IRI for SPARQL.
     */
    private function escapeIri(string $iri): string
    {
        if (str_starts_with($iri, '<')) {
            return $iri;
        }
        return '<' . $iri . '>';
    }
}
