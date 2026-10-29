<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = ['code', 'discount_type', 'discount_value', 'is_active', 'starts_at', 'expires_at', 'usage_limit', 'offer_title', 'offer_message', 'eligible_plans', 'is_test'];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'is_active' => 'boolean',
            'is_test' => 'boolean',
            'starts_at' => 'date',
            'expires_at' => 'date',
            'eligible_plans' => 'array',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }
}