<?php

declare(strict_types=1);

namespace OpenRiC\Favorites\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use OpenRiC\Favorites\Contracts\FavoritesServiceInterface;

/**
 * Favorites controller -- adapted from Heratio AhgFavorites\Controllers\FavoritesController (235 lines).
 *
 * Provides browsing, toggling, clearing, and exporting of user favorites.
 */
class FavoritesController extends Controller
{
    public function __construct(
        private readonly FavoritesServiceInterface $favorites,
    ) {}

    /**
     * Paginated list of current user's favorites.
     */
    public function index(Request $request): \Illuminate\Contracts\View\View|RedirectResponse
    {
        $userId = Auth::id();
        if (!$userId) {
            return redirect()->route('login');
        }

        $params = $request->only(['page', 'limit', 'sort', 'sortDir', 'query', 'entity_type']);
        $data   = $this->favorites->getFavorites((int) $userId, $params);
        $count  = $this->favorites->getFavoriteCount((int) $userId);

        return view('openric-favorites::index', array_merge($data, [
            'params'     => $params,
            'totalCount' => $count,
        ]));
    }

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
     * Remove a specific favorite by row id.
     */
    public function remove(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $userId = (int) Auth::id();
        $removed = $this->favorites->removeFavorite($userId, $id);

        if ($request->expectsJson()) {
            return response()->json(['removed' => $removed]);
        }

        return redirect()->route('favorites.index')->with('success', 'Removed from favorites.');
    }

    /**
     * Clear all favorites for current user.
     */
    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $count = $this->favorites->clearAll((int) Auth::id());

        if ($request->expectsJson()) {
            return response()->json(['cleared' => $count]);
        }

        return redirect()->route('favorites.index')->with('success', "Cleared {$count} favorites.");
    }

    /**
     * Check favorite status for a given IRI (AJAX).
     */
    public function status(Request $request): JsonResponse
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['favorited' => false]);
        }

        return response()->json([
            'favorited' => $this->favorites->isFavorite((int) $userId, $request->input('entity_iri', '')),
        ]);
    }

    /**
     * Export favorites as CSV download.
     */
    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->favorites->exportCsv((int) Auth::id());
    }
}
