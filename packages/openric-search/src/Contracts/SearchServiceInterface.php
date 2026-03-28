<?php

declare(strict_types=1);

namespace OpenRiC\Search\Contracts;

interface SearchServiceInterface
{
    /**
     * Full-text search with fuzzy matching, wildcard, and phrase boosting.
     * Falls back to SPARQL when Elasticsearch returns no results.
     *
     * @param  array<string, mixed>  $filters  Optional filters (entity_type, date_from, date_to)
     * @return array{items: array, total: int, source: string, query_type: string, search_terms: array}
     */
    public function search(string $query, array $filters = [], int $limit = 25, int $offset = 0): array;

    /**
     * Find entities semantically similar to the given IRI via Qdrant vector search.
     *
     * @return array<int, array{iri: string, score: float, payload: array}>
     */
    public function similarTo(string $iri, int $limit = 10): array;

    /**
     * Autocomplete suggestions using Elasticsearch match_phrase_prefix.
     *
     * @return array<int, array{text: string, iri: string, type: string}>
     */
    public function suggest(string $query, int $limit = 10): array;

    /**
     * Index or re-index an entity in both Elasticsearch and Qdrant.
     *
     * @param  array<string, mixed>  $properties  RiC-O properties keyed by prefixed name
     */
    public function indexEntity(string $iri, array $properties): bool;

    /**
     * Remove an entity from both Elasticsearch and Qdrant indices.
     */
    public function deleteFromIndex(string $iri): bool;
}
