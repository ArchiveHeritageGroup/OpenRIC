<?php

declare(strict_types=1);

namespace OpenRiC\Backup\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenRiC\Backup\Contracts\BackupServiceInterface;
use OpenRiC\Core\Contracts\SettingsServiceInterface;

/**
 * Backup controller -- adapted from Heratio AhgBackup\Controllers\BackupController (604 lines).
 *
 * Provides full HTML dashboard, settings, restore, and upload views plus
 * AJAX endpoints for create / delete / restore / test operations.
 */
class BackupController extends Controller
{
    public function __construct(
        private readonly BackupServiceInterface $service,
        private readonly SettingsServiceInterface $settings,
    ) {}

    // ========================================================================
    // Dashboard
    // ========================================================================

    /**
     * Backup dashboard -- list backups, show stats, quick actions.
     */
    public function index(): View
    {
        $dbConfig   = config('database.connections.pgsql');
        $backups    = $this->service->listBackups();
        $stats      = $this->service->getStats();
        $schedule   = $this->service->getSchedule();
        $backupPath = $this->settings->getString('backup', 'backup_path', config('backup.path', storage_path('backups')));

        // Test DB connection
        $dbTest = $this->service->testDatabaseConnection();
        $tsTest = $this->service->testTriplestoreConnection();

        return view('openric-backup::index', [
            'dbConfig'          => $dbConfig,
            'dbConnected'       => $dbTest['success'],
            'dbVersion'         => $dbTest['server_version'] ?? null,
            'tsConnected'       => $tsTest['success'],
            'tsDataset'         => $tsTest['dataset'] ?? config('triplestore.dataset', 'openric'),
            'backupPath'        => $backupPath,
            'backups'           => $backups,
            'backupCount'       => $stats['total'],
            'totalSize'         => $stats['total_size'],
            'failedCount'       => $stats['failed_count'],
            'lastBackup'        => $stats['last_backup'],
            'maxBackups'        => $schedule['max_backups'],
            'retentionDays'     => $schedule['retention_days'],
            'notificationEmail' => $schedule['notification_email'],
        ]);
    }

    // ========================================================================
    // Create (AJAX)
    // ========================================================================

    /**
     * Create a new backup (AJAX).
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'components'   => 'required|array|min:1',
            'components.*' => 'in:database,triplestore,uploads,packages,framework',
        ]);

        $result = $this->service->createBackup(
            $request->input('components'),
            (int) Auth::id(),
        );

        $statusCode = $result['success'] ? 200 : 500;

        return response()->json($result, $statusCode);
    }

    // ========================================================================
    // Download
    // ========================================================================

    /**
     * Download a backup file.
     */
    public function download(int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return $this->service->downloadBackup($id);
    }

    // ========================================================================
    // Delete (AJAX)
    // ========================================================================

    /**
     * Delete a backup file (AJAX).
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->deleteBackup($id);

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Backup not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Backup deleted successfully.']);
    }

    // ========================================================================
    // Settings
    // ========================================================================

    /**
     * Backup settings form.
     */
    public function settingsForm(): View
    {
        $current = [
            'backup_path'               => $this->settings->getString('backup', 'backup_path', storage_path('backups')),
            'backup_max_backups'        => $this->settings->getInt('backup', 'backup_max_backups', 10),
            'backup_retention_days'     => $this->settings->getInt('backup', 'backup_retention_days', 30),
            'backup_notification_email' => $this->settings->getString('backup', 'backup_notification_email', ''),
            'backup_enabled'            => $this->settings->getBool('backup', 'backup_enabled', false),
            'backup_frequency'          => $this->settings->getString('backup', 'backup_frequency', 'daily'),
        ];

        return view('openric-backup::settings', [
            'settings' => $current,
        ]);
    }

    /**
     * Save backup settings.
     */
    public function saveSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_path'               => 'required|string|max:500',
            'backup_max_backups'        => 'required|integer|min:1|max:999',
            'backup_retention_days'     => 'required|integer|min:1|max:3650',
            'backup_notification_email' => 'nullable|email|max:255',
            'backup_enabled'            => 'nullable|in:0,1',
            'backup_frequency'          => 'required|string|in:daily,weekly,monthly',
        ]);

        $this->settings->set('backup', 'backup_path', $request->input('backup_path'), 'string', 'Backup storage path');
        $this->settings->set('backup', 'backup_max_backups', $request->input('backup_max_backups'), 'integer', 'Maximum number of backups to keep');
        $this->settings->set('backup', 'backup_retention_days', $request->input('backup_retention_days'), 'integer', 'Days to retain backups');
        $this->settings->set('backup', 'backup_notification_email', $request->input('backup_notification_email', ''), 'email', 'Notification email');
        $this->settings->set('backup', 'backup_enabled', $request->input('backup_enabled', '0'), 'boolean', 'Enable scheduled backups');
        $this->settings->set('backup', 'backup_frequency', $request->input('backup_frequency'), 'string', 'Backup frequency');

        $this->settings->clearCache();

        return redirect()->route('backups.settings')->with('success', 'Backup settings saved successfully.');
    }

    // ========================================================================
    // Restore
    // ========================================================================

    /**
     * Restore page -- select backup and components.
     */
    public function restoreForm(): View
    {
        $backups = $this->service->listBackups();

        return view('openric-backup::restore', [
            'backups' => $backups,
        ]);
    }

    /**
     * Perform restore (AJAX).
     */
    public function doRestore(Request $request): JsonResponse
    {
        $request->validate([
            'backup_id'    => 'required|integer',
            'components'   => 'required|array|min:1',
            'components.*' => 'in:database,triplestore,uploads,packages,framework',
        ]);

        $result = $this->service->restoreBackup(
            (int) $request->input('backup_id'),
            $request->input('components'),
        );

        $statusCode = $result['success'] ? 200 : 500;

        return response()->json($result, $statusCode);
    }

    // ========================================================================
    // Upload
    // ========================================================================

    /**
     * Upload backup form.
     */
    public function uploadForm(): View
    {
        return view('openric-backup::upload');
    }

    /**
     * Handle backup upload.
     */
    public function doUpload(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_file' => 'required|file|max:5242880|mimes:gz,zip,sql',
        ]);

        $result = $this->service->uploadBackup(
            $request->file('backup_file'),
            (int) Auth::id(),
        );

        if ($result['success']) {
            return redirect()->route('backups.index')->with('success', $result['message']);
        }

        return redirect()->route('backups.upload')->with('error', $result['message']);
    }

    // ========================================================================
    // AJAX: Connection tests
    // ========================================================================

    /**
     * Test database connection (AJAX).
     */
    public function testDatabase(): JsonResponse
    {
        return response()->json($this->service->testDatabaseConnection());
    }

    /**
     * Test triplestore connection (AJAX).
     */
    public function testTriplestore(): JsonResponse
    {
        return response()->json($this->service->testTriplestoreConnection());
    }
}
