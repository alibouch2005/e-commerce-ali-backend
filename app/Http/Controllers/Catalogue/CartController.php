<?php

namespace App\Http\Controllers\Catalogue;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\AddToCartRequest;
use App\Http\Resources\Api\CartResource;
use App\Models\Cart;
use App\Models\Product;
use App\Models\CartItem;
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
                'user_id' => $request->user()->id
            ]);

            return [$cart, null];
        }

        // guest
        $guestToken = $request->cookie('guest_token');

        if (!$guestToken) {
            $guestToken = Str::uuid()->toString();
        }

        $cart = Cart::firstOrCreate([
            'guest_token' => $guestToken
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
                'message' => 'Stock insuffisant'
            ], 422);
        }

        if ($item) {

            $item->update([
                'quantity' => $newQty
            ]);

        } else {

            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price
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
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $product = $cartItem->product;

        if ($request->quantity > $product->stock) {
            return response()->json([
                'message' => 'Stock insuffisant'
            ], 422);
        }

        $cartItem->update([
            'quantity' => $request->quantity
        ]);

        return response()->json($cartItem->fresh('product'));
    }

    /**
     * Remove product
     */
    public function remove(CartItem $cartItem)
    {
        $cartItem->delete();

        return response()->json([
            'message' => 'Produit supprimé'
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
            'message' => 'Panier vidé'
        ]);
    }
}