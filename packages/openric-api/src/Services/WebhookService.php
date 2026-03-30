<?php

declare(strict_types=1);

namespace OpenRic\Api\Services;

use OpenRic\Api\Contracts\WebhookServiceInterface;
use OpenRiC\Triplestore\Contracts\TriplestoreServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Service for sending notifications.
 * Implements the WebhookServiceInterface for proper dependency injection.
 */
class WebhookService implements WebhookServiceInterface
{
    public function __construct(
        private readonly TriplestoreServiceInterface $triplestore
    ) {}

    /**
     * Send a webhook notification.
     */
    public function send(string $event, array $data): bool
    {
        // Find registered webhooks for this event
        $webhooks = $this->findWebhooks($event);

        foreach ($webhooks as $webhook) {
            $this->deliverWebhook($webhook, $event, $data);
        }

        return true;
    }

    /**
     * Find webhooks registered for an event.
     */
    private function findWebhooks(string $event): array
    {
        $sparql = <<<SPARQL
SELECT ?webhook ?url ?secret ?active
WHERE {
    ?webhook a rico:Webhook .
    ?webhook rico:hasOrHadType "{$event}"@en .
    ?webhook rico:hasOrHadStatus "active"@en .
    ?webhook rico:hasTarget ?url .
    OPTIONAL { ?webhook rico:hasSecret ?secret }
}
SPARQL;

        return $this->triplestore->select($sparql);
    }

    /**
     * Deliver a webhook.
     */
    private function deliverWebhook(array $webhook, string $event, array $data): void
    {
        $url = $webhook['url']['value'] ?? $webhook['url'] ?? '';
        $secret = $webhook['secret']['value'] ?? $webhook['secret'] ?? null;

        try {
            $payload = [
                'event' => $event,
                'timestamp' => now()->toIso8601String(),
                'data' => $data,
            ];

            $headers = [
                'Content-Type' => 'application/json',
                'X-OpenRiC-Event' => $event,
            ];

            if ($secret) {
                $signature = hash_hmac('sha256', json_encode($payload), $secret);
                $headers['X-OpenRiC-Signature'] = $signature;
            }

            Http::withHeaders($headers)
                ->timeout(10)
                ->post($url, $payload);

            Log::channel('webhooks')->info('Webhook delivered', [
                'event' => $event,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            Log::channel('webhooks')->error('Webhook delivery failed', [
                'event' => $event,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register a new webhook.
     */
    public function register(array $data): string
    {
        $webhookIri = $this->triplestore->generateIri('Webhook');

        $triples = [
            ['subject' => $webhookIri, 'predicate' => 'a', 'object' => 'rico:Webhook'],
            ['subject' => $webhookIri, 'predicate' => 'rico:hasOrHadType', 'object' => $data['event'] . '@en'],
            ['subject' => $webhookIri, 'predicate' => 'rico:hasTarget', 'object' => $data['url']],
            ['subject' => $webhookIri, 'predicate' => 'rico:hasOrHadStatus', 'object' => 'active@en'],
            ['subject' => $webhookIri, 'predicate' => 'dcterms:created', 'object' => '"' . now()->toIso8601String() . '"^^xsd:dateTime'],
        ];

        if (!empty($data['secret'])) {
            $triples[] = ['subject' => $webhookIri, 'predicate' => 'rico:hasSecret', 'object' => hash('sha256', $data['secret'])];
        }

        $this->triplestore->insert($triples, 'system', 'Registered webhook');

        return $webhookIri;
    }
}
