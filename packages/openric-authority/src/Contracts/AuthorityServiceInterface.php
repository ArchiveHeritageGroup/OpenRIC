<?php

declare(strict_types=1);

namespace OpenRiC\Authority\Contracts;

/**
 * Contract for authority file search and linking operations.
 *
 * Provides search against external authority files (Wikidata, VIAF, LCNAF)
 * and manages owl:sameAs links between local RiC agents and external URIs.
 */
interface AuthorityServiceInterface
{
    /**
     * Search Wikidata for entities matching the given name and agent type.
     *
     * Uses the Wikidata SPARQL endpoint with EntitySearch API, filtering
     * by Q5 (human) for persons and Q43229 (organization) for corporate bodies.
     *
     * @param  string      $name       agent name to search for
     * @param  string      $agentType  RiC-O agent type: 'Person', 'CorporateBody', or 'Family'
     * @param  string|null $dates      optional date string for disambiguation
     * @return array<int, array{source: string, uri: string, label: string, description: string, viaf: string|null}>
     */
    public function searchWikidata(string $name, string $agentType = 'Person', ?string $dates = null): array;

    /**
     * Search VIAF for entities matching the given name and agent type.
     *
     * Uses CQL queries: local.personalNames for persons/families,
     * local.corporateNames for corporate bodies.
     *
     * @param  string $name       agent name to search for
     * @param  string $agentType  RiC-O agent type: 'Person', 'CorporateBody', or 'Family'
     * @return array<int, array{source: string, uri: string, viaf_id: string, label: string}>
     */
    public function searchViaf(string $name, string $agentType = 'Person'): array;

    /**
     * Search Library of Congress Name Authority File for matching names.
     *
     * Uses the LC Linked Data Service suggest2 endpoint.
     *
     * @param  string $name       agent name to search for
     * @param  string $agentType  RiC-O agent type for context
     * @return array<int, array{source: string, uri: string, label: string, lccn: string}>
     */
    public function searchLcnaf(string $name, string $agentType = 'Person'): array;

    /**
     * Add an owl:sameAs link between a local agent and an external authority URI.
     *
     * The link is stored in the triplestore with full RDF-Star provenance.
     *
     * @param  string $agentIri    local agent IRI in the triplestore
     * @param  string $externalUri external authority URI (Wikidata, VIAF, or LCNAF)
     * @param  string $source      authority source identifier ('wikidata', 'viaf', 'lcnaf')
     * @param  string $userId      user performing the linking
     * @param  string $reason      human-readable reason for the link
     */
    public function linkAgent(string $agentIri, string $externalUri, string $source, string $userId, string $reason): bool;

    /**
     * Remove an owl:sameAs link between a local agent and an external authority URI.
     *
     * @param  string $agentIri    local agent IRI
     * @param  string $externalUri external authority URI to unlink
     * @param  string $userId      user performing the unlinking
     * @param  string $reason      human-readable reason for removing the link
     */
    public function unlinkAgent(string $agentIri, string $externalUri, string $userId, string $reason): bool;

    /**
     * Retrieve all owl:sameAs external links for a given agent IRI.
     *
     * @param  string $iri agent IRI
     * @return array<int, array{uri: string, source: string, label: string}>
     */
    public function getExternalLinks(string $iri): array;

    /**
     * Get all agents from the triplestore that do not yet have external links.
     *
     * @param  int $limit maximum number of agents to return
     * @return array<int, array{uri: string, name: string, type: string, dates: string|null}>
     */
    public function getUnlinkedAgents(int $limit = 50): array;

    /**
     * Search all authority sources for a given agent and return scored results.
     *
     * Searches Wikidata, VIAF, and LCNAF in sequence with rate limiting,
     * and returns all matches with confidence scores.
     *
     * @param  string      $name       agent name to search
     * @param  string      $agentType  RiC-O agent type
     * @param  string|null $dates      optional date string for disambiguation
     * @return array<int, array{source: string, uri: string, label: string, confidence: float}>
     */
    public function autoSearch(string $name, string $agentType = 'Person', ?string $dates = null): array;

    /**
     * Automatically link a single agent to the best-matching external authorities.
     *
     * Searches all sources, scores results, and links those above the confidence threshold.
     *
     * @param  string $agentIri            local agent IRI
     * @param  string $userId              user performing the auto-link
     * @param  float  $confidenceThreshold minimum confidence score to create a link (0.0-1.0)
     * @return array{agent_iri: string, links_added: int, matches: array}
     */
    public function autoLinkAgent(string $agentIri, string $userId, float $confidenceThreshold = 0.7): array;

    /**
     * Batch-link multiple unlinked agents to external authorities.
     *
     * Iterates through unlinked agents, searches all sources, and links
     * matches that meet the confidence threshold.
     *
     * @param  string $userId              user performing the batch operation
     * @param  int    $limit               maximum number of agents to process
     * @param  float  $confidenceThreshold minimum confidence score to create a link
     * @param  bool   $dryRun              if true, return matches without creating links
     * @return array{agents_checked: int, links_added: int, errors: int, matches: array}
     */
    public function batchLink(string $userId, int $limit = 50, float $confidenceThreshold = 0.7, bool $dryRun = false): array;

    /**
     * Generate a statistics report for authority linking operations.
     *
     * @return array{total_agents: int, linked_agents: int, unlinked_agents: int, links_by_source: array<string, int>}
     */
    public function getStatistics(): array;
}
