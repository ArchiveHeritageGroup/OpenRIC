<?php

declare(strict_types=1);

namespace OpenRic\AccessRequest\Contracts;

/**
 * Contract for Access Request Service.
 * 
 * This interface defines the contract for access request operations
 * using the RiC-O data model via the triplestore.
 */
interface AccessRequestServiceInterface
{
    /**
     * Browse all access requests (admin view).
     *
     * @param int $perPage Number of requests per page
     * @return array Array of access request bindings
     */
    public function getAllRequests(int $perPage = 25): array;

    /**
     * Get pending access requests for admins/approvers.
     *
     * @param int $perPage Number of requests per page
     * @return array Array of pending request bindings
     */
    public function getPendingRequests(int $perPage = 25): array;

    /**
     * Get requests for the current authenticated user.
     *
     * @param string $userIri The user's IRI
     * @param int $perPage Number of requests per page
     * @return array Array of user's request bindings
     */
    public function getMyRequests(string $userIri, int $perPage = 25): array;

    /**
     * Get a single access request by IRI.
     *
     * @param string $requestIri The request IRI
     * @return array|null The access request entity or null
     */
    public function getRequest(string $requestIri): ?array;

    /**
     * Get configured approvers.
     *
     * @return array Array of approver bindings
     */
    public function getApprovers(): array;

    /**
     * Create a new access request.
     *
     * @param string $userIri The requesting user's IRI
     * @param array $data Request data (subject, description, justification, etc.)
     * @return string The IRI of the created request
     */
    public function createRequest(string $userIri, array $data): string;

    /**
     * Approve an access request.
     *
     * @param string $requestIri The request IRI
     * @param string $reviewerIri The reviewer's IRI
     * @param string|null $notes Optional review notes
     * @return bool Success status
     */
    public function approveRequest(string $requestIri, string $reviewerIri, ?string $notes = null): bool;

    /**
     * Deny an access request.
     *
     * @param string $requestIri The request IRI
     * @param string $reviewerIri The reviewer's IRI
     * @param string|null $reason Optional denial reason
     * @return bool Success status
     */
    public function denyRequest(string $requestIri, string $reviewerIri, ?string $reason = null): bool;

    /**
     * Add an approver role to a user.
     *
     * @param string $userIri The user's IRI
     * @return string The IRI of the created approver assignment
     */
    public function addApprover(string $userIri): string;

    /**
     * Remove an approver (deactivate).
     *
     * @param string $approverIri The approver assignment IRI
     * @return bool Success status
     */
    public function removeApprover(string $approverIri): bool;

    /**
     * Cancel an access request (by the requesting user).
     *
     * @param string $requestIri The request IRI
     * @param string $userIri The user's IRI
     * @return bool Success status
     */
    public function cancelRequest(string $requestIri, string $userIri): bool;
}
