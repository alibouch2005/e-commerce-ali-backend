<?php

namespace App\Services;

use App\Models\AppSetting;

class StoreSettingsService
{
    public function delivery(): array
    {
        return AppSetting::where('key', 'delivery')->value('value') ?: [
            'free_delivery_enabled' => false,
            'free_delivery_minimum' => 0,
        ];
    }

    public function updateDelivery(array $data): array
    {
        $value = [
            'free_delivery_enabled' => (bool) ($data['free_delivery_enabled'] ?? false),
            'free_delivery_minimum' => max(0, (float) ($data['free_delivery_minimum'] ?? 0)),
        ];

        AppSetting::updateOrCreate(['key' => 'delivery'], ['value' => $value]);

        return $value;
    }
}
