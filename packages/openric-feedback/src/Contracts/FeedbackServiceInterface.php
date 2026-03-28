<?php

declare(strict_types=1);

namespace OpenRiC\Feedback\Contracts;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Feedback service interface -- adapted from Heratio AhgFeedback\Controllers\FeedbackController (316 lines).
 *
 * Heratio had no separate service; all logic was in the controller. OpenRiC extracts it properly.
 */
interface FeedbackServiceInterface
{
    /**
     * Submit new feedback (public, may be anonymous).
     */
    public function submit(array $data): int;

    /**
     * Browse feedback with pagination and filtering.
     *
     * @return array{results: Collection, total: int, page: int, lastPage: int, limit: int}
     */
    public function browse(array $params = []): array;

    /**
     * Find a single feedback entry by ID.
     */
    public function find(int $id): ?object;

    /**
     * Update feedback status and admin notes.
     */
    public function updateStatus(int $id, string $status, ?int $reviewerId = null, string $adminNotes = ''): bool;

    /**
     * Get aggregate statistics.
     *
     * @return array{total: int, new: int, reviewed: int, resolved: int, closed: int, avg_rating: float|null}
     */
    public function getStats(): array;

    /**
     * Export all feedback as CSV.
     */
    public function export(): StreamedResponse;
}
