<?php

namespace App\Services;

class DeliveryPricingService
{
    private const STORE_LATITUDE = 33.55244;

    private const STORE_LONGITUDE = -7.67712;

    private const BASE_FEE = 5.0;

    private const PRICE_PER_KM = 4.0;

    private const MIN_DELIVERY_FEE = 5.0;

    private const FALLBACK_DELIVERY_FEE = 30.0;

    public function __construct(private readonly StoreSettingsService $settings) {}

    public function quote(string $fulfillmentMethod, ?float $latitude = null, ?float $longitude = null, float $cartSubtotal = 0, bool $productFreeDelivery = false, bool $loyaltyFreeDelivery = false): array
    {
        $deliverySettings = $this->settings->delivery();
        $freeDeliveryEnabled = (bool) ($deliverySettings['free_delivery_enabled'] ?? false);
        $freeDeliveryMinimum = (float) ($deliverySettings['free_delivery_minimum'] ?? 0);

        if ($fulfillmentMethod === 'pickup') {
            return [
                'delivery_fee' => 0.0,
                'delivery_distance_km' => 0.0,
                'is_estimated' => false,
            ];
        }

        if ($productFreeDelivery || $loyaltyFreeDelivery || ($freeDeliveryEnabled && $cartSubtotal >= $freeDeliveryMinimum)) {
            return [
                'delivery_fee' => 0.0,
                'delivery_distance_km' => $latitude !== null && $longitude !== null
                    ? round($this->distanceKm(self::STORE_LATITUDE, self::STORE_LONGITUDE, $latitude, $longitude), 2)
                    : null,
                'is_estimated' => false,
                'free_delivery' => true,
                'free_delivery_reason' => $loyaltyFreeDelivery ? 'loyalty_5th_order' : ($productFreeDelivery ? 'product' : 'global'),
            ];
        }

        if ($latitude === null || $longitude === null) {
            return [
                'delivery_fee' => self::FALLBACK_DELIVERY_FEE,
                'delivery_distance_km' => null,
                'is_estimated' => true,
                'free_delivery' => false,
            ];
        }

        $distance = $this->distanceKm(self::STORE_LATITUDE, self::STORE_LONGITUDE, $latitude, $longitude);
        $fee = max(self::MIN_DELIVERY_FEE, self::BASE_FEE + ($distance * self::PRICE_PER_KM));

        return [
            'delivery_fee' => $this->roundMoney($fee),
            'delivery_distance_km' => round($distance, 2),
            'is_estimated' => false,
            'free_delivery' => false,
        ];
    }

    private function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function roundMoney(float $amount): float
    {
        return round($amount * 2) / 2;
    }
}
