<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\CheckoutRequest;
use App\Http\Resources\Api\OrderResource;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
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
    private const STATUSES = ['pending', 'preparing', 'shipping', 'delivered', 'cancelled', 'refunded'];

    private const CARD_REQUIRED_FROM = 5000;

    private const CASABLANCA_LAT_MIN = 33.45;

    private const CASABLANCA_LAT_MAX = 33.66;

    private const CASABLANCA_LNG_MIN = -7.82;

    private const CASABLANCA_LNG_MAX = -7.45;

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

        $loyaltyFreeDelivery = $request->user()
            ? $this->isNextDeliveryFreeForClient($request->user()->id)
            : false;

        return response()->json($pricing->quote(
            $data['fulfillment_method'],
            isset($data['delivery_latitude']) ? (float) $data['delivery_latitude'] : null,
            isset($data['delivery_longitude']) ? (float) $data['delivery_longitude'] : null,
            (float) ($data['cart_subtotal'] ?? 0),
            (bool) ($data['product_free_delivery'] ?? false),
            $loyaltyFreeDelivery,
        ));
    }

    public function checkout(CheckoutRequest $request, CmiPaymentService $cmi, DeliveryPricingService $pricing)
    {
        if ($request->payment_method === 'card' && ! $cmi->configured()) {
            return response()->json([
                'message' => 'Paiement CMI non configure. Ajoutez CMI_CLIENT_ID et CMI_STORE_KEY dans .env avant d activer le paiement par carte.',
            ], 503);
        }

        if ($request->fulfillment_method === 'delivery' && ! $this->isCasablancaDelivery($request)) {
            return response()->json([
                'message' => 'Pour le moment, la livraison est disponible seulement a Casablanca.',
            ], 422);
        }

        $result = DB::transaction(function () use ($request, $pricing) {
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
                'delivery_time_slot' => $request->delivery_time_slot,
                'phone' => $request->phone,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'card' ? 'pending' : 'cash_pending',
                'fulfillment_method' => $request->fulfillment_method,
                'status' => 'pending',
            ]);

            $total = 0;
            $adjustments = [];
            foreach ($cart->items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item->product_id);

                if ($product->stock <= 0) {
                    $adjustments[] = "{$product->name}: rupture de stock, retire de la commande.";

                    continue;
                }

                $orderedQuantity = min((int) $item->quantity, (int) $product->stock);
                if ($orderedQuantity < $item->quantity) {
                    $adjustments[] = "{$product->name}: quantite ajustee de {$item->quantity} a {$orderedQuantity}.";
                }

                $product->decrement('stock', $orderedQuantity);
                $total += $orderedQuantity * $item->price;
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $orderedQuantity,
                    'price' => $item->price,
                ]);
            }

            if ($total <= 0 || $order->items()->doesntExist()) {
                abort(response()->json(['message' => 'Tous les produits du panier sont en rupture de stock.'], 422));
            }

            $productFreeDelivery = $order->items()->with('product')->get()->every(fn ($item) => (bool) $item->product?->free_delivery);
            $loyaltyFreeDelivery = $this->isNextDeliveryFreeForClient($user->id);

            $deliveryQuote = $pricing->quote(
                $request->fulfillment_method,
                $request->delivery_latitude !== null ? (float) $request->delivery_latitude : null,
                $request->delivery_longitude !== null ? (float) $request->delivery_longitude : null,
                $total,
                $productFreeDelivery,
                $loyaltyFreeDelivery,
            );

            $discount = 0;
            $coupon = null;
            $couponCode = $request->filled('coupon_code') ? strtoupper(trim($request->coupon_code)) : null;
            if ($couponCode) {
                $coupon = Coupon::where('code', $couponCode)->lockForUpdate()->first();
                if (! $coupon || ! $coupon->isUsableByUser($user->id)) {
                    abort(response()->json(['message' => 'Code promotionnel invalide, deja utilise ou expire.'], 422));
                }

                $discountBase = $total;
                if ($coupon->product_id) {
                    $discountBase = (float) $order->items()
                        ->where('product_id', $coupon->product_id)
                        ->get()
                        ->sum(fn ($item) => (float) $item->price * (int) $item->quantity);

                    if ($discountBase <= 0) {
                        abort(response()->json(['message' => 'Ce code promo est valable seulement sur un produit specifique qui n est pas dans votre panier.'], 422));
                    }
                }

                $discount = $coupon->discountFor((float) $discountBase);
                if ($discount <= 0) {
                    abort(response()->json(['message' => 'Le minimum de commande pour ce code promo n est pas atteint.'], 422));
                }
            }

            $grandTotal = max(0, $total - $discount) + $deliveryQuote['delivery_fee'];
            if ($grandTotal >= self::CARD_REQUIRED_FROM && $request->payment_method !== 'card') {
                abort(response()->json(['message' => 'Paiement par carte obligatoire pour les commandes de 5000 DH ou plus.'], 422));
            }

            $order->update([
                'total_price' => $grandTotal,
                'delivery_fee' => $deliveryQuote['delivery_fee'],
                'delivery_distance_km' => $deliveryQuote['delivery_distance_km'],
                'coupon_code' => $couponCode,
                'discount_amount' => $discount,
            ]);

            if ($coupon) {
                $coupon->increment('used_count');
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                ]);
            }

            $cart->items()->delete();
            StoreNotification::create([
                'user_id' => $user->id,
                'type' => 'order',
                'title' => 'Commande confirmee',
                'message' => "Votre commande #{$order->id} a ete recue.",
                'data' => ['order_id' => $order->id],
            ]);

            if ($order->fulfillment_method === 'delivery' && $order->payment_method === 'cash_on_delivery') {
                $this->notifyAvailableLivreurs($order);
            }

            return ['order' => $order, 'adjustments' => $adjustments];
        });

        $order = $result['order'];
        $order->load(['items.product', 'livreur', 'delivery', 'user']);

        if ($order->payment_method === 'card') {
            return (new OrderResource($order))->additional([
                'payment' => $cmi->paymentForm($order),
                'warnings' => $result['adjustments'],
            ]);
        }

        return (new OrderResource($order))->additional(['warnings' => $result['adjustments']]);
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
        $data = $request->validate([
            'status' => 'required|in:pending,preparing,shipping,delivered,cancelled,refunded',
            'reason' => 'nullable|string|max:255',
        ]);
        DB::transaction(function () use ($order, $data) {
            if ($data['status'] === 'cancelled' && $order->status !== 'cancelled') {
                $order->load('items');
                foreach ($order->items as $item) {
                    Product::whereKey($item->product_id)->lockForUpdate()->increment('stock', $item->quantity);
                }
                $data['cancelled_by'] = 'admin';
                $data['cancelled_at'] = now();
                $data['cancellation_reason'] = $data['reason'] ?? 'Commande annulee par l administration.';
            }

            if ($data['status'] === 'refunded') {
                $data['refund_reason'] = $data['reason'] ?? 'Commande remboursee par l administration.';
            }
            unset($data['reason']);
            $order->update($data);
        });
        $freshOrder = $order->fresh();
        if ($freshOrder->status === 'refunded') {
            $this->notifyCustomer($freshOrder, 'Remboursement confirme', "Votre commande #{$freshOrder->id} a ete marquee comme remboursee. {$freshOrder->refund_reason}");
        } else {
            $this->notifyCustomer($freshOrder, 'Mise a jour de commande', "La commande #{$freshOrder->id} est maintenant : {$freshOrder->status}.");
        }

        return new OrderResource($freshOrder->load(['items.product', 'user', 'livreur']));
    }

    public function cancel(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Vous pouvez annuler seulement avant la preparation.'], 422);
        }

        if ($order->created_at->lt(now()->subMinutes(15))) {
            return response()->json(['message' => 'Le delai d annulation client est depasse. Contactez le support.'], 422);
        }

        DB::transaction(function () use ($order, $data) {
            $order->load('items');
            foreach ($order->items as $item) {
                Product::whereKey($item->product_id)->lockForUpdate()->increment('stock', $item->quantity);
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_by' => 'client',
                'cancelled_at' => now(),
                'cancellation_reason' => $data['reason'] ?? 'Annulee par le client avant preparation.',
            ]);
        });

        $this->notifyCustomer($order, 'Commande annulee', "Votre commande #{$order->id} a ete annulee.");

        return new OrderResource($order->fresh()->load(['items.product', 'livreur', 'delivery']));
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
            'revenue_trends' => $this->revenueTrends(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'product_requests_count' => SupportMessage::where('type', 'product_request')->count(),
            'open_product_requests' => SupportMessage::where('type', 'product_request')->whereIn('status', ['open', 'in_progress'])->count(),
            'requested_products' => $requestedProducts,
            'market_suggestions' => $this->marketSuggestions(),
            'low_stock_products' => Product::where('stock', '<', 10)->select('id', 'name', 'stock')->get(),
            'stock_summary' => ['out_of_stock' => Product::where('stock', 0)->count(), 'critical' => Product::whereBetween('stock', [1, 9])->count()],
            'customer_conversion' => ['registered_clients' => $clientCount, 'buyers' => $buyersCount, 'non_buyers' => $clientCount - $buyersCount, 'rate' => $clientCount ? round(($buyersCount / $clientCount) * 100, 1) : 0],
            'top_customers' => User::where('role', 'client')->withCount('orders')->withSum(['orders as total_spent' => fn ($query) => $query->where('status', 'delivered')], 'total_price')->orderByDesc('total_spent')->take(10)->get(['id', 'name', 'email']),
            'status' => collect(self::STATUSES)->mapWithKeys(fn ($status) => [$status => Order::where('status', $status)->count()]),
            'admin_attention' => [
                'pending_orders' => Order::where('status', 'pending')->count(),
                'urgent_support' => SupportMessage::where('priority', 'urgent')->whereIn('status', ['open', 'in_progress'])->count(),
                'out_of_stock' => Product::where('stock', 0)->count(),
                'low_stock' => Product::whereBetween('stock', [1, 9])->count(),
                'refunds' => Order::where('status', 'refunded')->count(),
            ],
        ]);
    }

    public function salesByDay()
    {
        return response()->json(Order::where('status', 'delivered')->selectRaw('DATE(created_at) as date, SUM(total_price) as total')
            ->groupBy('date')->orderBy('date')->get());
    }

    private function revenueTrends(): array
    {
        $orders = Order::where('status', 'delivered')
            ->orderBy('created_at')
            ->get(['id', 'total_price', 'delivery_fee', 'created_at']);

        $formatGroup = function ($orders, string $periodFormat, string $labelFormat) {
            return $orders
                ->groupBy(fn ($order) => $order->created_at->format($periodFormat))
                ->map(function ($periodOrders, $period) use ($labelFormat) {
                    $total = (float) $periodOrders->sum('total_price');
                    $deliveryFees = (float) $periodOrders->sum('delivery_fee');

                    return [
                        'period' => $period,
                        'label' => $periodOrders->first()->created_at->format($labelFormat),
                        'total' => round($total, 2),
                        'products_revenue' => round($total - $deliveryFees, 2),
                        'delivery_fees' => round($deliveryFees, 2),
                        'orders_count' => $periodOrders->count(),
                    ];
                })
                ->values()
                ->all();
        };

        return [
            'monthly' => $formatGroup($orders->filter(fn ($order) => $order->created_at->gte(now()->subMonths(11)->startOfMonth())), 'Y-m', 'M Y'),
            'yearly' => $formatGroup($orders, 'Y', 'Y'),
        ];
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
                'name' => 'Ecouteurs Bluetooth TWS',
                'category' => 'Electronique',
                'priority' => 'Haute',
                'score' => 94,
                'reason' => 'Produit compact, achat frequent, facile a livrer et tres fort potentiel chez etudiants, sport et transport.',
                'source' => 'Tendances high-tech Maroc',
                'test_price' => '99 - 249 DH',
                'margin_level' => 'Elevee',
            ],
            [
                'name' => 'Power bank 20000 mAh',
                'category' => 'Electronique',
                'priority' => 'Haute',
                'score' => 91,
                'reason' => 'Tres utile au quotidien, bon panier moyen, se vend bien avec telephones et accessoires USB-C.',
                'source' => 'Tendances accessoires smartphones Maroc',
                'test_price' => '149 - 299 DH',
                'margin_level' => 'Moyenne a elevee',
            ],
            [
                'name' => 'Chargeur rapide USB-C 45W/65W',
                'category' => 'Accessoires telephone',
                'priority' => 'Haute',
                'score' => 89,
                'reason' => 'Achat de remplacement tres courant, petit format, livraison rapide et compatible avec beaucoup de smartphones.',
                'source' => 'Tendances mobile commerce Maroc',
                'test_price' => '79 - 169 DH',
                'margin_level' => 'Elevee',
            ],
            [
                'name' => 'Support telephone voiture magnetique',
                'category' => 'Auto & telephone',
                'priority' => 'Haute',
                'score' => 86,
                'reason' => 'Produit impulsif, prix accessible, demande forte chez conducteurs et livreurs.',
                'source' => 'Tendances accessoires auto Maroc',
                'test_price' => '49 - 129 DH',
                'margin_level' => 'Elevee',
            ],
            [
                'name' => 'Ring light avec trepied telephone',
                'category' => 'Createurs & beaute',
                'priority' => 'Haute',
                'score' => 85,
                'reason' => 'Produit tres visuel pour TikTok, live commerce, maquillage, coiffure et petits vendeurs Instagram.',
                'source' => 'Tendances social commerce Maroc',
                'test_price' => '129 - 299 DH',
                'margin_level' => 'Moyenne a elevee',
            ],
            [
                'name' => 'Air fryer compacte 3-4L',
                'category' => 'Maison & cuisine',
                'priority' => 'Haute',
                'score' => 84,
                'reason' => 'Produit cuisine tres demande, bon prix moyen et facile a vendre avec demonstrations video.',
                'source' => 'Tendances petit electromenager Maroc',
                'test_price' => '399 - 899 DH',
                'margin_level' => 'Moyenne',
            ],
            [
                'name' => 'Brosse lissante cheveux',
                'category' => 'Beaute',
                'priority' => 'Moyenne',
                'score' => 82,
                'reason' => 'Avant/apres simple a montrer, bon achat cadeau et forte traction beaute femme.',
                'source' => 'Tendances beaute Maroc',
                'test_price' => '149 - 349 DH',
                'margin_level' => 'Elevee',
            ],
            [
                'name' => 'Mini camera WiFi de securite',
                'category' => 'Electronique',
                'priority' => 'Moyenne',
                'score' => 80,
                'reason' => 'Demande maison, bureau et petit commerce; produit premium avec besoin clair.',
                'source' => 'Tendances securite maison Maroc',
                'test_price' => '199 - 499 DH',
                'margin_level' => 'Moyenne',
            ],
            [
                'name' => 'Mixeur portable rechargeable',
                'category' => 'Maison & cuisine',
                'priority' => 'Moyenne',
                'score' => 78,
                'reason' => 'Produit demo facile: jus, smoothie, sport, bureau; petit et livrable rapidement.',
                'source' => 'Tendances cuisine pratique Maroc',
                'test_price' => '99 - 219 DH',
                'margin_level' => 'Elevee',
            ],
            [
                'name' => 'Montre connectee sport',
                'category' => 'Electronique',
                'priority' => 'Moyenne',
                'score' => 76,
                'reason' => 'Bon produit promo, demande jeune et sport, panier moyen plus interessant que petits accessoires.',
                'source' => 'Tendances wearables Maroc',
                'test_price' => '199 - 599 DH',
                'margin_level' => 'Moyenne',
            ],
            [
                'name' => 'Manette Bluetooth smartphone',
                'category' => 'Gaming',
                'priority' => 'Moyenne',
                'score' => 74,
                'reason' => 'Bon produit pour jeunes et gamers mobile; fonctionne bien en bundle avec support telephone.',
                'source' => 'Tendances gaming mobile Maroc',
                'test_price' => '129 - 299 DH',
                'margin_level' => 'Moyenne',
            ],
            [
                'name' => 'Organisateur rangement cuisine',
                'category' => 'Maison',
                'priority' => 'Moyenne',
                'score' => 72,
                'reason' => 'Produit utile et visuel, facile a vendre avec photos avant/apres et panier multi-quantite.',
                'source' => 'Tendances maison Maroc',
                'test_price' => '39 - 149 DH',
                'margin_level' => 'Elevee',
            ],
        ]);

        $seasonBoosts = [
            'rentree' => [
                'Ecouteurs Bluetooth TWS' => 6,
                'Power bank 20000 mAh' => 5,
                'Manette Bluetooth smartphone' => 4,
            ],
            'black_friday' => [
                'Ecouteurs Bluetooth TWS' => 5,
                'Power bank 20000 mAh' => 5,
                'Air fryer compacte 3-4L' => 5,
            ],
            'ramadan_eid' => [
                'Brosse lissante cheveux' => 6,
                'Air fryer compacte 3-4L' => 5,
                'Organisateur rangement cuisine' => 4,
            ],
            'summer' => [
                'Mixeur portable rechargeable' => 5,
                'Power bank 20000 mAh' => 3,
                'Ring light avec trepied telephone' => 3,
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

    private function isNextDeliveryFreeForClient(int $userId): bool
    {
        $deliveredCount = Order::where('user_id', $userId)
            ->where('fulfillment_method', 'delivery')
            ->where('status', 'delivered')
            ->count();

        return $deliveredCount > 0 && ($deliveredCount + 1) % 5 === 0;
    }

    private function isCasablancaDelivery(Request $request): bool
    {
        if ($request->filled('delivery_latitude') && $request->filled('delivery_longitude')) {
            $latitude = (float) $request->delivery_latitude;
            $longitude = (float) $request->delivery_longitude;

            return $latitude >= self::CASABLANCA_LAT_MIN
                && $latitude <= self::CASABLANCA_LAT_MAX
                && $longitude >= self::CASABLANCA_LNG_MIN
                && $longitude <= self::CASABLANCA_LNG_MAX;
        }

        $address = mb_strtolower((string) $request->adresse_livraison);

        return str_contains($address, 'casablanca') || str_contains($address, 'casa');
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
