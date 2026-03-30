<?php

declare(strict_types=1);

namespace OpenRiC\AiGovernance\Contracts;

/**
 * AI Output Provenance Log — Module 3.
 *
 * Tracks every AI-generated output with full provenance chain.
 */
interface AiProvenanceServiceInterface
{
    public function logOutput(array $data): int;
    public function getOutput(int $id): ?object;
    public function listOutputs(array $filters = [], int $limit = 25, int $offset = 0): array;
    public function getOutputsForEntity(string $entityIri, ?string $outputType = null): array;

    /**
     * Review an AI output: approve, reject, or mark as superseded.
     */
    public function reviewOutput(int $id, string $status, int $reviewedBy, ?string $approvedOutput = null, ?string $notes = null): void;

    /**
     * Compute edit distance between raw and approved output.
     */
    public function computeEditDistance(int $id): int;

    /**
     * Get the full provenance chain for an entity (all AI outputs over time).
     */
    public function getProvenanceChain(string $entityIri): array;

    /**
     * Get outputs pending review.
     */
    public function getPendingReviews(int $limit = 50): array;

    /**
     * Get review statistics for a user.
     */
    public function getReviewerStats(int $userId): array;

    /**
     * Rate an AI output (user satisfaction).
     */
    public function rateOutput(int $outputId, int $userId, int $rating, ?string $comment = null): void;

    /**
     * Get ratings for an output.
     */
    public function getOutputRatings(int $outputId): array;

    public function getOutputTypes(): array;
    public function getStatusOptions(): array;
    public function getModelNames(): array;
}
