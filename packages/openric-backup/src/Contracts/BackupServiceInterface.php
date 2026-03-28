<?php

declare(strict_types=1);

namespace OpenRiC\Backup\Contracts;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Backup service interface -- adapted from Heratio AhgBackup\Controllers\BackupController (604 lines).
 *
 * OpenRiC backs up PostgreSQL (pg_dump) and Fuseki triplestore (SPARQL graph export)
 * instead of Heratio's MySQL-based backups.
 */
interface BackupServiceInterface
{
    /**
     * Create a new backup. Type: full, database, or triplestore.
     *
     * @return array{success: bool, message: string, filename?: string, size?: string, errors?: array}
     */
    public function createBackup(string $type, int $createdBy): array;

    /**
     * List all existing backup files.
     *
     * @return array<int, array{id: int, filename: string, type: string, size_bytes: int, status: string, created_at: string}>
     */
    public function listBackups(): array;

    /**
     * Download a backup by ID.
     */
    public function downloadBackup(int $backupId): BinaryFileResponse;

    /**
     * Delete a backup record and its file.
     */
    public function deleteBackup(int $backupId): bool;

    /**
     * Restore from a backup.
     *
     * @return array{success: bool, message: string, errors?: array}
     */
    public function restoreBackup(int $backupId): array;

    /**
     * Get backup schedule configuration.
     *
     * @return array{enabled: bool, frequency: string, retention_days: int, max_backups: int}
     */
    public function getSchedule(): array;

    /**
     * Get backup statistics.
     *
     * @return array{total: int, total_size: string, last_backup: ?string, oldest_backup: ?string}
     */
    public function getStats(): array;
}
