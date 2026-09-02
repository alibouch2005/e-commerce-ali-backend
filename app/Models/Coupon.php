<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'minimum_amount', 'usage_limit', 'used_count', 'starts_at', 'expires_at', 'is_active'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'expires_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function discountFor(float $amount): float
    {
        if ($amount < $this->minimum_amount) {
            return 0;
        }

        return min($amount, $this->type === 'percent' ? $amount * ($this->value / 100) : $this->value);
    }

    public function isUsable(): bool
    {
        return $this->is_active && (! $this->starts_at || $this->starts_at->isPast()) && (! $this->expires_at || $this->expires_at->isFuture()) && (! $this->usage_limit || $this->used_count < $this->usage_limit);
    }
}
