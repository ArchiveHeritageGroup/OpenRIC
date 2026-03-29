<?php

declare(strict_types=1);

namespace OpenRiC\Backup\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use OpenRiC\Backup\Contracts\BackupServiceInterface;
use OpenRiC\Core\Contracts\SettingsServiceInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Backup service -- adapted from Heratio AhgBackup\Controllers\BackupController (604 lines).
 *
 * Replaces Heratio's MySQL/mysqldump with PostgreSQL/pg_dump and adds Fuseki triplestore
 * export, uploads tar, packages tar, and framework tar.
 */
class BackupService implements BackupServiceInterface
{
    public function __construct(
        private readonly SettingsServiceInterface $settings,
    ) {}

    // ========================================================================
    // Configuration helpers
    // ========================================================================

    /**
     * Get the configured backup storage path.
     */
    private function getBackupPath(): string
    {
        return $this->settings->getString('backup', 'backup_path', config('backup.path', storage_path('backups')));
    }

    /**
     * Format bytes into a human-readable string.
     */
    private function humanFileSize(int $bytes, int $decimals = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units  = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = (int) floor((strlen((string) $bytes) - 1) / 3);
        $factor = min($factor, count($units) - 1);

        return sprintf("%.{$decimals}f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Detect backup type and components from a filename.
     *
     * @return array{type: string, components: array<string>}
     */
    private function detectTypeFromFilename(string $filename): array
    {
        $type = 'unknown';
        $components = [];

        if (str_contains($filename, 'full-backup')) {
            $type = 'full';
            $components = ['database', 'triplestore', 'uploads', 'packages', 'framework'];
        } elseif (str_contains($filename, 'database') || str_ends_with($filename, '.sql.gz')) {
            $type = 'database';
            $components = ['database'];
        } elseif (str_contains($filename, 'triplestore') || str_ends_with($filename, '.nq.gz')) {
            $type = 'triplestore';
            $components = ['triplestore'];
        } elseif (str_contains($filename, 'uploads')) {
            $type = 'uploads';
            $components = ['uploads'];
        } elseif (str_contains($filename, 'packages')) {
            $type = 'packages';
            $components = ['packages'];
        } elseif (str_contains($filename, 'framework')) {
            $type = 'framework';
            $components = ['framework'];
        }

        return ['type' => $type, 'components' => $components];
    }

    // ========================================================================
    // Create backup
    // ========================================================================

    /** {@inheritDoc} */
    public function createBackup(array $components, int $createdBy): array
    {
        $backupPath = $this->getBackupPath();

        // Ensure backup directory exists
        if (!File::isDirectory($backupPath)) {
            try {
                File::makeDirectory($backupPath, 0755, true);
            } catch (\Exception $e) {
                Log::error('Backup: failed to create directory', ['path' => $backupPath, 'error' => $e->getMessage()]);
                return ['success' => false, 'message' => 'Failed to create backup directory: ' . $e->getMessage()];
            }
        }

        $timestamp    = date('Y-m-d_His');
        $createdFiles = [];
        $errors       = [];

        // Determine overall type label
        $typeLabel = count($components) > 2 ? 'full' : implode('+', $components);

        // Insert pending record
        $backupId = DB::table('backups')->insertGetId([
            'filename'      => '',
            'type'          => $typeLabel,
            'components'    => json_encode($components),
            'size_bytes'    => 0,
            'status'        => 'running',
            'error_message' => null,
            'created_by'    => $createdBy,
            'started_at'    => now(),
            'completed_at'  => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // ---- PostgreSQL database ----
        if (in_array('database', $components, true)) {
            $result = $this->backupDatabase($backupPath, $timestamp);
            if ($result['success']) {
                $createdFiles[] = $result['file'];
            } else {
                $errors[] = $result['error'];
            }
        }

        // ---- Fuseki triplestore ----
        if (in_array('triplestore', $components, true)) {
            $result = $this->backupTriplestore($backupPath, $timestamp);
            if ($result['success']) {
                $createdFiles[] = $result['file'];
            } else {
                $errors[] = $result['error'];
            }
        }

        // ---- Uploads ----
        if (in_array('uploads', $components, true)) {
            $result = $this->backupUploads($backupPath, $timestamp);
            if ($result['success']) {
                $createdFiles[] = $result['file'];
            } else {
                $errors[] = $result['error'];
            }
        }

        // ---- Packages ----
        if (in_array('packages', $components, true)) {
            $result = $this->backupPackages($backupPath, $timestamp);
            if ($result['success']) {
                $createdFiles[] = $result['file'];
            } else {
                $errors[] = $result['error'];
            }
        }

        // ---- Framework ----
        if (in_array('framework', $components, true)) {
            $result = $this->backupFramework($backupPath, $timestamp);
            if ($result['success']) {
                $createdFiles[] = $result['file'];
            } else {
                $errors[] = $result['error'];
            }
        }

        // Calculate totals
        $totalSize       = array_sum(array_column($createdFiles, 'size_bytes'));
        $primaryFilename = !empty($createdFiles) ? $createdFiles[0]['filename'] : "failed_{$timestamp}";
        $status          = empty($createdFiles) ? 'failed' : (empty($errors) ? 'completed' : 'completed');

        DB::table('backups')->where('id', $backupId)->update([
            'filename'      => $primaryFilename,
            'size_bytes'    => $totalSize,
            'status'        => $status,
            'error_message' => !empty($errors) ? implode('; ', $errors) : null,
            'completed_at'  => now(),
            'updated_at'    => now(),
        ]);

        // Enforce retention policy
        $this->enforceRetention();

        // Send notification if configured
        $email = $this->settings->getString('backup', 'backup_notification_email', '');
        if ($email !== '' && !empty($createdFiles)) {
            Log::info('Backup: notification would be sent', ['email' => $email, 'files' => count($createdFiles)]);
        }

        if (!empty($createdFiles) && empty($errors)) {
            return ['success' => true, 'message' => 'Backup completed successfully.', 'files' => $createdFiles];
        }

        if (!empty($createdFiles)) {
            return ['success' => true, 'message' => 'Backup completed with warnings.', 'files' => $createdFiles, 'errors' => $errors];
        }

        return ['success' => false, 'message' => 'Backup failed.', 'errors' => $errors];
    }

    /**
     * Backup PostgreSQL database using pg_dump.
     */
    private function backupDatabase(string $backupPath, string $timestamp): array
    {
        $dbConfig = config('database.connections.pgsql');
        $dbHost   = $dbConfig['host'] ?? '127.0.0.1';
        $dbPort   = (string) ($dbConfig['port'] ?? '5432');
        $dbName   = $dbConfig['database'] ?? 'openric';
        $dbUser   = $dbConfig['username'] ?? 'openric';

        $filename = "database_{$dbName}_{$timestamp}.sql.gz";
        $filepath = $backupPath . '/' . $filename;

        $env = '';
        if (!empty($dbConfig['password'])) {
            $env = 'PGPASSWORD=' . escapeshellarg($dbConfig['password']) . ' ';
        }

        $cmd = $env . 'pg_dump'
            . ' --host=' . escapeshellarg($dbHost)
            . ' --port=' . escapeshellarg($dbPort)
            . ' --username=' . escapeshellarg($dbUser)
            . ' --no-password --format=plain'
            . ' --blobs --verbose'
            . ' ' . escapeshellarg($dbName)
            . ' 2>&1 | gzip > ' . escapeshellarg($filepath);

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($filepath) && File::size($filepath) > 0) {
            $size = File::size($filepath);
            return [
                'success' => true,
                'file'    => [
                    'component'  => 'database',
                    'filename'   => $filename,
                    'size_bytes' => $size,
                    'size'       => $this->humanFileSize($size),
                ],
            ];
        }

        if (File::exists($filepath)) {
            File::delete($filepath);
        }

        $errorMsg = !empty($output) ? implode("\n", $output) : "pg_dump exited with code {$returnCode}";
        Log::error('Backup: database failed', ['error' => $errorMsg]);

        return ['success' => false, 'error' => "Database backup failed: {$errorMsg}"];
    }

    /**
     * Backup Fuseki triplestore via N-Quads export.
     */
    private function backupTriplestore(string $backupPath, string $timestamp): array
    {
        $fusekiUrl = config('triplestore.fuseki_url', 'http://localhost:3030');
        $dataset   = config('triplestore.dataset', 'openric');

        $filename  = "triplestore_{$dataset}_{$timestamp}.nq.gz";
        $filepath  = $backupPath . '/' . $filename;
        $exportUrl = rtrim($fusekiUrl, '/') . '/' . $dataset . '/data';

        $cmd = 'curl -sf -H "Accept: application/n-quads" '
            . escapeshellarg($exportUrl)
            . ' 2>&1 | gzip > ' . escapeshellarg($filepath);

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($filepath) && File::size($filepath) > 20) {
            $size = File::size($filepath);
            return [
                'success' => true,
                'file'    => [
                    'component'  => 'triplestore',
                    'filename'   => $filename,
                    'size_bytes' => $size,
                    'size'       => $this->humanFileSize($size),
                ],
            ];
        }

        if (File::exists($filepath)) {
            File::delete($filepath);
        }

        $errorMsg = !empty($output) ? implode("\n", $output) : "curl exited with code {$returnCode}";
        Log::error('Backup: triplestore failed', ['error' => $errorMsg]);

        return ['success' => false, 'error' => "Triplestore backup failed: {$errorMsg}"];
    }

    /**
     * Backup uploads directory as a compressed tar.
     */
    private function backupUploads(string $backupPath, string $timestamp): array
    {
        $uploadsPath = config('app.uploads_path', storage_path('app/uploads'));
        $filename    = "uploads_{$timestamp}.tar.gz";
        $filepath    = $backupPath . '/' . $filename;

        if (!File::isDirectory($uploadsPath)) {
            return ['success' => false, 'error' => "Uploads directory not found: {$uploadsPath}"];
        }

        $cmd = 'tar -czf ' . escapeshellarg($filepath)
            . ' -C ' . escapeshellarg(dirname($uploadsPath))
            . ' ' . escapeshellarg(basename($uploadsPath))
            . ' 2>&1';

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($filepath)) {
            $size = File::size($filepath);
            return [
                'success' => true,
                'file'    => [
                    'component'  => 'uploads',
                    'filename'   => $filename,
                    'size_bytes' => $size,
                    'size'       => $this->humanFileSize($size),
                ],
            ];
        }

        if (File::exists($filepath)) {
            File::delete($filepath);
        }

        return ['success' => false, 'error' => 'Uploads backup failed: tar returned exit code ' . $returnCode];
    }

    /**
     * Backup packages directory as a compressed tar.
     */
    private function backupPackages(string $backupPath, string $timestamp): array
    {
        $packagesPath = base_path('packages');
        $filename     = "packages_{$timestamp}.tar.gz";
        $filepath     = $backupPath . '/' . $filename;

        if (!File::isDirectory($packagesPath)) {
            return ['success' => false, 'error' => 'Packages directory not found.'];
        }

        $cmd = 'tar -czf ' . escapeshellarg($filepath)
            . ' -C ' . escapeshellarg(base_path())
            . ' packages 2>&1';

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($filepath)) {
            $size = File::size($filepath);
            return [
                'success' => true,
                'file'    => [
                    'component'  => 'packages',
                    'filename'   => $filename,
                    'size_bytes' => $size,
                    'size'       => $this->humanFileSize($size),
                ],
            ];
        }

        if (File::exists($filepath)) {
            File::delete($filepath);
        }

        return ['success' => false, 'error' => 'Packages backup failed: tar returned exit code ' . $returnCode];
    }

