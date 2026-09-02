<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 12px; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 18px; }
        .brand { font-size: 22px; font-weight: bold; color: #111827; }
        .muted { color: #6b7280; }
        .grid { width: 100%; margin-bottom: 18px; }
        .grid td { vertical-align: top; width: 50%; padding: 4px 0; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.items th { background: #111827; color: #fff; padding: 9px; text-align: left; }
        table.items td { border-bottom: 1px solid #e5e7eb; padding: 9px; }
        .right { text-align: right; }
        .total-box { margin-top: 16px; width: 45%; margin-left: auto; }
        .total-row { display: flex; justify-content: space-between; border-bottom: 1px solid #e5e7eb; padding: 7px 0; }
        .grand-total { font-size: 16px; font-weight: bold; color: #4f46e5; }
        .footer { margin-top: 32px; font-size: 10px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">E-commerce Ali Bouchouar</div>
        <div class="muted">Recu de commande #{{ $order->id }}</div>
    </div>

    <table class="grid">
        <tr>
            <td>
                <strong>Client</strong><br>
                {{ $order->user->name ?? 'Client' }}<br>
                {{ $order->user->email ?? '' }}<br>
                {{ $order->phone }}
            </td>
            <td class="right">
                <strong>Date</strong><br>
                {{ $order->created_at?->format('Y-m-d H:i') }}<br><br>
                <strong>Mode</strong><br>
                {{ $order->fulfillment_method === 'pickup' ? 'Retrait local' : 'Livraison' }}<br>
                {{ $order->payment_method }}
            </td>
        </tr>
    </table>

    @if($order->fulfillment_method === 'delivery')
        <p><strong>Adresse de livraison:</strong> {{ $order->adresse_livraison }}</p>
    @else
        <p><strong>Retrait:</strong> Commande a recuperer localement par le client.</p>
    @endif

    <table class="items">
        <thead>
            <tr>
                <th>Produit</th>
                <th class="right">Prix</th>
                <th class="right">Qte</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'Produit' }}</td>
                    <td class="right">{{ number_format($item->price, 2) }} DH</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ number_format($item->price * $item->quantity, 2) }} DH</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $itemsTotal = $order->items->sum(fn ($item) => $item->price * $item->quantity);
    @endphp

    <div class="total-box">
        <div class="total-row"><span>Sous-total</span><span>{{ number_format($itemsTotal, 2) }} DH</span></div>
        <div class="total-row"><span>Remise</span><span>{{ number_format($order->discount_amount ?? 0, 2) }} DH</span></div>
        <div class="total-row"><span>Frais de livraison</span><span>{{ number_format($order->delivery_fee ?? 0, 2) }} DH</span></div>
        @if($order->delivery_distance_km)
            <div class="total-row"><span>Distance livraison</span><span>{{ number_format($order->delivery_distance_km, 2) }} km</span></div>
        @endif
        <div class="total-row grand-total"><span>Total paye</span><span>{{ number_format($order->total_price, 2) }} DH</span></div>
    </div>

    <div class="footer">
        Document genere automatiquement le {{ now()->format('Y-m-d H:i') }}.
    </div>
</body>
</html>
