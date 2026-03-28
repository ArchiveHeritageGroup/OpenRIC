<?php

declare(strict_types=1);

namespace OpenRiC\Research\Contracts;

/**
 * Research workspace, annotation, citation, and assessment operations.
 *
 * Adapted from Heratio ahg-research ResearchService (608 lines).
 */
interface ResearchServiceInterface
{
    /**
     * Create a new research workspace.
     *
     * @param  array{user_id: int, name: string, description?: string, is_public?: bool} $data
     * @return int  workspace ID
     */
    public function createWorkspace(array $data): int;

    /**
     * Get all workspaces for a user.
     *
     * @return array<int, object>
     */
    public function getWorkspaces(int $userId): array;

    /**
     * Get a single workspace with its items.
     */
    public function getWorkspace(int $workspaceId): ?object;

    /**
     * Add an entity to a workspace.
     *
     * @param  array{entity_iri: string, entity_type: string, title: string, sort_order?: int} $data
     * @return int  item ID
     */
    public function addItemToWorkspace(int $workspaceId, array $data): int;

    /**
     * Remove an entity from a workspace.
     */
    public function removeItemFromWorkspace(int $workspaceId, string $entityIri): bool;

    /**
     * Create an annotation on an entity.
     *
     * @param  array{user_id: int, entity_iri: string, annotation_type: string, content: string, is_public?: bool} $data
     * @return int  annotation ID
     */
    public function createAnnotation(array $data): int;

    /**
     * Get all annotations for a specific entity.
     *
     * @return array<int, object>
     */
    public function getAnnotationsForEntity(string $entityIri, ?int $userId = null): array;

    /**
     * Get citations for an entity.
     *
     * @return array<int, object>
     */
    public function getCitations(string $entityIri, ?int $userId = null): array;

    /**
     * Add a citation for an entity.
     *
     * @param  array{user_id: int, entity_iri: string, citation_style: string, citation_text: string} $data
     * @return int  citation ID
     */
    public function addCitation(array $data): int;

    /**
     * Get assessments for an entity.
     *
     * @return array<int, object>
     */
    public function getAssessments(string $entityIri): array;

    /**
     * Add an assessment for an entity.
     *
     * @param  array{user_id: int, entity_iri: string, assessment_type: string, content: string, score?: int} $data
     * @return int  assessment ID
     */
    public function addAssessment(array $data): int;
}
