<?php

declare(strict_types=1);

namespace OpenRiC\Backup\Contracts;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Backup service contract -- adapted from Heratio AhgBackup\Controllers\BackupController.
 *
 * OpenRiC backs up PostgreSQL (pg_dump), Fuseki triplestore (N-Quads export),
 * uploads (tar), packages (tar), and framework files (tar).
 */
interface BackupServiceInterface
{
    /**
     * Create a new backup with the specified components.
     *
     * @param  array<string>  $components  One or more of: database, triplestore, uploads, packages, framework
     * @param  int            $createdBy   User ID who initiated the backup
     * @return array{success: bool, message: string, files?: array, errors?: array}
     */
    public function createBackup(array $components, int $createdBy): array;

    /**
     * List all existing backup records, newest first.
     *
     * @return array<int, array>
     */
    public function listBackups(): array;

    /**
     * Download a backup file by record ID.
     */
    public function downloadBackup(int $backupId): BinaryFileResponse;

    /**
     * Delete a backup record and its file on disk.
     */
    public function deleteBackup(int $backupId): bool;

    /**
     * Restore from a backup.
     *
     * @param  int            $backupId    Backup record ID
     * @param  array<string>  $components  Components to restore
     * @return array{success: bool, message: string, restored?: array, errors?: array}
     */
    public function restoreBackup(int $backupId, array $components): array;

    /**
     * Upload an external backup file and register it.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  int                            $createdBy
     * @return array{success: bool, message: string, backup_id?: int}
     */
    public function uploadBackup(\Illuminate\Http\UploadedFile $file, int $createdBy): array;

    /**
     * Get backup schedule/retention settings.
     *
     * @return array{enabled: bool, frequency: string, retention_days: int, max_backups: int, notification_email: string}
     */
    public function getSchedule(): array;

    /**
     * Get backup statistics.
     *
     * @return array{total: int, total_size: string, total_size_bytes: int, last_backup: ?string, oldest_backup: ?string, failed_count: int}
     */
    public function getStats(): array;

    /**
     * Enforce retention policy (max backups + max age).
     */
    public function enforceRetention(): void;

    /**
     * Test the database connection.
     *
     * @return array{success: bool, message: string, server_version?: string}
     */
    public function testDatabaseConnection(): array;

    /**
     * Test the triplestore connection.
     *
     * @return array{success: bool, message: string, dataset?: string}
     */
    public function testTriplestoreConnection(): array;
}
