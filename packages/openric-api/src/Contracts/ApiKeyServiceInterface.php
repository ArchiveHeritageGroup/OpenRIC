<?php

declare(strict_types=1);

namespace OpenRic\Api\Contracts;

/**
 * Contract for API Key Service.
 * 
 * This interface defines the contract for API key management
 * and authentication operations.
 */
interface ApiKeyServiceInterface
{
    /**
     * Validate an API key.
     *
     * @param string $key The API key to validate
     * @return array|null Key data if valid, null otherwise
     */
    public function validateKey(string $key): ?array;

    /**
     * Create a new API key.
     *
     * @param string $name Key name/label
     * @param array $scopes Allowed scopes
     * @param int|null $userId Associated user ID
     * @return array Created key data including the key itself
     */
    public function createKey(string $name, array $scopes = [], ?int $userId = null): array;

    /**
     * Revoke an API key.
     *
     * @param string $key The key to revoke
     * @return bool Success status
     */
    public function revokeKey(string $key): bool;

    /**
     * Get API keys for a user.
     *
     * @param int $userId The user ID
     * @return array Array of key data (without the actual keys)
     */
    public function getKeysForUser(int $userId): array;

    /**
     * Check if key has a specific scope.
     *
     * @param string $key The API key
     * @param string $scope The scope to check
     * @return bool Whether the key has the scope
     */
    public function hasScope(string $key, string $scope): bool;

    /**
     * Log API key usage.
     *
     * @param string $key The API key used
     * @param string $endpoint The endpoint accessed
     * @param string $method HTTP method
     * @param int $statusCode Response status code
     * @return void
     */
    public function logUsage(string $key, string $endpoint, string $method, int $statusCode): void;

    /**
     * Get usage statistics for a key.
     *
     * @param string $key The API key
     * @param int $days Number of days to look back
     * @return array Usage statistics
     */
    public function getUsageStats(string $key, int $days = 30): array;
}
