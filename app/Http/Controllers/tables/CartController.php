<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Tables\AddToCartRequest;
use App\Http\Requests\Tables\UpdateCartItemRequest;
use App\Http\Resources\Api\CartResource;
use App\Models\Cart;
use App\Models\Product;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Return an existing cart for the user or create a new one.
     */
    private function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate([
            'user_id' => $user->id,
        ]);
    }

    /**
     * List items in the authenticated user's cart.
     */
    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request->user());
        $cart->load('items.product');// evite N+1 queries en chargeant les produits liés aux items du panier

        // Retourne la ressource Cart complète avec items et totaux calculés
        return new CartResource($cart);
    }

    /**
     * Add a product to the cart, or increase quantity when already present.
     */
    public function add(AddToCartRequest $request)
    {
        $cart = $this->getOrCreateCart($request->user());
        $product = Product::findOrFail($request->product_id);

        $item = $cart->items()->where('product_id', $product->id)->first();

        // check stock availability
        $currentQuantity = $item ? $item->quantity : 0;
        $newTotalQty = $currentQuantity + $request->quantity;
        if ($newTotalQty > $product->stock) {
            return response()->json([
                'message' => 'Stock insuffisant',
                'available' => $product->stock - $currentQuantity,
            ], 422);
        }

        if ($item) {
            $item->quantity = $newTotalQty;
            $item->save();
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price,
            ]);
        }

        return response()->json([
            'message' => 'Produit ajouté au panier',
        ], 201);
    }

    /**
     * Update the quantity of a cart item.
     */
    public function updateQuantity(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        $this->authorizeCartItem($cartItem);

        $product = $cartItem->product()->lockForUpdate()->first();
        if ($request->quantity > $product->stock) {
            return response()->json([
                'message' => 'Stock insuffisant',
                'available' => $product->stock,
            ], 422);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json(['message' => 'Quantité mise à jour']);
    }

    /**
     * Remove a single item from the cart.
     */
    public function remove(CartItem $cartItem)
    {
        $this->authorizeCartItem($cartItem);
        $cartItem->delete();

        return response()->json(['message' => 'Produit supprimé du panier']);
    }

    /**
     * Empty the authenticated user's cart.
     */
    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request->user());
        $cart->items()->delete();

        return response()->json(['message' => 'Panier vidé']);
    }

    /**
     * Abort if the given item does not belong to the current user.
     */
    private function authorizeCartItem(CartItem $item): void
    {
        if ($item->cart->user_id !== Auth::id()) {
            abort(403, 'Unauthorized cart item');
        }
    }
}
