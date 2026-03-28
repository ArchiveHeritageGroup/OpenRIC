<?php

declare(strict_types=1);

namespace OpenRiC\Triplestore\Contracts;

use OpenRiC\Triplestore\Exceptions\TriplestoreException;

/**
 * Abstraction layer for all triplestore operations.
 *
 * Every method that writes data includes RDF-Star provenance annotation.
 * Every SPARQL query is parameterised — user input is never string-interpolated.
 * Every query includes a LIMIT clause to prevent unbounded results.
 */
interface TriplestoreServiceInterface
{
    // =========================================================================
    // Query Methods
    // =========================================================================

    /**
     * Execute a SPARQL SELECT query and return the result bindings.
     *
     * @param  string               $sparql  SPARQL SELECT query with ?VAR placeholders
     * @param  array<string, mixed> $params  parameter bindings — IRIs wrapped in <>, literals escaped
     * @return array<int, array<string, mixed>>  array of result rows
     *
     * @throws TriplestoreException on HTTP or parse errors
     */
    public function select(string $sparql, array $params = []): array;

    /**
     * Execute a SPARQL ASK query and return a boolean result.
     *
     * @param  string               $sparql  SPARQL ASK query with ?VAR placeholders
     * @param  array<string, mixed> $params  parameter bindings
     *
     * @throws TriplestoreException on HTTP or parse errors
     */
    public function ask(string $sparql, array $params = []): bool;

    /**
     * Execute a SPARQL CONSTRUCT query and return the resulting triples.
     *
     * @param  string               $sparql  SPARQL CONSTRUCT query with ?VAR placeholders
     * @param  array<string, mixed> $params  parameter bindings
     * @return array<int, array<string, string>>  array of triple arrays with 'subject', 'predicate', 'object'
     *
     * @throws TriplestoreException on HTTP or parse errors
     */
    public function construct(string $sparql, array $params = []): array;

    /**
     * Retrieve all triples for a given IRI via SPARQL DESCRIBE.
     *
     * @param  string $iri  the IRI to describe
     * @return array<int, array<string, string>>  array of triple arrays
     *
     * @throws TriplestoreException on HTTP or parse errors
     */
    public function describe(string $iri): array;

    // =========================================================================
    // Write Methods (all include RDF-Star provenance)
    // =========================================================================

    /**
     * Insert triples into the triplestore with RDF-Star provenance.
     *
     * @param  array<int, array<string, string>> $triples  array of ['subject' => ..., 'predicate' => ..., 'object' => ...]
     * @param  string $userId  user identifier for provenance
     * @param  string $reason  human-readable change reason
     *
     * @throws TriplestoreException on HTTP errors
     */
    public function insert(array $triples, string $userId, string $reason): bool;

    /**
     * Update triples: delete old triples and insert new ones, with provenance.
     *
     * @param  string $subjectIri  the subject IRI being updated
     * @param  array<int, array<string, string>> $oldTriples  triples to remove
     * @param  array<int, array<string, string>> $newTriples  triples to add
     * @param  string $userId  user identifier for provenance
     * @param  string $reason  human-readable change reason
     *
     * @throws TriplestoreException on HTTP errors
     */
    public function update(string $subjectIri, array $oldTriples, array $newTriples, string $userId, string $reason): bool;

    /**
     * Delete all triples for a subject IRI, with provenance annotation.
     *
     * @param  string $subjectIri  the subject IRI to delete
     * @param  string $userId  user identifier for provenance
     * @param  string $reason  human-readable change reason
     *
     * @throws TriplestoreException on HTTP errors
     */
    public function delete(string $subjectIri, string $userId, string $reason): bool;

    /**
     * Delete specific triples from the triplestore, with provenance.
     *
     * @param  array<int, array<string, string>> $triples  triples to remove
     * @param  string $userId  user identifier for provenance
     * @param  string $reason  human-readable change reason
     *
     * @throws TriplestoreException on HTTP errors
     */
    public function deleteTriples(array $triples, string $userId, string $reason): bool;

