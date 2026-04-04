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
