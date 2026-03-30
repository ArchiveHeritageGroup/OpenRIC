<?php

declare(strict_types=1);

namespace OpenRiC\Favorites\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use OpenRiC\Favorites\Contracts\FavoritesServiceInterface;
use OpenRiC\Favorites\Contracts\FolderServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Favorites controller -- adapted from Heratio AhgFavorites\Controllers\FavoritesController.
 *
 * Full CRUD for favorites and folders: browse, toggle, bulk ops, notes,
 * folder management, sharing, export/import.
 */
class FavoritesController extends Controller
{
    public function __construct(
        private readonly FavoritesServiceInterface $favorites,
        private readonly FolderServiceInterface $folders,
    ) {}

    // ──────────────────────────────────────────────
    //  Browse
    // ──────────────────────────────────────────────

    /**
     * Paginated list of current user's favorites with folder sidebar.
     */
    public function browse(Request $request): View|RedirectResponse
    {
        $userId = Auth::id();
        if (!$userId) {
            return redirect()->route('login');
        }

        $userId = (int) $userId;
        $params = $request->only([
            'page', 'limit', 'sort', 'sortDir', 'query',
            'entity_type', 'folder_id', 'unfiled', 'view',
        ]);

        $data         = $this->favorites->getFavorites($userId, $params);
        $folderList   = $this->folders->getUserFolders($userId);
        $unfiledCount = $this->folders->getUnfiledCount($userId);
        $totalCount   = $this->favorites->getFavoriteCount($userId);

        return view('openric-favorites::browse', array_merge($data, [
            'params'       => $params,
            'folders'      => $folderList,
            'unfiledCount' => $unfiledCount,
            'totalCount'   => $totalCount,
        ]));
    }

    // ──────────────────────────────────────────────
    //  Add / Remove / Toggle
    // ──────────────────────────────────────────────

