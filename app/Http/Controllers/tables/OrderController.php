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
            'payment_method' => $request->payment_method,
            'status' => 'pending' // 🔥 IMPORTANT
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
            'status' => 'required|in:pending,preparing,shipping,delivered'
        ]);

        $order->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Status updated',
            'order' => $order
        ]);
    }

    // ADMIN STATS - Total orders, revenue, status breakdown
  public function stats()
{
    return response()->json([
        'total_orders' => Order::count(),

        'total_revenue' => Order::sum('total_price'),

        'delivered_orders' => Order::where('status', 'delivered')->count(),
        

        //  commandes par jour (7 derniers jours)
        'orders_per_day' => Order::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->take(7)
            ->get(),

        //  commandes par statut
        'status' => [
            'pending' => Order::where('status', 'pending')->count(),
            'preparing' => Order::where('status', 'preparing')->count(),
            'shipping' => Order::where('status', 'shipping')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
        ]
    ]);
}
    public function salesByDay()
{
    $data = Order::selectRaw('DATE(created_at) as date, SUM(total_price) as total')
        ->groupBy('date')
        ->orderBy('date', 'ASC')
        ->get();

    return response()->json($data);
}
}