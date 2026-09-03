<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $itemsLoaded = $this->relationLoaded('items');
        $itemsSubtotal = $itemsLoaded
            ? round((float) $this->items->sum(fn ($item) => (float) $item->price * (int) $item->quantity), 2)
            : null;
        $deliveryFee = (float) ($this->delivery_fee ?? 0);
        $discountAmount = (float) ($this->discount_amount ?? 0);
        $computedTotal = $itemsLoaded
            ? round(max(0, (float) $itemsSubtotal - $discountAmount) + $deliveryFee, 2)
            : (float) $this->total_price;
        $freeDeliveryReason = $deliveryFee <= 0 && $this->fulfillment_method === 'delivery'
            ? $this->resolveFreeDeliveryReason()
            : null;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'livreur_id' => $this->livreur_id, // Ajouté pour le suivi
            'total_price' => $this->total_price,
            'items_subtotal' => $itemsSubtotal,
            'computed_total' => $computedTotal,
            'delivery_fee' => $this->delivery_fee,
            'delivery_distance_km' => $this->delivery_distance_km,
            'status' => $this->status,
            'adresse_livraison' => $this->adresse_livraison,
            'delivery_latitude' => $this->delivery_latitude,
            'delivery_longitude' => $this->delivery_longitude,
            'delivery_time_slot' => $this->delivery_time_slot,
            'free_delivery_reason' => $freeDeliveryReason,
            'can_client_cancel' => $this->status === 'pending' && $this->created_at?->gte(now()->subMinutes(15)),
            'phone' => $this->phone,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'payment_reference' => $this->payment_reference,
            'paid_at' => $this->paid_at,
            'fulfillment_method' => $this->fulfillment_method,
            'coupon_code' => $this->coupon_code,
            'discount_amount' => $this->discount_amount,
            'created_at' => $this->created_at, // Utile pour la date
            'cancelled_by' => $this->cancelled_by,
            'cancelled_at' => $this->cancelled_at,
            'cancellation_reason' => $this->cancellation_reason,
            'refund_reason' => $this->refund_reason,

            // 🔥 CHARGEMENT DES RELATIONS
            // On utilise $this->user pour récupérer l'objet client
            'user' => $this->whenLoaded('user'),

            // Tes articles
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'delivery' => $this->whenLoaded('delivery'),
        ];
    }

    private function resolveFreeDeliveryReason(): ?string
    {
        if (! $this->relationLoaded('items')) {
            return null;
        }

        return $this->items->every(fn ($item) => (bool) $item->product?->free_delivery)
            ? 'product'
            : 'loyalty_or_global';
    }
}
