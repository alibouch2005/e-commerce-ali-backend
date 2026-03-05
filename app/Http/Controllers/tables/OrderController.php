<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\CheckoutRequest;
use App\Http\Resources\Api\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    // List orders for the authenticated user
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with('items.product')
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }
    // List all orders for admin
    public function adminIndex()
    {
        $orders = Order::with('user', 'items.product')
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    // Checkout: create an order from the user's cart
    public function checkout(CheckoutRequest $request)
    {
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)
            ->with('items.product')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Panier vide'
            ], 400);
        }

        $total = 0;

        $order = Order::create([
            'user_id' => $user->id,
            'total_price' => 0,
            'adresse_livraison' => $request->adresse_livraison,
            'phone' => $request->phone,
            'payment_method' => $request->payment_method
        ]);

        foreach ($cart->items as $item) {

            $subtotal = $item->quantity * $item->product->price;

            $total += $subtotal;

            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->price
            ]);
        }

        $order->update([
            'total_price' => $total
        ]);

        $cart->items()->delete();

        return new OrderResource($order->load('items.product'));
    }

    // Show a single order for the authenticated user
    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Commande non autorisée'
            ], 403);
        }

        return new OrderResource(
            $order->load('items.product')
        );
    }
    // Show a single order for admin
    public function adminShow(Order $order)
    {
        return new OrderResource(
            $order->load('items.product', 'user')
        );
    }

    // Update order status (admin only)
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:En attente,Preparation,Expedie,Livre'
        ]);

        $order->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Status updated',
            'order' => $order
        ]);
    }
}
