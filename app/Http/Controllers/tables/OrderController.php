<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\CheckoutRequest;
use App\Http\Resources\Api\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Models\Product; // Ajouté pour les stats de stock
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with('items.product')
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    public function adminIndex()
    {
        $orders = Order::with('user', 'items.product')
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    public function checkout(CheckoutRequest $request)
    {
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)
            ->with('items.product')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Panier vide'], 400);
        }

        $total = 0;

        // Création de la commande
        $order = Order::create([
            'user_id' => $user->id,
            'total_price' => 0,
            'adresse_livraison' => $request->adresse_livraison,
            'phone' => $request->phone,
            'payment_method' => $request->payment_method,
            'status' => 'pending'
        ]);

        foreach ($cart->items as $item) {
            $product = $item->product;

            // 🔥 VÉRIFICATION ET DIMINUTION DU STOCK
            if ($product->stock < $item->quantity) {
                return response()->json([
                    'message' => "Stock insuffisant pour le produit : {$product->name}"
                ], 400);
            }

            $product->decrement('stock', $item->quantity);

            $subtotal = $item->quantity * $product->price;
            $total += $subtotal;

            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $product->price
            ]);
        }

        $order->update(['total_price' => $total]);
        $cart->items()->delete();

        return new OrderResource($order->load('items.product'));
    }

    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Commande non autorisée'], 403);
        }
        return new OrderResource($order->load('items.product'));
    }

    public function adminShow(Order $order)
    {
        return new OrderResource($order->load('items.product', 'user'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate(['status' => 'required|in:pending,preparing,shipping,delivered']);
        $order->update(['status' => $request->status]);
        return response()->json(['message' => 'Status updated', 'order' => $order]);
    }

    public function stats()
    {
        return response()->json([
            'total_orders' => Order::count(),
            'total_revenue' => Order::sum('total_price'),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            
            // 🔥 NOUVEAU : Produits en stock faible pour le Dashboard Admin
            'low_stock_products' => Product::where('stock', '<', 5)
                ->select('id', 'name', 'stock')
                ->get(),

            'orders_per_day' => Order::selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->take(7)
                ->get(),

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
        return response()->json(Order::selectRaw('DATE(created_at) as date, SUM(total_price) as total')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get());
    }

    public function assignLivreur(Request $request, Order $order)
    {
        $request->validate(['livreur_id' => 'required|exists:users,id']);
        $order->update(['livreur_id' => $request->livreur_id, 'status' => 'shipping']);
        return response()->json(['message' => 'Livreur assigné', 'order' => $order]);
    }

    public function livreurs()
    {
        return response()->json(User::where('role', 'livreur')->get());
    }

    public function livreurOrders(Request $request)
    {
        return OrderResource::collection(
            Order::where('livreur_id', $request->user()->id)
                ->with(['items.product', 'user'])
                ->latest()
                ->get()
        );
    }

    public function livreurUpdateStatus(Request $request, Order $order)
    {
        if ($order->livreur_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        $order->update(['status' => 'delivered']);
        return response()->json(['message' => 'Commande livrée ✅', 'order' => $order]);
    }
    public function exportPDF(Request $request)
{
    $date = $request->date ?? now()->toDateString();

    $orders = Order::with('user')
        ->whereDate('created_at', $date)
        ->get();

    $pdf = Pdf::loadView('pdf.orders', compact('orders', 'date'));

    return $pdf->download("orders-$date.pdf");
}
}