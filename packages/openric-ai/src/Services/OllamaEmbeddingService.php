<?php

declare(strict_types=1);

namespace OpenRiC\AI\Services;

use GuzzleHttp\Client;
use OpenRiC\AI\Contracts\EmbeddingServiceInterface;

class OllamaEmbeddingService implements EmbeddingServiceInterface
{
    private Client $ollamaClient;

    private Client $qdrantClient;

    private string $model;

    private string $collection;

    public function __construct()
    {
        $this->ollamaClient = new Client([
            'base_uri' => config('openric.ollama.endpoint', 'http://localhost:11434') . '/',
            'timeout' => 30,
        ]);

        $qdrantHost = config('openric.qdrant.host', 'localhost');
        $qdrantPort = config('openric.qdrant.port', 6333);
        $this->qdrantClient = new Client([
            'base_uri' => "http://{$qdrantHost}:{$qdrantPort}/",
            'timeout' => 10,
        ]);

        $this->model = config('openric.ollama.embedding_model', 'nomic-embed-text');
        $this->collection = config('openric.qdrant.collection', 'openric_entities');
    }

    public function generateEmbedding(string $text): array
    {
        $response = $this->ollamaClient->post('api/embeddings', [
            'json' => [
                'model' => $this->model,
                'prompt' => $text,
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return $result['embedding'] ?? [];
    }

    public function indexEntity(string $iri, string $text): bool
    {
        try {
            $embedding = $this->generateEmbedding($text);

            if (empty($embedding)) {
                return false;
            }

            $this->qdrantClient->put("collections/{$this->collection}/points", [
                'json' => [
                    'points' => [
                        [
                            'id' => crc32($iri),
                            'vector' => $embedding,
                            'payload' => [
                                'iri' => $iri,
                                'text' => mb_substr($text, 0, 1000),
                                'indexed_at' => now()->toISOString(),
                            ],
                        ],
                    ],
                ],
            ]);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function findSimilar(string $iri, int $limit = 10): array
    {
        try {
            $response = $this->qdrantClient->post("collections/{$this->collection}/points/recommend", [
                'json' => [
                    'positive' => [crc32($iri)],
                    'limit' => $limit,
                    'with_payload' => true,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return $result['result'] ?? [];
        } catch (\Exception) {
            return [];
        }
    }

    public function suggestDescription(string $title, string $context = ''): string
    {
        try {
            $prompt = "Based on the archival record title \"{$title}\"";
            if ($context !== '') {
                $prompt .= " and context: {$context}";
            }
            $prompt .= ', suggest a brief scope and content description for an archival finding aid (2-3 sentences):';

            $response = $this->ollamaClient->post('api/generate', [
                'json' => [
                    'model' => 'llama3',
                    'prompt' => $prompt,
                    'stream' => false,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return $result['response'] ?? '';
        } catch (\Exception) {
            return '';
        }
    }
}
