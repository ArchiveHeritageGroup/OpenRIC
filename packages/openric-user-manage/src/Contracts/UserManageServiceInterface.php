<?php

declare(strict_types=1);

namespace OpenRiC\UserManage\Contracts;

/**
 * User management service interface.
 *
 * Adapted from Heratio ahg-user-manage (1,460 lines).
 * Enhanced admin UI for user management beyond basic auth.
 */
interface UserManageServiceInterface
{
    /**
     * Browse users with pagination, filtering, and sorting.
     *
     * @param  array{page?: int, limit?: int, sort?: string, sortDir?: string, search?: string, status?: string, role?: string} $params
     * @return array{users: array[], total: int, page: int, limit: int}
     */
    public function browseUsers(array $params = []): array;

    /**
     * Get full user detail including roles, groups, clearance level, and recent activity.
     */
    public function getUserDetail(int $userId): ?array;

    /**
     * Create a new user with roles and permissions.
     */
    public function createUser(array $data): int;

    /**
     * Update an existing user.
     */
    public function updateUser(int $userId, array $data): void;

    /**
     * Deactivate a user (soft disable, not delete).
     */
    public function deactivateUser(int $userId): void;

    /**
     * Reset a user's password and return the new temporary password.
     */
    public function resetPassword(int $userId): string;

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

    /**
     * Perform a bulk action on multiple users.
     *
     * @param  int[]  $userIds
     * @param  string $action activate|deactivate|delete|assign_role
     * @param  array  $params  extra params (e.g., role_id for assign_role)
     * @return array{affected: int, message: string}
     */
    public function bulkAction(array $userIds, string $action, array $params = []): array;

    /**
     * Get all available roles for assignment.
     *
     * @return array[]
     */
    public function getAvailableRoles(): array;
}
