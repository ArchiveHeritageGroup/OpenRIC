<?php

declare(strict_types=1);

namespace OpenRiC\ResearchRequest\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use OpenRiC\ResearchRequest\Contracts\ResearchRequestServiceInterface;

/**
 * Research request controller -- adapted from Heratio AhgCart\Controllers\CartController (269 lines).
 *
 * Manages the research cart, request submission, and admin review workflow.
 */
class ResearchRequestController extends Controller
{
    public function __construct(
        private readonly ResearchRequestServiceInterface $service,
    ) {}

    /**
     * View the user's research cart.
     */
    public function cart(Request $request): \Illuminate\Contracts\View\View|JsonResponse
    {
        $userId = (int) Auth::id();
        $items  = $this->service->getCart($userId);

        if ($request->expectsJson()) {
            return response()->json(['items' => $items]);
        }

        return view('openric-research-request::cart', ['items' => $items]);
    }

    /**
     * Add an entity to the cart (POST).
     */
    public function add(Request $request): JsonResponse|RedirectResponse
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

        $added = $this->service->addToCart(
            (int) $userId,
            $request->input('entity_iri'),
            $request->input('entity_type'),
            $request->input('title'),
        );

        if ($request->expectsJson()) {
            return response()->json(['added' => $added]);
        }

        $msg = $added ? 'Item added to research cart.' : 'Item is already in your cart.';
        return redirect()->back()->with($added ? 'success' : 'info', $msg);
    }

    /**
     * Remove an item from the cart (POST).
     */
    public function remove(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $removed = $this->service->removeFromCart((int) Auth::id(), $id);

        if ($request->expectsJson()) {
            return response()->json(['removed' => $removed]);
        }

        return redirect()->route('research.cart')->with('success', 'Item removed from cart.');
    }

    /**
     * Submit the cart as a research request (POST).
     */
    public function submit(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'purpose' => 'required|string|max:2000',
            'notes'   => 'nullable|string|max:5000',
        ]);

        try {
            $uuid = $this->service->submitRequest(
                (int) Auth::id(),
                $request->input('purpose'),
                $request->input('notes', ''),
            );

            if ($request->expectsJson()) {
                return response()->json(['uuid' => $uuid, 'message' => 'Research request submitted.']);
            }

            return redirect()->route('research.cart')
                ->with('success', "Research request submitted (Reference: {$uuid}).");
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return redirect()->route('research.cart')->with('error', $e->getMessage());
        }
    }

    /**
     * Admin: list all research requests (paginated, filterable by status).
     */
    public function requests(Request $request): \Illuminate\Contracts\View\View|JsonResponse
    {
        $params = $request->only(['page', 'limit', 'status', 'user_id']);
        $data   = $this->service->getRequests($params);
        $stats  = $this->service->getStats();

        if ($request->expectsJson()) {
            return response()->json(array_merge($data, ['stats' => $stats]));
        }

        return view('openric-research-request::requests', array_merge($data, [
            'stats'  => $stats,
            'params' => $params,
        ]));
    }

    /**
     * Admin: view a single request.
     */
    public function show(Request $request, int $id): \Illuminate\Contracts\View\View|JsonResponse
    {
        $researchRequest = $this->service->getRequest($id);

        if (!$researchRequest) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Request not found.'], 404);
            }
            abort(404, 'Research request not found.');
        }

        if ($request->expectsJson()) {
            return response()->json(['request' => $researchRequest]);
        }

        return view('openric-research-request::show', ['researchRequest' => $researchRequest]);
    }

    /**
     * Admin: approve a pending request (POST).
     */
    public function approve(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $request->validate(['notes' => 'nullable|string|max:5000']);

        $approved = $this->service->approveRequest(
            $id,
            (int) Auth::id(),
            $request->input('notes', ''),
        );

        if (!$approved) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Request not found or not pending.'], 422);
            }
            return redirect()->back()->with('error', 'Request not found or not pending.');
        }

        if ($request->expectsJson()) {
            return response()->json(['approved' => true]);
        }

        return redirect()->route('research.requests')->with('success', 'Request approved.');
    }

    /**
     * Admin: deny a pending request (POST).
     */
    public function deny(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $request->validate(['notes' => 'nullable|string|max:5000']);

        $denied = $this->service->denyRequest(
            $id,
            (int) Auth::id(),
            $request->input('notes', ''),
        );

        if (!$denied) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Request not found or not pending.'], 422);
            }
            return redirect()->back()->with('error', 'Request not found or not pending.');
        }

        if ($request->expectsJson()) {
            return response()->json(['denied' => true]);
        }

        return redirect()->route('research.requests')->with('success', 'Request denied.');
    }
}
