<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'order_id',
        'livreur_id',
        'status',
        'date_livraison',
    ];

    // Delivery n ─── 1 Order (plusieurs livraisons peuvent être associées à une même commande)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Delivery n ─── 1 User (plusieurs livraisons peuvent être associées à un même livreur)
    public function livreur()
    {
        return $this->belongsTo(User::class, 'livreur_id');
    }
}
