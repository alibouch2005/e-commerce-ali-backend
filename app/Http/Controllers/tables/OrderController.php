<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\CheckoutRequest;
use App\Http\Resources\Api\OrderResource;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreNotification;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\CmiPaymentService;
use App\Services\DeliveryPricingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private const STATUSES = ['pending', 'preparing', 'shipping', 'delivered', 'cancelled'];

    public function index(Request $request)
    {
        return OrderResource::collection(Order::where('user_id', $request->user()->id)
            ->with(['items.product', 'livreur', 'delivery'])->latest()->paginate(15));
    }

    public function adminIndex()
    {
        return OrderResource::collection(Order::with(['user', 'livreur', 'items.product', 'delivery'])->latest()->paginate(30));
    }

    public function deliveryQuote(Request $request, DeliveryPricingService $pricing)
    {
        $data = $request->validate([
            'fulfillment_method' => 'required|in:delivery,pickup',
            'delivery_latitude' => 'nullable|numeric|between:-90,90',
            'delivery_longitude' => 'nullable|numeric|between:-180,180',
            'cart_subtotal' => 'nullable|numeric|min:0|max:1000000',
            'product_free_delivery' => 'nullable|boolean',
        ]);

        return response()->json($pricing->quote(
            $data['fulfillment_method'],
            isset($data['delivery_latitude']) ? (float) $data['delivery_latitude'] : null,
            isset($data['delivery_longitude']) ? (float) $data['delivery_longitude'] : null,
            (float) ($data['cart_subtotal'] ?? 0),
            (bool) ($data['product_free_delivery'] ?? false),
        ));
    }

    public function checkout(CheckoutRequest $request, CmiPaymentService $cmi, DeliveryPricingService $pricing)
    {
        if ($request->payment_method === 'card' && ! $cmi->configured()) {
            return response()->json([
                'message' => 'Paiement CMI non configure. Ajoutez CMI_CLIENT_ID et CMI_STORE_KEY dans .env avant d activer le paiement par carte.',
            ], 503);
        }

        $order = DB::transaction(function () use ($request, $pricing) {
            $user = $request->user();
            $cart = Cart::where('user_id', $user->id)->with('items')->lockForUpdate()->first();

            if (! $cart || $cart->items->isEmpty()) {
                abort(response()->json(['message' => 'Panier vide'], 422));
            }

            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => 0,
                'delivery_fee' => 0,
                'delivery_distance_km' => null,
                'adresse_livraison' => $request->adresse_livraison,
                'delivery_latitude' => $request->delivery_latitude,
                'delivery_longitude' => $request->delivery_longitude,
                'phone' => $request->phone,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'card' ? 'pending' : 'cash_pending',
                'fulfillment_method' => $request->fulfillment_method,
                'status' => 'pending',
            ]);

            $total = 0;
            foreach ($cart->items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item->product_id);
                if ($product->stock < $item->quantity) {
                    abort(response()->json(['message' => "Stock insuffisant pour : {$product->name}"], 422));
                }

                $product->decrement('stock', $item->quantity);
                $total += $item->quantity * $item->price;
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ]);
            }

            $productFreeDelivery = $cart->items->every(function ($item) {
                return (bool) Product::whereKey($item->product_id)->value('free_delivery');
            });

            $deliveryQuote = $pricing->quote(
                $request->fulfillment_method,
                $request->delivery_latitude !== null ? (float) $request->delivery_latitude : null,
                $request->delivery_longitude !== null ? (float) $request->delivery_longitude : null,
                $total,
                $productFreeDelivery,
            );

            $discount = 0;
            $couponCode = $request->filled('coupon_code') ? strtoupper(trim($request->coupon_code)) : null;
            if ($couponCode) {
                $coupon = Coupon::where('code', $couponCode)->lockForUpdate()->first();
                if (! $coupon || ! $coupon->isUsable()) {
                    abort(response()->json(['message' => 'Code promotionnel invalide ou expiré.'], 422));
                }
                $discount = $coupon->discountFor((float) $total);
                if ($discount <= 0) {
                    abort(response()->json(['message' => 'Le minimum de commande pour ce code promo n’est pas atteint.'], 422));
                }
                $coupon->increment('used_count');
            }

            $order->update([
                'total_price' => max(0, $total - $discount) + $deliveryQuote['delivery_fee'],
                'delivery_fee' => $deliveryQuote['delivery_fee'],
                'delivery_distance_km' => $deliveryQuote['delivery_distance_km'],
                'coupon_code' => $couponCode,
                'discount_amount' => $discount,
            ]);
            $cart->items()->delete();
            StoreNotification::create([
                'user_id' => $user->id,
                'type' => 'order',
                'title' => 'Commande confirmée',
                'message' => "Votre commande #{$order->id} a été reçue.",
                'data' => ['order_id' => $order->id],
            ]);

            if ($order->fulfillment_method === 'delivery' && $order->payment_method === 'cash_on_delivery') {
                $this->notifyAvailableLivreurs($order);
            }

            return $order;
        });

        $order->load(['items.product', 'livreur', 'delivery', 'user']);

        if ($order->payment_method === 'card') {
            return (new OrderResource($order))->additional([
                'payment' => $cmi->paymentForm($order),
            ]);
        }

        return new OrderResource($order);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return new OrderResource($order->load(['items.product', 'livreur', 'delivery']));
    }

    public function adminShow(Order $order)
    {
        return new OrderResource($order->load(['items.product', 'user', 'livreur', 'delivery']));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate(['status' => 'required|in:pending,preparing,shipping,delivered,cancelled']);
        DB::transaction(function () use ($order, $data) {
            if ($data['status'] === 'cancelled' && $order->status !== 'cancelled') {
                $order->load('items');
                foreach ($order->items as $item) {
                    Product::whereKey($item->product_id)->lockForUpdate()->increment('stock', $item->quantity);
                }
            }
            $order->update($data);
        });
        $this->notifyCustomer($order, 'Mise à jour de commande', "La commande #{$order->id} est maintenant : {$data['status']}.");

        return new OrderResource($order->fresh()->load(['items.product', 'user', 'livreur']));
    }

    public function assignLivreur(Request $request, Order $order)
    {
        $data = $request->validate(['livreur_id' => 'required|exists:users,id']);
        $livreur = User::whereKey($data['livreur_id'])->where('role', 'livreur')->firstOrFail();

        DB::transaction(function () use ($order, $livreur) {
            $order->update(['livreur_id' => $livreur->id, 'status' => 'shipping']);
            Delivery::updateOrCreate(['order_id' => $order->id], [
                'livreur_id' => $livreur->id,
                'status' => 'shipping',
            ]);
            $this->notifyCustomer($order, 'Livreur assigné', "Votre commande #{$order->id} est en cours de livraison.");
            StoreNotification::create([
                'user_id' => $livreur->id,
                'type' => 'delivery',
                'title' => 'Nouvelle livraison',
                'message' => "La commande #{$order->id} vous a été assignée.",
                'data' => ['order_id' => $order->id],
            ]);
        });

        return new OrderResource($order->fresh()->load(['user', 'livreur', 'items.product']));
    }

    public function livreurs()
    {
        return response()->json(User::where('role', 'livreur')->select('id', 'name', 'email', 'phone')->orderBy('name')->get());
    }

    public function livreurOrders(Request $request)
    {
        return OrderResource::collection(Order::where('fulfillment_method', 'delivery')
            ->where(function ($query) use ($request) {
                $query->where('livreur_id', $request->user()->id)
                    ->orWhere(function ($available) {
                        $available->whereNull('livreur_id')
                            ->whereIn('status', ['pending', 'preparing'])
                            ->where(function ($payment) {
                                $payment->where('payment_method', 'cash_on_delivery')
                                    ->orWhere('payment_status', 'paid');
                            });
                    });
            })
            ->with(['items.product', 'user', 'delivery'])
            ->latest()
            ->paginate(20));
    }

    public function acceptDelivery(Request $request, Order $order)
    {
        $livreur = $request->user();

        $accepted = DB::transaction(function () use ($order, $livreur) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->fulfillment_method !== 'delivery') {
                return ['ok' => false, 'message' => 'Cette commande est en retrait magasin.'];
            }

            if ($lockedOrder->livreur_id && $lockedOrder->livreur_id !== $livreur->id) {
                return ['ok' => false, 'message' => 'Cette livraison a deja ete acceptee par un autre livreur.'];
            }

            if (! in_array($lockedOrder->status, ['pending', 'preparing', 'shipping'], true)) {
                return ['ok' => false, 'message' => 'Cette commande ne peut plus etre acceptee.'];
            }

            if ($lockedOrder->payment_method === 'card' && $lockedOrder->payment_status !== 'paid') {
                return ['ok' => false, 'message' => 'Paiement carte non confirme.'];
            }

            $lockedOrder->update([
                'livreur_id' => $livreur->id,
                'status' => 'shipping',
            ]);

            Delivery::updateOrCreate(['order_id' => $lockedOrder->id], [
                'livreur_id' => $livreur->id,
                'status' => 'shipping',
            ]);

            return ['ok' => true, 'order' => $lockedOrder];
        });

        if (! $accepted['ok']) {
            return response()->json(['message' => $accepted['message']], 409);
        }

        $this->notifyCustomer($accepted['order'], 'Livreur assigne', "Votre commande #{$accepted['order']->id} a ete acceptee par un livreur.");

        return new OrderResource($accepted['order']->fresh()->load(['items.product', 'user', 'livreur', 'delivery']));
    }

    public function livreurUpdateStatus(Request $request, Order $order)
    {
        abort_unless($order->livreur_id === $request->user()->id, 403);
        $data = $request->validate([
            'status' => 'required|in:shipping,delivered',
            'recipient_name' => 'required_if:status,delivered|nullable|string|max:255',
            'delivery_note' => 'nullable|string|max:1000',
            'proof_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);
        if ($data['status'] === 'delivered' && ! $request->hasFile('proof_image')) {
            return response()->json(['message' => 'Une photo de preuve de livraison est obligatoire.'], 422);
        }
        $order->update(['status' => $data['status']]);
        $deliveryData = [
            'status' => $data['status'],
            'date_livraison' => $data['status'] === 'delivered' ? now() : null,
        ];
        if ($data['status'] === 'delivered') {
            $deliveryData['recipient_name'] = $data['recipient_name'];
            $deliveryData['delivery_note'] = $data['delivery_note'] ?? null;
            $deliveryData['proof_image'] = '/storage/'.$request->file('proof_image')->store('delivery-proofs', 'public');
        }
        Delivery::where('order_id', $order->id)->update($deliveryData);
        $this->notifyCustomer($order, 'Suivi de livraison', "La commande #{$order->id} est maintenant : {$data['status']}.");

        return new OrderResource($order->fresh()->load(['items.product', 'user', 'delivery']));
    }

    public function stats()
    {
        $clientCount = User::where('role', 'client')->count();
        $buyersCount = User::where('role', 'client')->has('orders')->count();
        $requestedProducts = SupportMessage::where('type', 'product_request')
            ->whereNotNull('requested_product_name')
            ->where(function ($query) {
                $query->whereNull('requested_product_city')->orWhere('requested_product_city', 'Casablanca');
            })
            ->latest()
            ->take(12)
            ->get([
                'id',
                'name',
                'requested_product_name',
                'requested_product_city',
                'requested_product_image',
                'message',
                'status',
                'created_at',
            ]);

        return response()->json([
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('total_price'),
            'revenue_breakdown' => [
                'delivered_products' => Order::where('status', 'delivered')->sum(DB::raw('total_price - COALESCE(delivery_fee, 0)')),
                'delivery_fees' => Order::where('status', 'delivered')->sum('delivery_fee'),
                'pending_revenue' => Order::whereIn('status', ['pending', 'preparing', 'shipping'])->sum('total_price'),
            ],
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'product_requests_count' => SupportMessage::where('type', 'product_request')->count(),
            'open_product_requests' => SupportMessage::where('type', 'product_request')->whereIn('status', ['open', 'in_progress'])->count(),
            'requested_products' => $requestedProducts,
            'market_suggestions' => $this->marketSuggestions(),
            'low_stock_products' => Product::where('stock', '<', 5)->select('id', 'name', 'stock')->get(),
            'stock_summary' => ['out_of_stock' => Product::where('stock', 0)->count(), 'critical' => Product::whereBetween('stock', [1, 4])->count()],
            'customer_conversion' => ['registered_clients' => $clientCount, 'buyers' => $buyersCount, 'non_buyers' => $clientCount - $buyersCount, 'rate' => $clientCount ? round(($buyersCount / $clientCount) * 100, 1) : 0],
            'top_customers' => User::where('role', 'client')->withCount('orders')->withSum(['orders as total_spent' => fn ($query) => $query->where('status', 'delivered')], 'total_price')->orderByDesc('total_spent')->take(10)->get(['id', 'name', 'email']),
            'status' => collect(self::STATUSES)->mapWithKeys(fn ($status) => [$status => Order::where('status', $status)->count()]),
        ]);
    }

    public function salesByDay()
    {
        return response()->json(Order::where('status', 'delivered')->selectRaw('DATE(created_at) as date, SUM(total_price) as total')
            ->groupBy('date')->orderBy('date')->get());
    }

    public function exportPDF(Request $request)
    {
        $date = $request->validate(['date' => 'nullable|date'])['date'] ?? now()->toDateString();
        $orders = Order::with('user')->whereDate('created_at', $date)->get();

        return Pdf::loadView('pdf.orders', compact('orders', 'date'))->download("orders-{$date}.pdf");
    }

    public function receipt(Request $request, Order $order)
    {
        abort_unless($request->user()->role === 'admin' || $order->user_id === $request->user()->id, 403);

        $order->load(['user', 'livreur', 'items.product', 'delivery']);

        return Pdf::loadView('pdf.order_receipt', compact('order'))->download("receipt-order-{$order->id}.pdf");
    }

    private function marketSuggestions(): array
    {
        $month = (int) now()->format('n');
        $season = match (true) {
            in_array($month, [8, 9], true) => 'rentree',
            in_array($month, [11, 12], true) => 'black_friday',
            in_array($month, [3, 4], true) => 'ramadan_eid',
            in_array($month, [6, 7], true) => 'summer',
            default => 'normal',
        };

        $marketBaseline = collect([
            [
                'name' => 'Accessoires smartphones',
                'category' => 'Electronique',
                'priority' => 'Haute',
                'score' => 91,
                'reason' => 'Smartphones dominent l achat mobile; coques, chargeurs rapides, supports voiture et power banks sont faciles a vendre et livrer.',
                'source' => 'Marche Maroc 2026: electronique + mobile commerce',
            ],
            [
                'name' => 'Ecouteurs Bluetooth et casques',
                'category' => 'Electronique',
                'priority' => 'Haute',
                'score' => 88,
                'reason' => 'Produit compact, prix accessible, demande forte chez etudiants, jeunes actifs, gaming et sport.',
                'source' => 'Tendance accessoires high-tech Maroc',
            ],
            [
                'name' => 'Petits electromenagers cuisine',
                'category' => 'Maison & cuisine',
                'priority' => 'Haute',
                'score' => 86,
                'reason' => 'Air fryer, mixeur, bouilloire, balance cuisine: utiles, visuels, bons pour videos courtes et paniers moyens.',
                'source' => 'Tendances maison/electronique Maroc',
            ],
            [
                'name' => 'Parfums et coffrets beaute',
                'category' => 'Beaute',
                'priority' => 'Haute',
                'score' => 85,
                'reason' => 'Categorie legere avec bonne marge; coffrets, parfums et soins marchent bien en social commerce.',
                'source' => 'Tendances beaute Maroc 2026',
            ],
            [
                'name' => 'Soins peau et cheveux',
                'category' => 'Beaute & bien-etre',
                'priority' => 'Haute',
                'score' => 83,
                'reason' => 'Les produits avant/apres se vendent bien: serum, creme, huile capillaire, protection solaire.',
                'source' => 'Tendances beaute/social commerce Maroc',
            ],
            [
                'name' => 'Articles rangement maison',
                'category' => 'Maison',
                'priority' => 'Moyenne',
                'score' => 79,
                'reason' => 'Organisateurs cuisine, boites rangement, etageres compactes: utile, visuel, facile a expliquer.',
                'source' => 'Tendances home goods',
            ],
            [
                'name' => 'Gaming accessoires',
                'category' => 'Electronique',
                'priority' => 'Moyenne',
                'score' => 77,
                'reason' => 'Souris, tapis, clavier, manette, support telephone: bons produits d entree pour catalogue electronique.',
                'source' => 'Tendances loisirs/electronique',
            ],
            [
                'name' => 'Produits bebe pratiques',
                'category' => 'Bebe & enfants',
                'priority' => 'Moyenne',
                'score' => 74,
                'reason' => 'Accessoires utiles, jouets educatifs, veilleuses et rangement bebe peuvent convertir avec preuve visuelle.',
                'source' => 'Tendances categories Maroc',
            ],
            [
                'name' => 'Epicerie premium et snacks',
                'category' => 'Alimentaire',
                'priority' => 'Moyenne',
                'score' => 72,
                'reason' => 'Livraison urbaine en croissance; bon complement si stock rapide et local Casablanca.',
                'source' => 'Croissance grocery/food delivery Maroc',
            ],
        ]);

        $seasonBoosts = [
            'rentree' => [
                'Accessoires smartphones' => 6,
                'Ecouteurs Bluetooth et casques' => 5,
                'Gaming accessoires' => 4,
            ],
            'black_friday' => [
                'Accessoires smartphones' => 5,
                'Ecouteurs Bluetooth et casques' => 5,
                'Petits electromenagers cuisine' => 5,
            ],
            'ramadan_eid' => [
                'Parfums et coffrets beaute' => 7,
                'Petits electromenagers cuisine' => 5,
                'Articles rangement maison' => 4,
            ],
            'summer' => [
                'Soins peau et cheveux' => 5,
                'Accessoires smartphones' => 3,
                'Epicerie premium et snacks' => 3,
            ],
            'normal' => [],
        ];

        return $marketBaseline
            ->map(function ($item) use ($season, $seasonBoosts) {
                $boost = $seasonBoosts[$season][$item['name']] ?? 0;
                $item['score'] = min(100, $item['score'] + $boost);
                $item['season'] = $season;
                $item['updated_at'] = now()->toDateString();

                return $item;
            })
            ->sortByDesc('score')
            ->take(10)
            ->values()
            ->all();
    }

    private function notifyCustomer(Order $order, string $title, string $message): void
    {
        StoreNotification::create(['user_id' => $order->user_id, 'type' => 'order', 'title' => $title, 'message' => $message, 'data' => ['order_id' => $order->id]]);
    }

    private function notifyAvailableLivreurs(Order $order): void
    {
        User::where('role', 'livreur')->each(fn ($livreur) => StoreNotification::create([
            'user_id' => $livreur->id,
            'type' => 'delivery_offer',
            'title' => 'Nouvelle livraison disponible',
            'message' => "Commande #{$order->id} disponible a accepter.",
            'data' => ['order_id' => $order->id],
        ]));
    }
}
