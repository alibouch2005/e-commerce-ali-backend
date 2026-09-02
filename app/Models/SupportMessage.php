<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    protected $fillable = [
        'user_id',
        'answered_by',
        'type',
        'name',
        'email',
        'subject',
        'requested_product_name',
        'requested_product_image',
        'requested_product_city',
        'message',
        'priority',
        'status',
        'admin_reply',
        'answered_at',
        'closed_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'answered_by');
    }
}
