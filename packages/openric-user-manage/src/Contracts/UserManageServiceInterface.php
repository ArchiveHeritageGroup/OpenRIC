<?php

declare(strict_types=1);

namespace OpenRiC\UserManage\Contracts;

/**
 * User management service interface.
 *
 * Adapted from Heratio ahg-user-manage UserService + UserBrowseService (full parity).
 * Covers: browse, CRUD, password management, ACL, clipboard, registration, API keys,
 * bulk actions, activity log, stats, security clearance, translate languages.
 */
interface UserManageServiceInterface
{
    // ── Browse & Search ────────────────────────────────────────────

    /**
     * Browse users with pagination, filtering, and sorting.
     *
     * @param  array{page?: int, limit?: int, sort?: string, sortDir?: string, search?: string, status?: string, role?: string} $params
     * @return array{users: array[], total: int, page: int, limit: int}
     */
    public function browseUsers(array $params = []): array;

    // ── Read ───────────────────────────────────────────────────────

    /**
     * Get full user detail by ID including roles, permissions, clearance, contact, translate languages, activity.
     */
    public function getUserDetail(int $userId): ?array;

    /**
     * Get full user detail by slug (resolves via slug table then delegates to getUserDetail).
     */
    public function getUserBySlug(string $slug): ?array;

    // ── Create / Update / Delete ──────────────────────────────────

    /**
     * Create a new user with roles, contact info, translate languages.
     */
    public function createUser(array $data): int;

    /**
     * Update an existing user.
     */
    public function updateUser(int $userId, array $data): void;

    /**
     * Delete a user and all related records (groups, permissions, properties, contact, slug, etc.).
     */
    public function deleteUser(int $userId): void;

    /**
     * Deactivate a user (soft disable, not delete).
     */
    public function deactivateUser(int $userId): void;

    /**
     * Activate a user.
     */
    public function activateUser(int $userId): void;

    // ── Password ──────────────────────────────────────────────────

    /**
     * Reset a user's password (admin). Returns the new temporary password.
     */
    public function resetPassword(int $userId): string;

    /**
     * Change password (self-service). Verifies current password first.
     *
     * @return bool true on success, false if current password is wrong
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool;

    // ── Roles & Groups ────────────────────────────────────────────

    /**
     * Get all available roles for assignment.
     *
     * @return array[]
     */
    public function getAvailableRoles(): array;

    /**
     * Get available languages configured for translation.
     *
     * @return string[]
     */
    public function getAvailableLanguages(): array;

    /**
     * Get translate languages for a specific user.
     *
     * @return string[]
     */
    public function getTranslateLanguages(int $userId): array;

    // ── ACL ───────────────────────────────────────────────────────

    /**
     * Build ACL matrix for a user for a given entity class.
     */
    public function buildAclData(int $userId, string $className): array;

    /**
     * Build edit ACL data for a user for a given entity class (user-specific permissions only).
     */
    public function buildEditAclData(int $userId, string $className): array;

    /**
     * Save ACL permission changes from form POST data.
     */
    public function saveAclPermissions(int $userId, array $permissionUpdates, array $newPermission = []): void;

    // ── Registration ──────────────────────────────────────────────

    /**
     * Get pending registration requests with optional status filter.
     */
    public function getRegistrationRequests(?string $statusFilter = null): array;

    /**
     * Approve a registration request — creates the user account.
     */
    public function approveRegistration(int $requestId, ?int $groupId, string $notes, int $adminId): array;

    /**
     * Reject a registration request.
     */
    public function rejectRegistration(int $requestId, string $notes, int $adminId): array;

    // ── API Keys ──────────────────────────────────────────────────

    /**
     * Get API keys (REST, OAI-PMH) for a user.
     *
     * @return array{rest: ?string, oai: ?string}
     */
    public function getApiKeys(int $userId): array;

    /**
     * Manage an API key (generate or delete).
     */
    public function manageApiKey(int $userId, string $keyType, string $action): ?string;

    // ── Clipboard ─────────────────────────────────────────────────

    /**
     * Get clipboard items for a user.
     */
    public function getClipboardItems(int $userId): array;

    // ── Activity & Stats ──────────────────────────────────────────

    /**
     * Get recent activity log for a user.
     *
     * @return array[]
     */
    public function getUserActivity(int $userId, int $limit = 50): array;

    /**
     * Get aggregate user statistics: total, active, inactive, by role.
     */
    public function getUserStats(): array;

    // ── Bulk Actions ──────────────────────────────────────────────

    /**
     * Perform a bulk action on multiple users.
     *
     * @param  int[]  $userIds
     * @param  string $action activate|deactivate|delete|assign_role|remove_role
     * @param  array  $params  extra params (e.g., role_id for assign_role)
     * @return array{affected: int, message: string}
     */
    public function bulkAction(array $userIds, string $action, array $params = []): array;

    // ── Security Clearance ────────────────────────────────────────

    /**
     * Get security clearance info for a user.
     */
    public function getSecurityClearance(int $userId): ?array;

    /**
     * Grant or update security clearance for a user.
     */
    public function grantSecurityClearance(int $userId, int $classificationId, ?string $expiresAt, string $notes): void;

    /**
     * Revoke security clearance for a user.
     */
    public function revokeSecurityClearance(int $userId): void;

    /**
     * Get all security classification levels.
     *
     * @return array[]
     */
    public function getSecurityClassifications(): array;

    // ── Slug Helper ───────────────────────────────────────────────

    /**
     * Get the slug for a user ID.
     */
    public function getSlug(int $userId): ?string;

    /**
     * Resolve a slug to a user ID.
     */
    public function resolveSlug(string $slug): ?int;
}
