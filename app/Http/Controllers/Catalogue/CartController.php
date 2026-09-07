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
        $selectedOptions = $this->normalizeSelectedOptions($product, $request->input('selected_options', []));
        $unitPrice = $product->currentPriceForOptions($selectedOptions);

        $item = $this->findMatchingItem($cart, $product, $selectedOptions);

        $currentQty = $item ? $item->quantity : 0;
        $newQty = $currentQty + $request->quantity;

        $requestedQty = $newQty;
        $newQty = min($newQty, $product->stock);

        if ($item) {

            $item->update([
                'quantity' => $newQty,
                'price' => $unitPrice,
            ]);

        } else {

            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => min($request->quantity, $product->stock),
                'price' => $unitPrice,
                'selected_options' => $selectedOptions,
            ]);

        }

        $cart->load('items.product');

        $response = (new CartResource($cart))->response();

        if ($guestToken) {
            $response->cookie('guest_token', $guestToken, 60 * 24 * 30);
        }

        return $requestedQty > $product->stock
            ? $response->setData(array_merge($response->getData(true), ['message' => "Quantite ajustee au stock disponible ({$product->stock})"]))
            : $response;
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

        if ($product->stock <= 0) {
            return response()->json([
                'message' => 'Produit bientot disponible',
            ], 422);
        }

        $cartItem->update([
            'quantity' => min($request->quantity, $product->stock),
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

            $targetItem = $this->findMatchingItem($userCart, $product, $guestItem->selected_options);
            $nextQuantity = min($product->stock, ($targetItem?->quantity ?? 0) + $guestItem->quantity);
            $unitPrice = $product->currentPriceForOptions($guestItem->selected_options);

            if ($targetItem) {
                $targetItem->update([
                    'quantity' => $nextQuantity,
                    'price' => $unitPrice,
                ]);

                continue;
            }

            $userCart->items()->create([
                'product_id' => $guestItem->product_id,
                'quantity' => min($product->stock, $guestItem->quantity),
                'price' => $unitPrice,
                'selected_options' => $guestItem->selected_options,
            ]);
        }

        $guestCart->items()->delete();
        $guestCart->delete();
    }

    private function findMatchingItem(Cart $cart, Product $product, ?array $selectedOptions): ?CartItem
    {
        $query = $cart->items()->where('product_id', $product->id);

        if (empty($selectedOptions)) {
            $query->whereNull('selected_options');
        } else {
            $query->where('selected_options', json_encode($selectedOptions, JSON_UNESCAPED_UNICODE));
        }

        return $query->first();
    }

    private function normalizeSelectedOptions(Product $product, array $selectedOptions): ?array
    {
        $availableOptions = collect($product->variant_options ?: [])
            ->map(fn ($values) => collect($values)->filter()->values()->all())
            ->filter(fn ($values) => count($values) > 0)
            ->all();

        if (! $product->has_variants || count($availableOptions) === 0) {
            return null;
        }

        $normalized = [];
        foreach ($availableOptions as $key => $values) {
            $value = trim((string) ($selectedOptions[$key] ?? ''));
            if ($value === '' || ! in_array($value, $values, true)) {
                abort(response()->json([
                    'message' => "Choisissez une option valide pour {$key}.",
                    'errors' => ['selected_options' => ["Option {$key} invalide."]],
                ], 422));
            }
            $normalized[$key] = $value;
        }

        ksort($normalized);

        return $normalized;
    }
}
