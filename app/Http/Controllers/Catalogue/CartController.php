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
     * Return an existing cart or create one (guest or user)
     */
    private function getOrCreateCart(Request $request): callable
    {
        // utilisateur connecté
        if ($request->user()) {
            $cart = Cart::firstOrCreate([
                'user_id' => $request->user()->id,
            ]);

            return [$cart, null];
        }

        // guest
        $guestToken = $request->cookie('guest_token');

        if (!$guestToken) {
            $guestToken = Str::uuid()->toString();
        }

        $cart = Cart::firstOrCreate([
            'guest_token' => $guestToken,
        ]);

        return [$cart, $guestToken];
    }
    /**
     * List cart items
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

        //$product = Product::findOrFail($request->product_id);
        $product = Product::where('id', $request->product_id)
            ->where('stock', '>', 0)
            ->firstOrFail();

        $item = $cart->items()->where('product_id', $product->id)->first();

        $currentQuantity = $item ? $item->quantity : 0;
        $newTotalQty = $currentQuantity + $request->quantity;

        // check stock
        if ($newTotalQty > $product->stock) {
            return response()->json([
                'message' => 'Stock insuffisant',
                'available' => $product->stock - $currentQuantity,
            ], 422);
        }

        if ($item) {
            $item->update([
                'quantity' => $newTotalQty,
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price
            ]);
        }

        $response = (new CartResource(
            $cart->fresh('items.product')
        ))->response();

        if ($guestToken) {
            $response->cookie('guest_token', $guestToken, 60 * 24 * 30);
        }

        return $response;
    }
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

        $cartItem->load('product');

        return response()->json($cartItem);
    }

    public function remove(CartItem $cartItem)
    {
        $cartItem->delete();

        return response()->json([
            'message' => 'Produit supprimé du panier'
        ]);
    }

    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

        $cart->items()->delete();

        return response()->json([
            'message' => 'Panier vidé'
        ]);
    }
}
