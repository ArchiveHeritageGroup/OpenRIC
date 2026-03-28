<?php

declare(strict_types=1);

namespace OpenRiC\Help\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenRiC\Help\Contracts\HelpServiceInterface;

/**
 * Help controller -- adapted from Heratio AhgHelp\Controllers\HelpController (138 lines).
 *
 * Provides a public help system with browsing by category, viewing individual topics,
 * and keyword search across all topics.
 */
class HelpController extends Controller
{
    public function __construct(
        private readonly HelpServiceInterface $service,
    ) {}

    /**
     * Help index: list all categories and their topics.
     */
    public function index(Request $request): \Illuminate\Contracts\View\View|JsonResponse
    {
        $topics = $this->service->getTopics();

        if ($request->expectsJson()) {
            return response()->json(['topics' => $topics]);
        }

        return view('openric-help::index', ['topics' => $topics]);
    }

    /**
     * Show a single help topic by slug.
     */
    public function show(Request $request, string $slug): \Illuminate\Contracts\View\View|JsonResponse
    {
        $topic = $this->service->getTopic($slug);

        if (!$topic) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Help topic not found.'], 404);
            }
            abort(404, 'Help topic not found.');
        }

        // Get all topics for sidebar navigation
        $allTopics = $this->service->getTopics();

        if ($request->expectsJson()) {
            return response()->json(['topic' => $topic]);
        }

        return view('openric-help::show', [
            'topic'     => $topic,
            'allTopics' => $allTopics,
        ]);
    }

    /**
     * Search help topics by keyword.
     */
    public function search(Request $request): \Illuminate\Contracts\View\View|JsonResponse
    {
        $query   = trim((string) $request->input('q', ''));
        $results = [];

        if (mb_strlen($query) >= 2) {
            $results = $this->service->searchTopics($query);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'query'   => $query,
                'results' => $results,
            ]);
        }

        return view('openric-help::search', [
            'query'   => $query,
            'results' => $results,
        ]);
    }
}
