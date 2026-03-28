<?php

declare(strict_types=1);

namespace OpenRiC\Donor\Contracts;

/**
 * Contract for donor management.
 *
 * Adapted from Heratio DonorService + DonorBrowseService (combined ~358 lines).
 * Donors are operational data stored in PostgreSQL.
 * Links to accessions via foreign key in accessions table.
 */
interface DonorServiceInterface
{
    /**
     * Browse donors with filters, sorting, and pagination.
     *
     * @param  array{
     *     page?: int,
     *     limit?: int,
     *     sort?: string,
     *     sortDir?: string,
     *     subquery?: string,
     *     donorType?: string,
     *     isActive?: bool|null
     * } $params
     * @return array{hits: array<int, array<string, mixed>>, total: int, page: int, limit: int}
     */
    public function browse(array $params): array;

    /**
     * Find a donor by ID.
     *
     * @param  int $id  The donor primary key
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array;

    /**
     * Create a new donor.
     *
     * @param  array<string, mixed> $data    Donor field values
     * @param  int                  $userId  Creating user ID
     * @return int  The new donor ID
     */
    public function create(array $data, int $userId): int;

    /**
     * Update an existing donor.
     *
     * @param  int                  $id    The donor ID
     * @param  array<string, mixed> $data  Updated field values
     */
    public function update(int $id, array $data): void;

    /**
     * Soft-delete a donor.
     *
     * @param  int $id  The donor ID
     */
    public function delete(int $id): void;

    /**
     * Get donor statistics for dashboard.
     *
     * @return array{
     *     total: int,
     *     active: int,
     *     byType: array<string, int>,
     *     recentCount: int
     * }
     */
    public function getDonorStats(): array;

    /**
     * Get recently created/updated donors.
     *
     * @param  int $limit  Maximum number of results
     * @return array<int, array<string, mixed>>
     */
    public function getRecentDonors(int $limit = 10): array;

    /**
     * Get all donations (accessions) for a donor.
     *
     * @param  int $donorId  The donor ID
     * @return array<int, array<string, mixed>>
     */
    public function getDonationsForDonor(int $donorId): array;

    /**
     * Get all accessions linked to a donor.
     *
     * @param  int $donorId  The donor ID
     * @return array<int, array<string, mixed>>
     */
    public function getAccessionsForDonor(int $donorId): array;
}
