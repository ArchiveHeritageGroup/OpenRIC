<?php

declare(strict_types=1);

namespace OpenRiC\Search\Contracts;

interface SearchServiceInterface
{
    /**
     * @return array{items: array, total: int, facets: array}
     */
    public function search(string $query, array $filters = [], int $limit = 25, int $offset = 0): array;

    public function similarTo(string $iri, int $limit = 10): array;

    public function suggest(string $query, int $limit = 5): array;

    public function indexEntity(string $iri, array $properties): bool;

    public function deleteFromIndex(string $iri): bool;
}
