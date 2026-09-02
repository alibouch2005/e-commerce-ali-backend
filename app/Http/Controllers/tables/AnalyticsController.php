<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    private const EVENTS = [
        'page_view',
        'product_view',
        'add_to_cart',
        'checkout_started',
        'purchase',
    ];

    public function store(Request $request)
    {
        $data = $request->validate([
            'session_id' => 'required|string|max:80',
            'event' => 'required|string|in:'.implode(',', self::EVENTS),
            'path' => 'nullable|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'order_id' => 'nullable|exists:orders,id',
            'metadata' => 'nullable|array',
        ]);

        $data['user_id'] = auth('sanctum')->user()?->id;

        AnalyticsEvent::create($data);

        return response()->json(['ok' => true], 201);
    }

    public function summary(Request $request)
    {
        $days = min((int) $request->input('days', 30), 365);
        $from = now()->subDays($days);
        $base = AnalyticsEvent::where('created_at', '>=', $from);

        $sessions = (clone $base)->distinct('session_id')->count('session_id');
        $productViewSessions = (clone $base)->where('event', 'product_view')->distinct('session_id')->count('session_id');
        $cartSessions = (clone $base)->where('event', 'add_to_cart')->distinct('session_id')->count('session_id');
        $checkoutSessions = (clone $base)->where('event', 'checkout_started')->distinct('session_id')->count('session_id');
        $purchaseSessions = (clone $base)->where('event', 'purchase')->distinct('session_id')->count('session_id');

        return response()->json([
            'days' => $days,
            'visitors' => $sessions,
            'page_views' => (clone $base)->where('event', 'page_view')->count(),
            'product_views' => (clone $base)->where('event', 'product_view')->count(),
            'add_to_cart' => (clone $base)->where('event', 'add_to_cart')->count(),
            'checkout_started' => (clone $base)->where('event', 'checkout_started')->count(),
            'purchases' => (clone $base)->where('event', 'purchase')->count(),
            'rates' => [
                'product_view' => $sessions ? round(($productViewSessions / $sessions) * 100, 1) : 0,
                'cart' => $sessions ? round(($cartSessions / $sessions) * 100, 1) : 0,
                'checkout' => $sessions ? round(($checkoutSessions / $sessions) * 100, 1) : 0,
                'purchase' => $sessions ? round(($purchaseSessions / $sessions) * 100, 1) : 0,
            ],
            'funnel' => [
                ['name' => 'Visiteurs', 'value' => $sessions],
                ['name' => 'Produits vus', 'value' => $productViewSessions],
                ['name' => 'Ajout panier', 'value' => $cartSessions],
                ['name' => 'Checkout', 'value' => $checkoutSessions],
                ['name' => 'Achat', 'value' => $purchaseSessions],
            ],
            'top_products' => AnalyticsEvent::query()
                ->where('event', 'product_view')
                ->where('analytics_events.created_at', '>=', $from)
                ->whereNotNull('product_id')
                ->join('products', 'products.id', '=', 'analytics_events.product_id')
                ->select('products.id', 'products.name', DB::raw('count(*) as views'))
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('views')
                ->take(8)
                ->get(),
        ]);
    }
}
