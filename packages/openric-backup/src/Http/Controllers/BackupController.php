<?php

declare(strict_types=1);

namespace OpenRiC\Backup\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use OpenRiC\Backup\Contracts\BackupServiceInterface;

/**
 * Backup controller -- adapted from Heratio AhgBackup\Controllers\BackupController (604 lines).
 */
class BackupController extends Controller
{
    public function __construct(
        private readonly BackupServiceInterface $service,
    ) {}

    /**
     * Dashboard: list backups and stats.
     */
    public function index(): JsonResponse
    {
        $backups  = $this->service->listBackups();
        $stats    = $this->service->getStats();
        $schedule = $this->service->getSchedule();

        return response()->json([
            'backups'  => $backups,
            'stats'    => $stats,
            'schedule' => $schedule,
        ]);
    }

    /**
     * Create a new backup.
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:full,database,triplestore',
        ]);

        $result = $this->service->createBackup(
            $request->input('type'),
            (int) Auth::id(),
        );

        $statusCode = $result['success'] ? 201 : 500;

        return response()->json($result, $statusCode);
    }

    /**
     * Download a backup file.
     */
    public function download(int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return $this->service->downloadBackup($id);
    }

    /**
     * Delete a backup.
     */
    public function delete(int $id): JsonResponse
    {
        $deleted = $this->service->deleteBackup($id);

        if (!$deleted) {
            return response()->json(['error' => 'Backup not found.'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get schedule configuration.
     */
    public function schedule(): JsonResponse
    {
        return response()->json($this->service->getSchedule());
    }
}
