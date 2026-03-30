<?php

declare(strict_types=1);

namespace OpenRic\Api\Contracts;

/**
 * Contract for Webhook Service.
 * 
 * This interface defines the contract for webhook management
 * and event dispatching.
 */
interface WebhookServiceInterface
{
    /**
     * Register a new webhook.
     *
     * @param string $url The webhook URL
     * @param array $events Events to subscribe to
     * @param string|null $secret Optional signing secret
     * @param int|null $userId Associated user ID
     * @return array Created webhook data
     */
    public function register(string $url, array $events, ?string $secret = null, ?int $userId = null): array;

    /**
     * Unregister a webhook.
     *
     * @param int $webhookId The webhook ID
     * @return bool Success status
     */
    public function unregister(int $webhookId): bool;

    /**
     * Update webhook events.
     *
     * @param int $webhookId The webhook ID
     * @param array $events New events list
     * @return bool Success status
     */
    public function updateEvents(int $webhookId, array $events): bool;

    /**
     * Get webhooks for an event.
     *
     * @param string $event The event name
     * @return array Array of webhook URLs
     */
    public function getWebhooksForEvent(string $event): array;

    /**
     * Dispatch an event to all subscribed webhooks.
     *
     * @param string $event The event name
     * @param array $payload The event payload
     * @return array Results of dispatch
     */
    public function dispatch(string $event, array $payload): array;

    /**
     * Verify webhook signature.
     *
     * @param string $signature The signature header
     * @param string $payload The raw payload
     * @param string $secret The webhook secret
     * @return bool Whether the signature is valid
     */
    public function verifySignature(string $signature, string $payload, string $secret): bool;

    /**
     * Get webhook delivery logs.
     *
     * @param int $webhookId The webhook ID
     * @param int $limit Number of logs to retrieve
     * @return array Delivery logs
     */
    public function getDeliveryLogs(int $webhookId, int $limit = 50): array;

    /**
     * Retry a failed webhook delivery.
     *
     * @param int $logId The delivery log ID
     * @return bool Success status
     */
    public function retryDelivery(int $logId): bool;
}
