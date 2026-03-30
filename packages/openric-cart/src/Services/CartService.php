<?php

declare(strict_types=1);

namespace OpenRic\Cart\Services;

use OpenRic\Cart\Contracts\CartServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;

/**
 * Cart Service for OpenRiC digital marketplace.
 * Manages shopping carts and orders using RiC-O data model.
 */
class CartService implements CartServiceInterface
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore
    ) {}

    /**
     * Get or create a cart for a user.
     */
    public function getCart(string $userIri): array
    {
        $sparql = <<<SPARQL
SELECT ?cart ?status ?created
WHERE {
    ?cart a rico:Cart .
    ?cart rico:belongsTo ?user .
    ?cart rico:hasOrHadStatus ?status .
    ?cart dcterms:created ?created .
    FILTER(?user = <{$userIri}>)
}
LIMIT 1
SPARQL;

        $results = $this->triplestore->select($sparql);

        if (empty($results)) {
            return $this->createCart($userIri);
        }

        // Get cart items
        $cartIri = $results[0]['cart']['value'] ?? $results[0]['cart'];
        return $this->getCartWithItems($cartIri);
    }

    /**
     * Create a new cart.
     */
    public function createCart(string $userIri): array
    {
        $cartIri = $this->triplestore->generateIri('Cart');

        $triples = [
            ['subject' => $cartIri, 'predicate' => 'a', 'object' => 'rico:Cart'],
            ['subject' => $cartIri, 'predicate' => 'rico:belongsTo', 'object' => $userIri],
            ['subject' => $cartIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'active@en'],
            ['subject' => $cartIri, 'predicate' => 'dcterms:created', 'object' => '"' . now()->toIso8601String() . '"^^xsd:dateTime'],
        ];

        $this->triplestore->insert($triples, $this->extractUserId($userIri), 'Created shopping cart');

        return ['iri' => $cartIri, 'items' => []];
    }

    /**
     * Add item to cart.
     */
    public function addItem(string $cartIri, string $itemIri, int $quantity = 1): bool
    {
        $itemLineIri = $this->triplestore->generateIri('CartItem');

        $triples = [
            ['subject' => $itemLineIri, 'predicate' => 'a', 'object' => 'rico:CartItem'],
            ['subject' => $itemLineIri, 'predicate' => 'rico:isOrWasRelatedTo', 'object' => $cartIri],
            ['subject' => $itemLineIri, 'predicate' => 'rico:isOrWasRelatedTo', 'object' => $itemIri],
            ['subject' => $itemLineIri, 'predicate' => 'rico:hasQuantity', 'object' => '"' . $quantity . '"^^xsd:integer'],
            ['subject' => $itemLineIri, 'predicate' => 'dcterms:created', 'object' => '"' . now()->toIso8601String() . '"^^xsd:dateTime'],
        ];

        $this->triplestore->insert($triples, 'system', 'Added item to cart');

        return true;
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(string $itemLineIri): bool
    {
        $this->triplestore->deleteEntity($itemLineIri, 'system', 'Removed item from cart');
        return true;
    }

    /**
     * Update item quantity.
     */
    public function updateQuantity(string $itemLineIri, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($itemLineIri);
        }

        $oldTriples = [];
        $newTriples = [
            'rico:hasQuantity' => '"' . $quantity . '"^^xsd:integer',
        ];

        $this->triplestore->update($itemLineIri, $oldTriples, $newTriples, 'system', 'Updated cart item quantity');
        return true;
    }

    /**
     * Get cart with items.
     */
    private function getCartWithItems(string $cartIri): array
    {
        $escapedCart = '<' . $cartIri . '>';

        $sparql = <<<SPARQL
SELECT ?item ?product ?quantity ?created
WHERE {
    ?item a rico:CartItem .
    ?item rico:isOrWasRelatedTo {$escapedCart} .
    ?item rico:isOrWasRelatedTo ?product .
    ?item rico:hasQuantity ?quantity .
    ?item dcterms:created ?created .
}
SPARQL;

        $items = $this->triplestore->select($sparql);

        return [
            'iri' => $cartIri,
            'items' => $items,
        ];
    }

    /**
     * Convert cart to order.
     */
    public function checkout(string $cartIri, array $paymentData): string
    {
        $orderIri = $this->triplestore->generateIri('Order');

        $triples = [
            ['subject' => $orderIri, 'predicate' => 'a', 'object' => 'rico:Order'],
            ['subject' => $orderIri, 'predicate' => 'rico:hasOrHadType', 'object' => 'purchase@en'],
            ['subject' => $orderIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'pending@en'],
            ['subject' => $orderIri, 'predicate' => 'dcterms:created', 'object' => '"' . now()->toIso8601String() . '"^^xsd:dateTime'],
        ];

        // Link to original cart
        $triples[] = ['subject' => $orderIri, 'predicate' => 'rico:isOrWasRelatedTo', 'object' => $cartIri];

        // Add payment info if provided
        if (!empty($paymentData['method'])) {
            $triples[] = ['subject' => $orderIri, 'predicate' => 'rico:hasPaymentMethod', 'object' => $paymentData['method'] . '@en'];
        }

        $this->triplestore->insert($triples, 'system', 'Created order from cart');

        // Mark cart as converted
        $oldStatus = [['subject' => $cartIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'active@en']];
        $newStatus = [['subject' => $cartIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'converted@en']];
        $this->triplestore->update($cartIri, $oldStatus, $newStatus, 'system', 'Cart converted to order');

        return $orderIri;
    }

    /**
     * Get user's orders.
     */
    public function getOrders(string $userIri): array
    {
        $sparql = <<<SPARQL
SELECT ?order ?status ?created
WHERE {
    ?order a rico:Order .
    ?order rico:isOrWasRelatedTo ?cart .
    ?cart rico:belongsTo ?user .
    ?order rico:hasOrHadStatus ?status .
    ?order dcterms:created ?created .
    FILTER(?user = <{$userIri}>)
}
ORDER BY DESC(?created)
SPARQL;

        return $this->triplestore->select($sparql);
    }

    /**
     * Get order details.
     */
    public function getOrder(string $orderIri): ?array
    {
        $escapedIri = '<' . $orderIri . '>';

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
            'iri' => $orderIri,
            'type' => null,
            'status' => null,
            'created' => null,
            'cart' => null,
            'paymentMethod' => null,
        ];

        foreach ($results as $row) {
            $predicate = $row['predicate']['value'] ?? $row['predicate'] ?? '';
            $object = $row['object']['value'] ?? $row['object'] ?? '';

            if (str_contains($predicate, 'hasOrHadType')) {
                $entity['type'] = $object;
            } elseif (str_contains($predicate, 'hasOrHadStatus')) {
                $entity['status'] = $object;
            } elseif (str_contains($predicate, 'created')) {
                $entity['created'] = $object;
            } elseif (str_contains($predicate, 'isOrWasRelatedTo') && str_contains($object, 'cart')) {
                $entity['cart'] = $object;
            } elseif (str_contains($predicate, 'hasPaymentMethod')) {
                $entity['paymentMethod'] = $object;
            }
        }

        return $entity;
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(string $orderIri, string $status, ?string $notes = null): bool
    {
        $oldTriples = [];
        $newTriples = [
            ['subject' => $orderIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => $status . '@en'],
        ];

        if ($notes) {
            $newTriples[] = ['subject' => $orderIri, 'predicate' => 'rico:hasOrHadNote', 'object' => $notes];
        }

        return $this->triplestore->update($orderIri, $oldTriples, $newTriples, 'system', 'Updated order status');
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
}
