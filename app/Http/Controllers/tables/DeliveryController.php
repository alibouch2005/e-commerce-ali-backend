<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\Request;
use App\Http\Requests\Tables\AssignDeliveryRequest;
use App\Http\Requests\Tables\UpdateDeliveryStatusRequest;
use App\Http\Resources\Api\DeliveryResource;

class DeliveryController extends Controller
{
    // 🔥 ADMIN : assigner livreur
    public function assign(AssignDeliveryRequest $request)
    {
        $delivery = Delivery::create([
            'order_id' => $request->order_id,
            'livreur_id' => $request->livreur_id,
            'status' => 'preparing'
        ]);

        return new DeliveryResource(
            $delivery->load('order', 'livreur')
        );
    }

    // 🔥 LIVREUR : voir ses livraisons
    public function myDeliveries(Request $request)
    {
        $deliveries = Delivery::where('livreur_id', $request->user()->id)
            ->with('order')
            ->latest()
            ->get();

        return DeliveryResource::collection($deliveries);
    }

    // 🔥 LIVREUR : changer statut + sync commande
    public function updateStatus(UpdateDeliveryStatusRequest $request, Delivery $delivery)
    {
        // 🔥 update delivery
        $delivery->update([
            'status' => $request->status
        ]);

        // 🔥 mapping status vers Order
        $orderStatus = match($request->status) {
            'preparing' => 'Preparation',
            'in_delivery' => 'Expedie',
            'delivered' => 'Livre',
            default => null
        };

        // 🔥 update order si mapping existe
        if ($orderStatus) {
            $delivery->order->update([
                'status' => $orderStatus
            ]);
        }

        // 🔥 si livré → date livraison
        if ($request->status === 'delivered') {
            $delivery->update([
                'date_livraison' => now()
            ]);
        }

        return new DeliveryResource(
            $delivery->load('order')
        );
    }
}