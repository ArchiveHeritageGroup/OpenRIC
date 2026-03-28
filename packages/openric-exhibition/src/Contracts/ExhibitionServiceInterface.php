<?php

declare(strict_types=1);

namespace OpenRiC\Exhibition\Contracts;

/**
 * Exhibition management service interface.
 *
 * Adapted from Heratio ahg-exhibition ExhibitionService (197 lines).
 * Virtual and physical exhibitions with objects, storylines, sections, events, checklists.
 */
interface ExhibitionServiceInterface
{
    /**
     * Search exhibitions with filters and pagination.
     *
     * @param  array{status?: string, exhibition_type?: string, year?: string, search?: string} $filters
     * @return array{results: \Illuminate\Support\Collection, total: int}
     */
    public function search(array $filters = [], int $limit = 20, int $offset = 0): array;

    /**
     * Get a single exhibition by ID, optionally with all related data.
     */
    public function get(int $id, bool $withRelated = false): ?object;

    /**
     * Get a single exhibition by slug.
     */
    public function getBySlug(string $slug): ?object;

    /**
     * Create a new exhibition.
     *
     * @return int  The new exhibition ID
     */
    public function create(array $data): int;

    /**
     * Update an existing exhibition.
     */
    public function update(int $id, array $data): void;

    /**
     * Delete an exhibition and all related data (cascade).
     */
    public function delete(int $id): void;

    /**
     * Get available exhibition types.
     *
     * @return array<string, string>
     */
    public function getTypes(): array;

    /**
     * Get available exhibition statuses.
     *
     * @return array<string, string>
     */
    public function getStatuses(): array;

    /**
     * Get dashboard statistics.
     *
     * @return array{total: int, active: int, planning: int, completed: int}
     */
    public function getStatistics(): array;

    /**
     * Get objects for an exhibition.
     */
    public function getObjects(int $exhibitionId): \Illuminate\Support\Collection;

    /**
     * Add an object to an exhibition.
     *
     * @return int  The new exhibition_object ID
     */
    public function addObject(int $exhibitionId, array $data): int;

    /**
     * Remove an object from an exhibition.
     */
    public function removeObject(int $exhibitionId, int $objectId): void;

    /**
     * Reorder objects in an exhibition.
     *
     * @param  int[] $objectIds  ordered list of exhibition_object IDs
     */
    public function reorderObjects(int $exhibitionId, array $objectIds): void;

    /**
     * Get storylines for an exhibition.
     */
    public function getStorylines(int $exhibitionId): \Illuminate\Support\Collection;

    /**
     * Get a single storyline.
     */
    public function getStoryline(int $id): ?object;

    /**
     * Create a storyline for an exhibition.
     *
     * @return int  The new storyline ID
     */
    public function createStoryline(int $exhibitionId, array $data): int;

    /**
     * Update a storyline.
     */
    public function updateStoryline(int $id, array $data): void;

    /**
     * Delete a storyline.
     */
    public function deleteStoryline(int $id): void;

    /**
     * Get sections for an exhibition.
     */
    public function getSections(int $exhibitionId): \Illuminate\Support\Collection;

    /**
     * Create a section for an exhibition.
     *
     * @return int  The new section ID
     */
    public function createSection(int $exhibitionId, array $data): int;

    /**
     * Update a section.
     */
    public function updateSection(int $id, array $data): void;

    /**
     * Delete a section.
     */
    public function deleteSection(int $id): void;

    /**
     * Get events for an exhibition.
     */
    public function getEvents(int $exhibitionId): \Illuminate\Support\Collection;

    /**
     * Create an event for an exhibition.
     *
     * @return int  The new event ID
     */
    public function createEvent(int $exhibitionId, array $data): int;

    /**
     * Update an event.
     */
    public function updateEvent(int $id, array $data): void;

    /**
     * Delete an event.
     */
    public function deleteEvent(int $id): void;

    /**
     * Get checklists for an exhibition.
     */
    public function getChecklists(int $exhibitionId): \Illuminate\Support\Collection;

    /**
     * Create a checklist item for an exhibition.
     *
     * @return int  The new checklist ID
     */
    public function createChecklist(int $exhibitionId, array $data): int;

    /**
     * Update a checklist item.
     */
    public function updateChecklist(int $id, array $data): void;

    /**
     * Delete a checklist item.
     */
    public function deleteChecklist(int $id): void;

    /**
     * Export object list as CSV string.
     */
    public function exportObjectListCsv(int $exhibitionId): string;
}
