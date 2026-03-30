<?php

declare(strict_types=1);

namespace OpenRic\Cart\Contracts;

/**
 * Contract for Cart Service.
 * 
 * This interface defines the contract for shopping cart and order operations
 * using the RiC-O data model via the triplestore.
 */
interface CartServiceInterface
{
    /**
     * Get or create a cart for a user.
     *
     * @param string $userIri The user's IRI
     * @return array Cart data with items
     */
    public function getCart(string $userIri): array;

    /**
     * Create a new cart.
     *
     * @param string $userIri The user's IRI
     * @return array Created cart data
     */
    public function createCart(string $userIri): array;

    /**
     * Add item to cart.
     *
     * @param string $cartIri The cart IRI
     * @param string $itemIri The item IRI to add
     * @param int $quantity Item quantity
     * @return bool Success status
     */
    public function addItem(string $cartIri, string $itemIri, int $quantity = 1): bool;

    /**
     * Remove item from cart.
     *
     * @param string $itemLineIri The cart item line IRI
     * @return bool Success status
     */
    public function removeItem(string $itemLineIri): bool;

    /**
     * Update item quantity.
     *
     * @param string $itemLineIri The cart item line IRI
     * @param int $quantity New quantity
     * @return bool Success status
     */
    public function updateQuantity(string $itemLineIri, int $quantity): bool;

    /**
     * Convert cart to order (checkout).
     *
     * @param string $cartIri The cart IRI
     * @param array $paymentData Payment information
     * @return string The IRI of the created order
     */
    public function checkout(string $cartIri, array $paymentData): string;

    /**
     * Get user's orders.
     *
     * @param string $userIri The user's IRI
     * @return array Array of order bindings
     */
    public function getOrders(string $userIri): array;

    /**
     * Get order details.
     *
     * @param string $orderIri The order IRI
     * @return array|null Order details or null
     */
    public function getOrder(string $orderIri): ?array;

    /**
     * Update order status.
     *
     * @param string $orderIri The order IRI
     * @param string $status New status
     * @param string|null $notes Optional notes
     * @return bool Success status
     */
    public function updateOrderStatus(string $orderIri, string $status, ?string $notes = null): bool;
}
