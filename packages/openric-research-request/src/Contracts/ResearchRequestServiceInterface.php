<?php

declare(strict_types=1);

namespace OpenRiC\ResearchRequest\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for research request service.
 *
 * Adapted from Heratio AhgCart\Services\CartService (97 lines) + CartController checkout flow.
 * Replaces Heratio's e-commerce cart with a research-request workflow:
 * user adds entities to cart -> submits request with purpose -> admin approves/denies.
 */
interface ResearchRequestServiceInterface
{
    /**
     * Add an entity to the user's research cart.
     */
    public function addToCart(int $userId, string $entityIri, string $entityType, string $title): bool;

    /**
     * Remove a single item from the user's research cart.
     */
    public function removeFromCart(int $userId, int $cartItemId): bool;

    /**
     * Get all items in the user's cart.
     */
    public function getCart(int $userId): Collection;

    /**
     * Submit a research request from the user's cart items.
     *
     * @return string UUID of the created request.
     */
    public function submitRequest(int $userId, string $purpose, string $notes = ''): string;

    /**
     * Get paginated research requests, optionally filtered by status and user.
     *
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function getRequests(array $params = []): array;

    /**
     * Get a single research request by ID.
     */
    public function getRequest(int $requestId): ?object;

    /**
     * Approve a pending research request.
     */
    public function approveRequest(int $requestId, int $reviewerId, string $notes = ''): bool;

    /**
     * Deny a pending research request.
     */
    public function denyRequest(int $requestId, int $reviewerId, string $notes = ''): bool;

    /**
     * Get aggregate statistics for research requests.
     *
     * @return array{total: int, pending: int, approved: int, denied: int, completed: int}
     */
    public function getStats(): array;
}
