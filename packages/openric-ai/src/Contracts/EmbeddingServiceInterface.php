<?php

declare(strict_types=1);

namespace OpenRiC\AI\Contracts;

interface EmbeddingServiceInterface
{
    public function generateEmbedding(string $text): array;

    public function indexEntity(string $iri, string $text): bool;

    public function findSimilar(string $iri, int $limit = 10): array;

    public function suggestDescription(string $title, string $context = ''): string;
}
