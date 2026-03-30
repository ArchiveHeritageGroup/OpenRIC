<?php

declare(strict_types=1);

namespace OpenRic\Cart\Controllers;

use OpenRic\Cart\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Browse available digital items.
     */
    public function browse()
    {
        return view('openric-cart::browse');
    }

    /**
     * View current cart.
     */
    public function index()
    {
        $user = auth()->user();
        $userIri = $this->getUserIri($user);
        $cart = $this->cartService->getCart($userIri);

        return view('openric-cart::index', compact('cart'));
    }

    /**
     * Add item to cart.
     */
    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'item_iri' => 'required|string',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $user = auth()->user();
        $userIri = $this->getUserIri($user);
        $cart = $this->cartService->getCart($userIri);

        $this->cartService->addItem(
            $cart['iri'],
            $validated['item_iri'],
            $validated['quantity'] ?? 1
        );

        return redirect()->back()->with('notice', 'Item added to cart.');
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(string $itemIri)
    {
        $this->cartService->removeItem(urldecode($itemIri));

        return redirect()->route('cart.index')->with('notice', 'Item removed from cart.');
    }

    /**
     * Update item quantity.
     */
    public function updateQuantity(Request $request, string $itemIri)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $this->cartService->updateQuantity(urldecode($itemIri), $validated['quantity']);

        return redirect()->route('cart.index')->with('notice', 'Cart updated.');
    }

    /**
     * Checkout.
     */
    public function checkout()
    {
        return view('openric-cart::checkout');
    }

    /**
     * Process payment.
     */
    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string',
        ]);

        $user = auth()->user();
        $userIri = $this->getUserIri($user);
        $cart = $this->cartService->getCart($userIri);

        $orderIri = $this->cartService->checkout($cart['iri'], [
            'method' => $validated['payment_method'],
        ]);

        return redirect()->route('cart.confirmation', ['order' => urlencode($orderIri)])
            ->with('notice', 'Order placed successfully.');
    }

    /**
     * Order confirmation.
     */
    public function confirmation(string $order)
    {
        return view('openric-cart::order-confirmation', ['orderIri' => urldecode($order)]);
    }

    /**
     * View orders.
     */
    public function orders()
    {
        $user = auth()->user();
        $userIri = $this->getUserIri($user);
        $orders = $this->cartService->getOrders($userIri);

        return view('openric-cart::orders', compact('orders'));
    }

    /**
     * Admin: view all orders.
     */
    public function adminOrders()
    {
        return view('openric-cart::admin-orders');
    }

    /**
     * Admin: settings.
     */
    public function adminSettings()
    {
        return view('openric-cart::admin-settings');
    }

    /**
     * Get user IRI.
     */
    private function getUserIri($user): string
    {
        if ($user && method_exists($user, 'getIri')) {
            return $user->getIri();
        }
        $baseUri = config('openric.user_base_uri', 'https://ric.theahg.co.za/user');
        return $baseUri . '/' . ($user?->id ?? 'anonymous');
    }
}
