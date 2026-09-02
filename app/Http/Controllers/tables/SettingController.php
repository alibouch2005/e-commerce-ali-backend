<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Services\StoreSettingsService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show(StoreSettingsService $settings)
    {
        return response()->json([
            'delivery' => $settings->delivery(),
        ]);
    }

    public function updateDelivery(Request $request, StoreSettingsService $settings)
    {
        $data = $request->validate([
            'free_delivery_enabled' => 'required|boolean',
            'free_delivery_minimum' => 'nullable|numeric|min:0|max:100000',
        ]);

        return response()->json([
            'delivery' => $settings->updateDelivery($data),
        ]);
    }
}
