<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Support\Carbon;

class CouponService
{
    public function quote(string $code, int $userId, int $amountPaise): array
    {
        $coupon = Coupon::query()->whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])->first();

        if (!$coupon || !$coupon->is_active) {
            return $this->invalid('This coupon code is invalid or inactive');
        }

        if ($coupon->starts_at && $coupon->starts_at->toDateString() > Carbon::today()->toDateString()) {
            return $this->invalid('This coupon code is not active yet');
        }

        if ($coupon->expires_at && $coupon->expires_at->toDateString() < Carbon::today()->toDateString()) {
            return $this->invalid('This coupon code has expired');
        }

        if ($coupon->usages()->where('user_id', $userId)->exists()) {
            return $this->invalid('You have already used this coupon code', 409);
        }

        if ($coupon->usage_limit !== null && $coupon->usages()->count() >= $coupon->usage_limit) {
            return $this->invalid('This coupon code has reached its usage limit');
        }

        $value = (float) $coupon->discount_value;
        if ($value <= 0 || !in_array($coupon->discount_type, ['fixed', 'percentage'], true)) {
            return $this->invalid('This coupon code is not configured correctly');
        }

        if ($coupon->discount_type === 'percentage' && $value > 100) {
            return $this->invalid('This coupon code is not configured correctly');
        }

        $discountPaise = $coupon->discount_type === 'fixed'
            ? (int) round($value * 100)
            : (int) round($amountPaise * $value / 100);
        $discountPaise = min($discountPaise, $amountPaise);

        if ($amountPaise <= 0 || $discountPaise >= $amountPaise) {
            return $this->invalid('The coupon discount must be less than the payment amount');
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount_amount' => $discountPaise,
            'final_amount' => $amountPaise - $discountPaise,
        ];
    }

    private function invalid(string $message, int $status = 422): array
    {
        return ['valid' => false, 'message' => $message, 'status' => $status];
    }
}