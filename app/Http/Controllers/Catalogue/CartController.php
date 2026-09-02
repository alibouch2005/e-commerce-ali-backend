<?php

namespace App\Http\Controllers\Catalogue;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\AddToCartRequest;
use App\Http\Resources\Api\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Get existing cart or create one
     */
    private function getOrCreateCart(Request $request)
    {
        // utilisateur connecté
        if ($request->user()) {

            $cart = Cart::firstOrCreate([
                'user_id' => $request->user()->id,
            ]);

            $this->mergeGuestCartIntoUserCart($request, $cart);

            return [$cart, null];
        }

        // guest
        $guestToken = $request->cookie('guest_token');

        if (! $guestToken) {
            $guestToken = Str::uuid()->toString();
        }

        $cart = Cart::firstOrCreate([
            'guest_token' => $guestToken,
        ]);

        return [$cart, $guestToken];
    }

    /**
     * Show cart
     */
    public function index(Request $request)
    {
        [$cart, $guestToken] = $this->getOrCreateCart($request);

        $cart->load('items.product');

        $response = (new CartResource($cart))->response();

        if ($guestToken) {
            $response->cookie('guest_token', $guestToken, 60 * 24 * 30);
        }

        return $response;
    }

    public function merge(Request $request)
    {
        abort_unless($request->user(), 401);

        $cart = Cart::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        $this->mergeGuestCartIntoUserCart($request, $cart);
        $cart->load('items.product');

        return (new CartResource($cart))->response()->setStatusCode(200)->cookie('guest_token', '', -1);
    }

    /**
     * Add product to cart
     */
    public function add(AddToCartRequest $request)
    {
        [$cart, $guestToken] = $this->getOrCreateCart($request);

        $product = Product::where('id', $request->product_id)
            ->where('stock', '>', 0)
            ->firstOrFail();

        $item = $cart->items()->where('product_id', $product->id)->first();

        $currentQty = $item ? $item->quantity : 0;
        $newQty = $currentQty + $request->quantity;

        if ($newQty > $product->stock) {
            return response()->json([
                'message' => 'Stock insuffisant',
            ], 422);
        }

        if ($item) {

            $item->update([
                'quantity' => $newQty,
            ]);

        } else {

            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->current_price,
            ]);

        }

        $cart->load('items.product');

        $response = (new CartResource($cart))->response();

        if ($guestToken) {
            $response->cookie('guest_token', $guestToken, 60 * 24 * 30);
        }

        return $response;
    }

    /**
     * Update quantity
     */
    public function updateQuantity(Request $request, CartItem $cartItem)
    {
        $this->authorizeCartItem($request, $cartItem);

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product = $cartItem->product;

        if ($request->quantity > $product->stock) {
            return response()->json([
                'message' => 'Stock insuffisant',
            ], 422);
        }

        $cartItem->update([
            'quantity' => $request->quantity,
        ]);

        return response()->json($cartItem->fresh('product'));
    }

    /**
     * Remove product
     */
    public function remove(Request $request, CartItem $cartItem)
    {
        $this->authorizeCartItem($request, $cartItem);
        $cartItem->delete();

        return response()->json([
            'message' => 'Produit supprimé',
        ]);
    }

    /**
     * Clear cart
     */
    public function clear(Request $request)
    {
        [$cart] = $this->getOrCreateCart($request);

        $cart->items()->delete();

        return response()->json([
            'message' => 'Panier vidé',
        ]);
    }

    private function authorizeCartItem(Request $request, CartItem $cartItem): void
    {
        $cart = $cartItem->cart;

        if ($request->user()) {
            abort_unless($cart->user_id === $request->user()->id, 403);

            return;
        }

        abort_unless($cart->guest_token && hash_equals($cart->guest_token, (string) $request->cookie('guest_token')), 403);
    }

    private function mergeGuestCartIntoUserCart(Request $request, Cart $userCart): void
    {
        $guestToken = (string) $request->cookie('guest_token');

        if ($guestToken === '') {
            return;
        }

        $guestCart = Cart::where('guest_token', $guestToken)->with('items.product')->first();

        if (! $guestCart || $guestCart->id === $userCart->id) {
            return;
        }

        foreach ($guestCart->items as $guestItem) {
            $product = $guestItem->product;

            if (! $product || $product->stock <= 0) {
                continue;
            }

            $targetItem = $userCart->items()->where('product_id', $guestItem->product_id)->first();
            $nextQuantity = min($product->stock, ($targetItem?->quantity ?? 0) + $guestItem->quantity);

            if ($targetItem) {
                $targetItem->update([
                    'quantity' => $nextQuantity,
                    'price' => $product->current_price,
                ]);

                continue;
            }

            $userCart->items()->create([
                'product_id' => $guestItem->product_id,
                'quantity' => min($product->stock, $guestItem->quantity),
                'price' => $product->current_price,
            ]);
        }

        $guestCart->items()->delete();
        $guestCart->delete();
    }
}
