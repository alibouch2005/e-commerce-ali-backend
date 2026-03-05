<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    protected $fillable = ['user_id'];


    // Cart n ─── 1 User (un panier appartient à un seul utilisateur)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Cart 1 ─── n CartItem (un panier peut contenir plusieurs items de panier)
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

}
