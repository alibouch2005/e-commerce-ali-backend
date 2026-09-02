<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'event',
        'path',
        'product_id',
        'order_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
