<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'livreur_id',
        'total_price',
        'delivery_fee',
        'delivery_distance_km',
        'adresse_livraison',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_time_slot',
        'phone',
        'payment_method',
        'payment_status',
        'payment_reference',
        'payment_payload',
        'paid_at',
        'fulfillment_method',
        'coupon_code',
        'discount_amount',
        'status',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'refund_reason',
    ];

    protected $casts = [
        'payment_payload' => 'array',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'delivery_fee' => 'decimal:2',
        'delivery_distance_km' => 'decimal:2',
    ];

    // ordre n ─── 1 user (plusieurs commandes peuvent être passées par un même utilisateur)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Order 1 ─── n OrderItem (une commande peut contenir plusieurs items)
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Order 1 ─── 1 Delivery (une commande peut avoir une seule livraison associée)
    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

    // Order n ─── 1 Livreur (plusieurs commandes peuvent être assignées à un même livreur)
    public function livreur()
    {
        return $this->belongsTo(User::class, 'livreur_id');
    }
}
