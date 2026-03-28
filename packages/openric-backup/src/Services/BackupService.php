<?php

declare(strict_types=1);

namespace OpenRiC\Backup\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use OpenRiC\Backup\Contracts\BackupServiceInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Backup service -- adapted from Heratio AhgBackup\Controllers\BackupController (604 lines).
 *
 * Replaces Heratio's MySQL/mysqldump with PostgreSQL/pg_dump and adds Fuseki triplestore export.
 */
class BackupService implements BackupServiceInterface
{
    private function getBackupPath(): string
    {
        return config('backup.path', storage_path('backups'));
    }

    private function humanFileSize(int $bytes, int $decimals = 2): string
    {
        $units  = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = $bytes > 0 ? (int) floor((strlen((string) $bytes) - 1) / 3) : 0;
        $factor = min($factor, count($units) - 1);

        return sprintf("%.{$decimals}f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    public function createBackup(string $type, int $createdBy): array
    {
        $backupPath = $this->getBackupPath();

        if (!File::isDirectory($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $timestamp = date('Y-m-d_His');
        $errors    = [];

        // Insert pending record
        $backupId = DB::table('backups')->insertGetId([
            'filename'     => '',
            'type'         => $type,
            'size_bytes'   => 0,
            'status'       => 'running',
            'error_message' => null,
            'created_by'   => $createdBy,
            'started_at'   => now(),
            'completed_at' => null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $createdFiles = [];

        // PostgreSQL database backup
        if (in_array($type, ['full', 'database'], true)) {
            $dbConfig = config('database.connections.pgsql');
            $dbHost   = $dbConfig['host'] ?? '127.0.0.1';
            $dbPort   = $dbConfig['port'] ?? '5432';
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
                . ' --port=' . escapeshellarg((string) $dbPort)
                . ' --username=' . escapeshellarg($dbUser)
                . ' --no-password --format=plain'
                . ' ' . escapeshellarg($dbName)
                . ' 2>&1 | gzip > ' . escapeshellarg($filepath);

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($filepath) && File::size($filepath) > 0) {
                $createdFiles[] = ['component' => 'database', 'filename' => $filename, 'size' => File::size($filepath)];
            } else {
                if (File::exists($filepath)) {
                    File::delete($filepath);
                }
                $errors[] = 'Database backup failed: ' . ($output ? implode("\n", $output) : "pg_dump exit code {$returnCode}");
            }
        }

        // Fuseki triplestore backup
        if (in_array($type, ['full', 'triplestore'], true)) {
            $fusekiUrl = config('triplestore.fuseki_url', 'http://localhost:3030');
            $dataset   = config('triplestore.dataset', 'openric');
            $filename  = "triplestore_{$dataset}_{$timestamp}.nq.gz";
            $filepath  = $backupPath . '/' . $filename;

            $exportUrl = rtrim($fusekiUrl, '/') . '/' . $dataset . '/data';
            $cmd       = 'curl -s -H "Accept: application/n-quads" '
                . escapeshellarg($exportUrl)
                . ' 2>&1 | gzip > ' . escapeshellarg($filepath);

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($filepath) && File::size($filepath) > 20) {
                $createdFiles[] = ['component' => 'triplestore', 'filename' => $filename, 'size' => File::size($filepath)];
            } else {
                if (File::exists($filepath)) {
                    File::delete($filepath);
                }
                $errors[] = 'Triplestore backup failed: ' . ($output ? implode("\n", $output) : "curl exit code {$returnCode}");
            }
        }

        // Determine primary filename and total size
        $totalSize       = array_sum(array_column($createdFiles, 'size'));
        $primaryFilename = !empty($createdFiles) ? $createdFiles[0]['filename'] : "failed_{$timestamp}";

        $status = empty($errors) && !empty($createdFiles)
            ? 'completed'
            : (empty($createdFiles) ? 'failed' : 'completed');

        DB::table('backups')->where('id', $backupId)->update([
            'filename'      => $primaryFilename,
            'size_bytes'    => $totalSize,
            'status'        => $status,
            'error_message' => !empty($errors) ? implode('; ', $errors) : null,
            'completed_at'  => now(),
            'updated_at'    => now(),
        ]);

        // Enforce retention
        $this->enforceRetention();

        if (!empty($createdFiles) && empty($errors)) {
            return ['success' => true, 'message' => 'Backup completed.', 'filename' => $primaryFilename, 'size' => $this->humanFileSize($totalSize)];
        } elseif (!empty($createdFiles)) {
            return ['success' => true, 'message' => 'Backup completed with warnings.', 'filename' => $primaryFilename, 'errors' => $errors];
        }

        return ['success' => false, 'message' => 'Backup failed.', 'errors' => $errors];
    }

    public function listBackups(): array
    {
        return DB::table('backups')
            ->leftJoin('users', 'backups.created_by', '=', 'users.id')
            ->select('backups.*', 'users.name as created_by_name')
            ->orderByDesc('backups.created_at')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->toArray();
    }

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

    public function deleteBackup(int $backupId): bool
    {
        $backup = DB::table('backups')->where('id', $backupId)->first();

        if (!$backup) {
            return false;
        }

        $path = $this->getBackupPath() . '/' . $backup->filename;
        if (File::exists($path)) {
            File::delete($path);
        }

        return DB::table('backups')->where('id', $backupId)->delete() > 0;
    }

    public function restoreBackup(int $backupId): array
    {
        $backup = DB::table('backups')->where('id', $backupId)->first();

        if (!$backup || $backup->status !== 'completed') {
            return ['success' => false, 'message' => 'Backup not found or not in completed state.'];
        }

        $path   = $this->getBackupPath() . '/' . $backup->filename;
        $errors = [];

        if (!File::exists($path)) {
            return ['success' => false, 'message' => 'Backup file not found on disk.'];
        }

        if ($backup->type === 'database' || $backup->type === 'full') {
            if (str_ends_with($backup->filename, '.sql.gz')) {
                $dbConfig = config('database.connections.pgsql');
                $env      = '';
                if (!empty($dbConfig['password'])) {
                    $env = 'PGPASSWORD=' . escapeshellarg($dbConfig['password']) . ' ';
                }

                $cmd = 'gunzip -c ' . escapeshellarg($path)
                    . ' | ' . $env . 'psql'
                    . ' --host=' . escapeshellarg($dbConfig['host'] ?? '127.0.0.1')
                    . ' --port=' . escapeshellarg((string) ($dbConfig['port'] ?? '5432'))
                    . ' --username=' . escapeshellarg($dbConfig['username'] ?? 'openric')
                    . ' --no-password'
                    . ' ' . escapeshellarg($dbConfig['database'] ?? 'openric')
                    . ' 2>&1';

                exec($cmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    $errors[] = 'Database restore failed: ' . implode("\n", $output);
                }
            }
        }

        if ($backup->type === 'triplestore' || $backup->type === 'full') {
            if (str_ends_with($backup->filename, '.nq.gz')) {
                $fusekiUrl = config('triplestore.fuseki_url', 'http://localhost:3030');
                $dataset   = config('triplestore.dataset', 'openric');
                $dataUrl   = rtrim($fusekiUrl, '/') . '/' . $dataset . '/data';

                $cmd = 'gunzip -c ' . escapeshellarg($path)
                    . ' | curl -s -X PUT -H "Content-Type: application/n-quads" --data-binary @- '
                    . escapeshellarg($dataUrl)
                    . ' 2>&1';

                exec($cmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    $errors[] = 'Triplestore restore failed: ' . implode("\n", $output);
                }
            }
        }

        if (empty($errors)) {
            return ['success' => true, 'message' => 'Restore completed successfully.'];
        }

        return ['success' => false, 'message' => 'Restore failed.', 'errors' => $errors];
    }

    public function getSchedule(): array
    {
        $settings = DB::table('settings')
            ->where('setting_group', 'backup')
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        return [
            'enabled'        => filter_var($settings['backup_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'frequency'      => $settings['backup_frequency'] ?? 'daily',
            'retention_days' => (int) ($settings['backup_retention_days'] ?? 30),
            'max_backups'    => (int) ($settings['backup_max_backups'] ?? 10),
        ];
    }

    public function getStats(): array
    {
        $total       = DB::table('backups')->where('status', 'completed')->count();
        $totalSize   = (int) DB::table('backups')->where('status', 'completed')->sum('size_bytes');
        $lastBackup  = DB::table('backups')->where('status', 'completed')->max('completed_at');
        $oldestBackup = DB::table('backups')->where('status', 'completed')->min('created_at');

        return [
            'total'         => $total,
            'total_size'    => $this->humanFileSize($totalSize),
            'last_backup'   => $lastBackup,
            'oldest_backup' => $oldestBackup,
        ];
    }

    private function enforceRetention(): void
    {
        $schedule      = $this->getSchedule();
        $maxBackups    = $schedule['max_backups'];
        $retentionDays = $schedule['retention_days'];
        $cutoff        = now()->subDays($retentionDays);
        $backupPath    = $this->getBackupPath();

        // Remove old backups beyond retention
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

        // Remove excess backups beyond max count
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
}
