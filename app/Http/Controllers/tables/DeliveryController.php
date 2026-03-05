<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\Tables\AssignDeliveryRequest;
use App\Http\Requests\Tables\UpdateDeliveryStatusRequest;
use App\Http\Resources\Api\DeliveryResource;

class DeliveryController extends Controller
{

    // ADMIN : assigner livreur
    public function assign(AssignDeliveryRequest $request)
    {

        $order = Order::findOrFail($request->order_id);

        $delivery = Delivery::create([
            'order_id' => $request->order_id,
            'livreur_id' => $request->livreur_id,
            'status' => 'en_preparation'
        ]);

        return new DeliveryResource(
            $delivery->load('order','livreur')
        );
    }

    // LIVREUR : voir ses livraisons
    public function myDeliveries(Request $request)
    {

        $deliveries = Delivery::where('livreur_id',$request->user()->id)
            ->with('order')
            ->latest()
            ->get();

        return DeliveryResource::collection($deliveries);
    }

    // LIVREUR : changer statut
    public function updateStatus(UpdateDeliveryStatusRequest $request, Delivery $delivery)
    {

        $delivery->update([
            'status' => $request->status
        ]);

        if($request->status === 'livree'){
            $delivery->update([
                'date_livree' => now()
            ]);
        }

        return new DeliveryResource($delivery);
    }

}