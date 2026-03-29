<?php

declare(strict_types=1);

namespace OpenRiC\Feedback\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Feedback service contract.
 *
 * Adapted from Heratio AhgFeedback\Controllers\FeedbackController which embedded
 * all DB logic in the controller. OpenRiC separates concerns into service + contract.
 */
interface FeedbackServiceInterface
{
    /**
     * Submit new feedback (public, may be anonymous).
     *
     * @param  array<string, mixed>  $data
     * @return int  Inserted feedback ID
     */
    public function submit(array $data): int;

    /**
     * Browse feedback with pagination, filtering, and sorting.
     *
     * Mirrors Heratio's browse() which supports status filter + sort options
     * (nameUp, nameDown, dateUp, dateDown) with SimplePager pagination.
     *
     * @param  array<string, mixed>  $params  Keys: page, limit, category, status, sort
     * @return array{results: array<int, array<string, mixed>>, total: int, page: int, lastPage: int, limit: int}
     */
    public function browse(array $params = []): array;

    /**
     * Find a single feedback entry by ID.
     */
    public function find(int $id): ?object;

    /**
     * Update feedback status, admin notes, and completion timestamp.
     *
     * Adapted from Heratio's update() which sets status, status_id, completed_at,
     * and repurposes unique_identifier for admin notes.
     */
    public function updateStatus(int $id, string $status, ?int $reviewerId = null, string $adminNotes = ''): bool;

    /**
     * Delete a feedback entry.
     *
     * Heratio deletes from feedback_i18n, feedback, and object tables.
     * OpenRiC deletes the single feedback row.
     */
    public function delete(int $id): bool;

    /**
     * Get aggregate statistics for sidebar counts.
     *
     * Heratio computes totalCount, pendingCount, completedCount in browse().
     * OpenRiC extracts this into a dedicated method.
     *
     * @return array{total: int, pending: int, completed: int, new: int, reviewed: int, avg_rating: float|null}
     */
    public function getStats(): array;

    /**
     * Get available feedback categories.
     *
     * Heratio pulls these from the term/taxonomy tables with a fallback to
     * hardcoded defaults. OpenRiC returns a static list.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public function getCategories(): array;

    /**
     * Export all feedback as CSV.
     */
    public function export(): StreamedResponse;
}