    /**
     * Backup framework files as a compressed tar (excludes vendor, node_modules, storage/logs, .git, packages).
     */
    private function backupFramework(string $backupPath, string $timestamp): array
    {
        $filename = "framework_{$timestamp}.tar.gz";
        $filepath = $backupPath . '/' . $filename;

        $excludes = '--exclude=vendor --exclude=node_modules --exclude=storage/logs --exclude=.git --exclude=packages';
        $cmd      = 'tar -czf ' . escapeshellarg($filepath)
            . ' ' . $excludes
            . ' -C ' . escapeshellarg(dirname(base_path()))
            . ' ' . escapeshellarg(basename(base_path()))
            . ' 2>&1';

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($filepath)) {
            $size = File::size($filepath);
            return [
                'success' => true,
                'file'    => [
                    'component'  => 'framework',
                    'filename'   => $filename,
                    'size_bytes' => $size,
                    'size'       => $this->humanFileSize($size),
                ],
            ];
        }

        if (File::exists($filepath)) {
            File::delete($filepath);
        }

        return ['success' => false, 'error' => 'Framework backup failed: tar returned exit code ' . $returnCode];
    }

    // ========================================================================
    // List / Download / Delete
    // ========================================================================

    /** {@inheritDoc} */
    public function listBackups(): array
    {
        return DB::table('backups')
            ->leftJoin('users', 'backups.created_by', '=', 'users.id')
            ->select(
                'backups.id',
                'backups.filename',
                'backups.type',
                'backups.components',
                'backups.size_bytes',
                'backups.status',
                'backups.error_message',
                'backups.started_at',
                'backups.completed_at',
                'backups.created_at',
                'users.name as created_by_name',
            )
            ->orderByDesc('backups.created_at')
            ->get()
            ->map(function (object $row): array {
                $arr = (array) $row;
                $arr['size_human'] = $this->humanFileSize((int) ($arr['size_bytes'] ?? 0));
                $arr['components_array'] = json_decode($arr['components'] ?? '[]', true) ?: [];
                return $arr;
            })
            ->toArray();
    }

    /** {@inheritDoc} */
    public function downloadBackup(int $backupId): BinaryFileResponse
    {
        $backup = DB::table('backups')->where('id', $backupId)->first();

        if (!$backup) {
            abort(404, 'Backup not found.');
        }

        $path = $this->getBackupPath() . '/' . $backup->filename;

        if (!File::exists($path)) {
            abort(404, 'Backup file not found on disk.');
        }

        return response()->download($path, $backup->filename);
    }

    /** {@inheritDoc} */
    public function deleteBackup(int $backupId): bool
    {
        $backup = DB::table('backups')->where('id', $backupId)->first();

        if (!$backup) {
            return false;
        }

        // Delete all files matching the backup timestamp pattern
        $path = $this->getBackupPath() . '/' . $backup->filename;
        if (File::exists($path)) {
            File::delete($path);
        }

        // Also try to delete related component files from the same run
        $components = json_decode($backup->components ?? '[]', true) ?: [];
        if (count($components) > 1 && preg_match('/(\d{4}-\d{2}-\d{2}_\d{6})/', $backup->filename, $m)) {
            $tsPattern = $m[1];
            $backupPath = $this->getBackupPath();
            $files = File::files($backupPath);
            foreach ($files as $file) {
                if (str_contains($file->getFilename(), $tsPattern)) {
                    File::delete($file->getPathname());
                }
            }
        }

        return DB::table('backups')->where('id', $backupId)->delete() > 0;
    }

    // ========================================================================
    // Restore
    // ========================================================================

    /** {@inheritDoc} */
    public function restoreBackup(int $backupId, array $components): array
    {
        $backup = DB::table('backups')->where('id', $backupId)->first();

        if (!$backup || $backup->status !== 'completed') {
            return ['success' => false, 'message' => 'Backup not found or not in completed state.'];
        }

        $backupPath      = $this->getBackupPath();
        $backupFilename  = $backup->filename;
        $backupComponents = json_decode($backup->components ?? '[]', true) ?: [];
        $restored        = [];
        $errors          = [];

        // Extract timestamp from primary filename for finding related files
        $tsPattern = '';
        if (preg_match('/(\d{4}-\d{2}-\d{2}_\d{6})/', $backupFilename, $m)) {
            $tsPattern = $m[1];
        }

        // ---- Database restore ----
        if (in_array('database', $components, true) && in_array('database', $backupComponents, true)) {
            $dbFile = $this->findComponentFile($backupPath, $tsPattern, 'database', '.sql.gz');
            if ($dbFile !== null) {
                $result = $this->restoreDatabase($dbFile);
                if ($result['success']) {
                    $restored[] = 'database';
                } else {
                    $errors[] = $result['error'];
                }
            } else {
                $errors[] = 'Database backup file not found for this backup.';
            }
        }

        // ---- Triplestore restore ----
        if (in_array('triplestore', $components, true) && in_array('triplestore', $backupComponents, true)) {
            $tsFile = $this->findComponentFile($backupPath, $tsPattern, 'triplestore', '.nq.gz');
            if ($tsFile !== null) {
                $result = $this->restoreTriplestore($tsFile);
                if ($result['success']) {
                    $restored[] = 'triplestore';
                } else {
                    $errors[] = $result['error'];
                }
            } else {
                $errors[] = 'Triplestore backup file not found for this backup.';
            }
        }

        // ---- Uploads restore ----
        if (in_array('uploads', $components, true) && in_array('uploads', $backupComponents, true)) {
            $uploadFile = $this->findComponentFile($backupPath, $tsPattern, 'uploads', '.tar.gz');
            if ($uploadFile !== null) {
                $uploadsPath = config('app.uploads_path', storage_path('app/uploads'));
                $result = $this->restoreTar($uploadFile, dirname($uploadsPath));
                if ($result['success']) {
                    $restored[] = 'uploads';
                } else {
                    $errors[] = 'Uploads restore failed: ' . $result['error'];
                }
            } else {
                $errors[] = 'Uploads backup file not found for this backup.';
            }
        }

        // ---- Packages restore ----
        if (in_array('packages', $components, true) && in_array('packages', $backupComponents, true)) {
            $pkgFile = $this->findComponentFile($backupPath, $tsPattern, 'packages', '.tar.gz');
            if ($pkgFile !== null) {
                $result = $this->restoreTar($pkgFile, base_path());
                if ($result['success']) {
                    $restored[] = 'packages';
                } else {
                    $errors[] = 'Packages restore failed: ' . $result['error'];
                }
            } else {
                $errors[] = 'Packages backup file not found for this backup.';
            }
        }

        // ---- Framework restore ----
        if (in_array('framework', $components, true) && in_array('framework', $backupComponents, true)) {
            $fwFile = $this->findComponentFile($backupPath, $tsPattern, 'framework', '.tar.gz');
            if ($fwFile !== null) {
                $result = $this->restoreTar($fwFile, dirname(base_path()));
                if ($result['success']) {
                    $restored[] = 'framework';
                } else {
                    $errors[] = 'Framework restore failed: ' . $result['error'];
                }
            } else {
                $errors[] = 'Framework backup file not found for this backup.';
            }
        }

        if (!empty($restored) && empty($errors)) {
            return ['success' => true, 'message' => 'Restore completed successfully.', 'restored' => $restored];
        }

        if (!empty($restored)) {
            return ['success' => true, 'message' => 'Restore completed with warnings.', 'restored' => $restored, 'errors' => $errors];
        }

        return ['success' => false, 'message' => 'Restore failed.', 'errors' => $errors];
    }

    /**
     * Find a component file matching a timestamp pattern.
     */
    private function findComponentFile(string $backupPath, string $tsPattern, string $component, string $extension): ?string
    {
        if ($tsPattern === '') {
            return null;
        }

        $files = File::files($backupPath);
        foreach ($files as $file) {
            $name = $file->getFilename();
            if (str_contains($name, $component) && str_contains($name, $tsPattern) && str_ends_with($name, $extension)) {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Restore PostgreSQL database from a .sql.gz dump.
     */
    private function restoreDatabase(string $filepath): array
    {
        $dbConfig = config('database.connections.pgsql');
        $env = '';
        if (!empty($dbConfig['password'])) {
            $env = 'PGPASSWORD=' . escapeshellarg($dbConfig['password']) . ' ';
        }

        $cmd = 'gunzip -c ' . escapeshellarg($filepath)
            . ' | ' . $env . 'psql'
            . ' --host=' . escapeshellarg($dbConfig['host'] ?? '127.0.0.1')
            . ' --port=' . escapeshellarg((string) ($dbConfig['port'] ?? '5432'))
            . ' --username=' . escapeshellarg($dbConfig['username'] ?? 'openric')
            . ' --no-password'
            . ' ' . escapeshellarg($dbConfig['database'] ?? 'openric')
            . ' 2>&1';

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0) {
            return ['success' => true];
        }

        $errorMsg = !empty($output) ? implode("\n", $output) : "psql exited with code {$returnCode}";
        return ['success' => false, 'error' => "Database restore failed: {$errorMsg}"];
    }

    /**
     * Restore Fuseki triplestore from a .nq.gz dump.
     */
    private function restoreTriplestore(string $filepath): array
    {
        $fusekiUrl = config('triplestore.fuseki_url', 'http://localhost:3030');
        $dataset   = config('triplestore.dataset', 'openric');
        $dataUrl   = rtrim($fusekiUrl, '/') . '/' . $dataset . '/data';

        $cmd = 'gunzip -c ' . escapeshellarg($filepath)
            . ' | curl -sf -X PUT -H "Content-Type: application/n-quads" --data-binary @- '
            . escapeshellarg($dataUrl)
            . ' 2>&1';

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0) {
            return ['success' => true];
        }

        $errorMsg = !empty($output) ? implode("\n", $output) : "curl exited with code {$returnCode}";
        return ['success' => false, 'error' => "Triplestore restore failed: {$errorMsg}"];
    }

    /**
     * Restore a tar.gz archive to a target directory.
     */
    private function restoreTar(string $filepath, string $targetDir): array
    {
        $cmd = 'tar -xzf ' . escapeshellarg($filepath)
            . ' -C ' . escapeshellarg($targetDir)
            . ' 2>&1';

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'tar returned exit code ' . $returnCode];
    }

    // ========================================================================
    // Upload
    // ========================================================================

    /** {@inheritDoc} */
    public function uploadBackup(UploadedFile $file, int $createdBy): array
    {
        $backupPath = $this->getBackupPath();

        if (!File::isDirectory($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $originalName = $file->getClientOriginalName();
        $safeName     = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $finalPath    = $backupPath . '/' . $safeName;

        // Avoid overwriting existing files
        if (File::exists($finalPath)) {
            $safeName  = date('Y-m-d_His') . '_' . $safeName;
            $finalPath = $backupPath . '/' . $safeName;
        }

        $file->move($backupPath, $safeName);

        $detected = $this->detectTypeFromFilename($safeName);

        $backupId = DB::table('backups')->insertGetId([
            'filename'      => $safeName,
            'type'          => $detected['type'],
            'components'    => json_encode($detected['components']),
            'size_bytes'    => File::size($finalPath),
            'status'        => 'completed',
            'error_message' => null,
            'created_by'    => $createdBy,
            'started_at'    => now(),
            'completed_at'  => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return ['success' => true, 'message' => 'Backup uploaded successfully.', 'backup_id' => $backupId];
    }

    // ========================================================================
    // Schedule / Stats / Retention
    // ========================================================================

    /** {@inheritDoc} */
    public function getSchedule(): array
    {
        return [
            'enabled'            => $this->settings->getBool('backup', 'backup_enabled', false),
            'frequency'          => $this->settings->getString('backup', 'backup_frequency', 'daily'),
            'retention_days'     => $this->settings->getInt('backup', 'backup_retention_days', 30),
            'max_backups'        => $this->settings->getInt('backup', 'backup_max_backups', 10),
            'notification_email' => $this->settings->getString('backup', 'backup_notification_email', ''),
        ];
    }

    /** {@inheritDoc} */
    public function getStats(): array
    {
        $total        = DB::table('backups')->where('status', 'completed')->count();
        $totalSize    = (int) DB::table('backups')->where('status', 'completed')->sum('size_bytes');
        $lastBackup   = DB::table('backups')->where('status', 'completed')->max('completed_at');
        $oldestBackup = DB::table('backups')->where('status', 'completed')->min('created_at');
        $failedCount  = DB::table('backups')->where('status', 'failed')->count();

        return [
            'total'            => $total,
            'total_size'       => $this->humanFileSize($totalSize),
            'total_size_bytes' => $totalSize,
            'last_backup'      => $lastBackup,
            'oldest_backup'    => $oldestBackup,
            'failed_count'     => $failedCount,
        ];
    }

    /** {@inheritDoc} */
    public function enforceRetention(): void
    {
        $schedule      = $this->getSchedule();
        $maxBackups    = $schedule['max_backups'];
        $retentionDays = $schedule['retention_days'];
        $cutoff        = now()->subDays($retentionDays);
        $backupPath    = $this->getBackupPath();

        // Remove backups older than retention period
        $expired = DB::table('backups')
            ->where('created_at', '<', $cutoff)
            ->where('status', 'completed')
            ->get();

        foreach ($expired as $backup) {
            $path = $backupPath . '/' . $backup->filename;
            if (File::exists($path)) {
                File::delete($path);
            }
            DB::table('backups')->where('id', $backup->id)->delete();
        }

        // Remove excess backups beyond max count (keep newest)
        $allBackups = DB::table('backups')
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get();

        if ($allBackups->count() > $maxBackups) {
            $toRemove = $allBackups->slice($maxBackups);
            foreach ($toRemove as $backup) {
                $path = $backupPath . '/' . $backup->filename;
                if (File::exists($path)) {
                    File::delete($path);
                }
                DB::table('backups')->where('id', $backup->id)->delete();
            }
        }
    }

    // ========================================================================
    // Connection tests
    // ========================================================================

    /** {@inheritDoc} */
    public function testDatabaseConnection(): array
    {
        try {
            $pdo     = DB::connection('pgsql')->getPdo();
            $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);

            return ['success' => true, 'message' => 'Connected to PostgreSQL.', 'server_version' => $version];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    /** {@inheritDoc} */
    public function testTriplestoreConnection(): array
    {
        $fusekiUrl = config('triplestore.fuseki_url', 'http://localhost:3030');
        $dataset   = config('triplestore.dataset', 'openric');
        $pingUrl   = rtrim($fusekiUrl, '/') . '/$/ping';

        $cmd = 'curl -sf -o /dev/null -w "%{http_code}" ' . escapeshellarg($pingUrl) . ' 2>&1';
        exec($cmd, $output, $returnCode);

        $httpCode = !empty($output) ? (int) $output[0] : 0;

        if ($returnCode === 0 && $httpCode === 200) {
            return ['success' => true, 'message' => 'Fuseki is reachable.', 'dataset' => $dataset];
        }

        return ['success' => false, 'message' => "Fuseki not reachable (HTTP {$httpCode})."];
    }
}