    // =========================================================================
    // Entity Methods
    // =========================================================================

    /**
     * Create a new entity in the triplestore and return its IRI.
     *
     * @param  string               $type        RiC-O class name (e.g. 'RecordSet', 'Person')
     * @param  array<string, mixed> $properties  property => value pairs
     * @param  string               $userId      user identifier for provenance
     * @param  string               $reason      human-readable change reason
     * @return string  the generated entity IRI
     *
     * @throws TriplestoreException on HTTP errors
     */
    public function createEntity(string $type, array $properties, string $userId, string $reason): string;

    /**
     * Retrieve an entity by IRI, returning all its properties.
     *
     * @param  string $iri  the entity IRI
     * @return array<string, mixed>|null  property map or null if not found
     *
     * @throws TriplestoreException on HTTP errors
     */
    public function getEntity(string $iri): ?array;

    /**
     * Update an entity's properties, with provenance.
     *
     * @param  string               $iri         the entity IRI
     * @param  array<string, mixed> $properties  property => value pairs to set
     * @param  string               $userId      user identifier for provenance
     * @param  string               $reason      human-readable change reason
     *
     * @throws TriplestoreException on HTTP errors
     */
    public function updateEntity(string $iri, array $properties, string $userId, string $reason): bool;

    /**
     * Delete an entity and all its triples, with provenance.
     *
     * @param  string $iri     the entity IRI
     * @param  string $userId  user identifier for provenance
     * @param  string $reason  human-readable change reason
     *
     * @throws TriplestoreException on HTTP errors
     */
    public function deleteEntity(string $iri, string $userId, string $reason): bool;

    // =========================================================================
    // Relationship Methods
    // =========================================================================

    /**
     * Create a relationship (triple) between two entities, with provenance.
     *
     * @param  string $subject    subject IRI
     * @param  string $predicate  predicate IRI or prefixed name
     * @param  string $object     object IRI
     * @param  string $userId     user identifier for provenance
     * @param  string $reason     human-readable change reason
     *
     * @throws TriplestoreException on HTTP errors
     */
    public function createRelationship(string $subject, string $predicate, string $object, string $userId, string $reason): bool;

    /**
     * Delete a specific relationship (triple), with provenance.
     *
     * @param  string $subject    subject IRI
     * @param  string $predicate  predicate IRI or prefixed name
     * @param  string $object     object IRI
     * @param  string $userId     user identifier for provenance
     * @param  string $reason     human-readable change reason
     *
     * @throws TriplestoreException on HTTP errors
     */
    public function deleteRelationship(string $subject, string $predicate, string $object, string $userId, string $reason): bool;

    /**
     * Get all relationships for an entity (both as subject and object).
     *
     * @param  string $iri    the entity IRI
     * @param  int    $limit  maximum number of relationships to return
     * @return array<int, array<string, string>>  array of relationship arrays
     *
     * @throws TriplestoreException on HTTP errors
     */
    public function getRelationships(string $iri, int $limit = 100): array;

    // =========================================================================
    // Health & Utility Methods
    // =========================================================================

    /**
     * Check triplestore health and return status information.
     *
     * @return array<string, mixed>  health status with keys: 'available', 'endpoint', 'response_time_ms'
     *
     * @throws TriplestoreException on connection failure
     */
    public function health(): array;

    /**
     * Count the total number of triples in the triplestore.
     *
     * @throws TriplestoreException on HTTP errors
     */
    public function countTriples(): int;

    /**
     * Generate a new unique IRI for a given entity type.
     *
     * @param  string $type  entity type (e.g. 'RecordSet', 'Person')
     * @return string  the generated IRI following pattern: {base_uri}/{type}/{uuid}
     */
    public function generateIri(string $type): string;

    /**
     * Get the canonical PREFIX declarations for SPARQL queries.
     *
     * @return string  PREFIX lines ready to prepend to a SPARQL query
     */
    public function getPrefixes(): string;
}
