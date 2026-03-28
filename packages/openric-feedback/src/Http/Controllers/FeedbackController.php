<?php

declare(strict_types=1);

namespace OpenRiC\Feedback\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use OpenRiC\Feedback\Contracts\FeedbackServiceInterface;

/**
 * Feedback controller -- adapted from Heratio AhgFeedback\Controllers\FeedbackController (316 lines).
 */
class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackServiceInterface $service,
    ) {}

    /**
     * Public: submit feedback.
     */
    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|string|in:general,bug,feature,content,usability',
            'message'  => 'required|string|max:10000',
            'rating'   => 'nullable|integer|min:1|max:5',
            'url'      => 'nullable|string|max:2048',
        ]);

        $id = $this->service->submit([
            'user_id'    => Auth::id(),
            'category'   => $request->input('category'),
            'message'    => $request->input('message'),
            'rating'     => $request->input('rating'),
            'url'        => $request->input('url'),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'id'      => $id,
            'message' => 'Thank you for your feedback. We will review it shortly.',
        ], 201);
    }

    /**
     * Admin: browse feedback with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['page', 'limit', 'category', 'status', 'sort', 'sortDir']);
        $data   = $this->service->browse($params);
        $stats  = $this->service->getStats();

        return response()->json(array_merge($data, ['stats' => $stats]));
    }

    /**
     * Admin: view single feedback entry.
     */
    public function show(int $id): JsonResponse
    {
        $feedback = $this->service->find($id);

        if (!$feedback) {
            return response()->json(['error' => 'Feedback not found.'], 404);
        }

        return response()->json(['feedback' => $feedback]);
    }

    /**
     * Admin: update feedback status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status'      => 'required|string|in:new,reviewed,resolved,closed',
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $updated = $this->service->updateStatus(
            $id,
            $request->input('status'),
            (int) Auth::id(),
            $request->input('admin_notes', ''),
        );

        if (!$updated) {
            return response()->json(['error' => 'Feedback not found or invalid status.'], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Admin: export all feedback as CSV.
     */
    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->service->export();
    }
}
