<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'total_price',
        'adresse_livraison',
        'phone',
        'payment_method',
        'status',
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
}
