<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Contracts;

use Illuminate\Support\Collection;

/**
 * Access Control List service interface.
 *
 * Adapted from Heratio AclService (498 lines).
 * Provides dual-layer permissions:
 *   1. Role-based: coarse-grained via roles table
 *   2. Object-level ACL: fine-grained via acl_groups + acl_object_permissions
 *
 * Also handles security classification checks and access requests.
 */
interface AclServiceInterface
{
    // ========================================================================
    // Permission checking
    // ========================================================================

    /**
     * Check if a user has permission to perform an action, optionally on a specific object.
     * Checks roles first, then ACL group permissions. Object-specific rules > general rules.
     * Deny always takes precedence over grant at the same specificity level.
     */
    public function check(int $userId, string $action, ?string $entityType = null, ?string $objectIri = null): bool;

    /**
     * Check if the currently authenticated user can admin.
     */
    public function canAdmin(?int $userId = null): bool;

    /**
     * Get all effective permissions for a user (role + ACL combined).
     *
     * @return array<string, string> permission name => label
     */
    public function getUserPermissions(int $userId): array;

    /**
     * Check if a user has a specific named permission.
     */
    public function hasPermission(int $userId, string $permission): bool;

    // ========================================================================
    // ACL group management
    // ========================================================================

    /**
     * Get all ACL groups with member counts.
     */
    public function getGroups(): Collection;

    /**
     * Get a single ACL group with its members and permissions.
     */
    public function getGroup(int $id): ?object;

    /**
     * Get all ACL permissions for a specific group.
     */
    public function getGroupPermissions(int $groupId): Collection;

    /**
     * Insert or update an ACL permission record.
     *
     * @return int The permission ID
     */
    public function savePermission(array $data): int;

    /**
     * Delete an ACL permission by ID.
     */
    public function deletePermission(int $id): bool;

    // ========================================================================
    // User-group membership
    // ========================================================================

    /**
     * Get all groups a user belongs to.
     */
    public function getUserGroups(int $userId): Collection;

    /**
     * Add a user to an ACL group.
     *
     * @return int The membership ID
     */
    public function addUserToGroup(int $userId, int $groupId): int;

    /**
     * Remove a user from an ACL group.
     */
    public function removeUserFromGroup(int $userId, int $groupId): bool;

    // ========================================================================
    // Security classification
    // ========================================================================

    /**
     * Get all active security classification levels.
     */
    public function getClassificationLevels(): Collection;

    /**
     * Get the security classification for a specific object (by IRI).
     */
    public function getObjectClassification(string $objectIri): ?object;

    /**
     * Set or update the security classification for an object.
     *
     * @return int The classification record ID
     */
    public function setObjectClassification(string $objectIri, int $classificationId, int $userId): int;

    /**
     * Get a user's current security clearance with classification details.
     */
    public function getUserClearance(int $userId): ?object;

    /**
     * Set or update a user's security clearance and log the change.
     */
    public function setUserClearance(int $userId, int $classificationId, int $grantedBy): void;

    // ========================================================================
    // Access requests
    // ========================================================================

    /**
     * Get security access requests, optionally filtered by status.
     */
    public function getAccessRequests(?string $status = 'pending'): Collection;

    /**
     * Approve a security access request.
     */
    public function approveAccessRequest(int $id, int $reviewerId, ?string $notes = null): bool;

    /**
     * Deny a security access request.
     */
    public function denyAccessRequest(int $id, int $reviewerId, ?string $notes = null): bool;

    // ========================================================================
    // Audit
    // ========================================================================

    /**
     * Get recent security audit log entries.
     */
    public function getSecurityAuditLog(int $limit = 50): Collection;

    /**
     * Get all users for dropdown/selection.
     */
    public function getAllUsers(): Collection;
}