    /**
     * Toggle a favorite on/off (POST). Supports both AJAX and standard form POST.
     */
    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        $userId = Auth::id();
        if (!$userId) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Login required'], 401);
            }
            return redirect()->route('login');
        }

        $request->validate([
            'entity_iri'  => 'required|string|max:2048',
            'entity_type' => 'required|string|max:100',
            'title'       => 'required|string|max:500',
        ]);

        $favorited = $this->favorites->toggle(
            (int) $userId,
            $request->input('entity_iri'),
            $request->input('entity_type'),
            $request->input('title'),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'favorited' => $favorited,
                'count'     => $this->favorites->getFavoriteCount((int) $userId),
            ]);
        }

        $message = $favorited ? 'Added to favorites.' : 'Removed from favorites.';
        return redirect()->back()->with('success', $message);
    }

    /**
     * AJAX status check for a given entity IRI.
     */
    public function ajaxStatus(Request $request): JsonResponse
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['favorited' => false]);
        }

        $iri = $request->input('entity_iri', '');
        if ($iri === '') {
            return response()->json(['favorited' => false]);
        }

        return response()->json([
            'favorited' => $this->favorites->isFavorite((int) $userId, $iri),
        ]);
    }

    /**
     * Remove a specific favorite by row id.
     */
    public function remove(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $userId  = (int) Auth::id();
        $removed = $this->favorites->removeFavorite($userId, $id);

        if ($request->expectsJson()) {
            return response()->json(['removed' => $removed]);
        }

        return redirect()->route('favorites.browse')->with('success', 'Removed from favorites.');
    }

    /**
     * Clear all favorites for the current user.
     */
    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $count = $this->favorites->clearAll((int) Auth::id());

        if ($request->expectsJson()) {
            return response()->json(['cleared' => $count]);
        }

        return redirect()->route('favorites.browse')->with('success', "Cleared {$count} favorites.");
    }

    // ──────────────────────────────────────────────
    //  Bulk Operations
    // ──────────────────────────────────────────────

    /**
     * Bulk action: remove or move selected favorites.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $userId = (int) Auth::id();
        $action = $request->input('action');
        $ids    = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('favorites.browse')->with('error', 'No items selected.');
        }

        switch ($action) {
            case 'remove':
                $count = $this->favorites->bulkRemove($userId, $ids);
                return redirect()->route('favorites.browse')
                    ->with('success', "Removed {$count} items.");

            case 'move':
                $folderId = $request->input('move_folder_id');
                $count    = $this->favorites->moveToFolder($userId, $ids, $folderId ? (int) $folderId : null);
                return redirect()->route('favorites.browse')
                    ->with('success', "Moved {$count} items.");
        }

        return redirect()->route('favorites.browse');
    }

    // ──────────────────────────────────────────────
    //  Notes
    // ──────────────────────────────────────────────

    /**
     * Update notes on a favorite (AJAX).
     */
    public function updateNotes(Request $request, int $id): JsonResponse
    {
        $updated = $this->favorites->updateNotes(
            (int) Auth::id(),
            $id,
            $request->input('notes', ''),
        );

        return response()->json(['success' => $updated]);
    }

    // ──────────────────────────────────────────────
    //  Folder Management
    // ──────────────────────────────────────────────

    /**
     * Create a new folder.
     */
    public function folderCreate(Request $request): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:255']);

        $id = $this->folders->createFolder(
            (int) Auth::id(),
            $request->input('name'),
            $request->input('description'),
            $request->input('color'),
        );

        return redirect()->route('favorites.browse', ['folder_id' => $id])
            ->with('success', 'Folder created.');
    }

    /**
     * Edit an existing folder.
     */
    public function folderEdit(Request $request, int $id): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:255']);

        $this->folders->updateFolder(
            (int) Auth::id(),
            $id,
            $request->only(['name', 'description', 'color']),
        );

        return redirect()->route('favorites.browse', ['folder_id' => $id])
            ->with('success', 'Folder updated.');
    }

    /**
     * Delete a folder (items moved to unfiled).
     */
    public function folderDelete(int $id): RedirectResponse
    {
        $this->folders->deleteFolder((int) Auth::id(), $id);

        return redirect()->route('favorites.browse')
            ->with('success', 'Folder deleted. Items moved to unfiled.');
    }

    /**
     * Generate a share link for a folder.
     */
    public function shareFolder(Request $request, int $id): RedirectResponse
    {
        $days  = (int) $request->input('days', 30);
        $token = $this->folders->shareFolder((int) Auth::id(), $id, $days);

        if ($token) {
            return redirect()->route('favorites.browse', ['folder_id' => $id])
                ->with('success', 'Share link: ' . url('/favorites/shared/' . $token));
        }

        return redirect()->back()->with('error', 'Could not share folder.');
    }

    /**
     * Revoke sharing for a folder.
     */
    public function revokeSharing(int $id): RedirectResponse
    {
        $this->folders->revokeSharing((int) Auth::id(), $id);

        return redirect()->route('favorites.browse', ['folder_id' => $id])
            ->with('success', 'Sharing revoked.');
    }

    /**
     * Public view of a shared folder (no auth required).
     */
    public function viewShared(string $token): View
    {
        $folder = $this->folders->getSharedFolder($token);
        if (!$folder) {
            abort(404, 'Shared folder not found or expired.');
        }

        $items = $this->folders->getSharedFolderItems($folder->id, $folder->user_id);

        return view('openric-favorites::shared', compact('folder', 'items', 'token'));
    }

    // ──────────────────────────────────────────────
    //  Export / Import
    // ──────────────────────────────────────────────

    /**
     * Export favorites as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $folderId = $request->input('folder_id');
        return $this->favorites->exportCsv((int) Auth::id(), $folderId ? (int) $folderId : null);
    }

    /**
     * Export favorites as JSON.
     */
    public function exportJson(Request $request): JsonResponse
    {
        $folderId = $request->input('folder_id');
        return $this->favorites->exportJson((int) Auth::id(), $folderId ? (int) $folderId : null);
    }

    /**
     * Import favorites from uploaded CSV or pasted text.
     */
    public function importFavorites(Request $request): RedirectResponse
    {
        $content = '';

        if ($request->hasFile('file')) {
            $content = file_get_contents($request->file('file')->getRealPath());
        } elseif ($request->input('iris')) {
            $content = $request->input('iris');
        }

        if ($content === '' || $content === false) {
            return redirect()->route('favorites.browse')
                ->with('error', 'No import data provided.');
        }

        $count = $this->favorites->importFromCsv((int) Auth::id(), (string) $content);

        return redirect()->route('favorites.browse')
            ->with('success', "Imported {$count} items.");
    }
}
