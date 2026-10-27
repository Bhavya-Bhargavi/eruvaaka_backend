<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class OfferController extends Controller
{
    public function active(): JsonResponse
    {
        $today = Carbon::today()->toDateString();
        $offers = Coupon::query()
            ->where('is_active', true)
            ->whereNotNull('offer_title')
            ->where(function ($query) use ($today): void {
                $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today);
            })
            ->where(function ($query) use ($today): void {
                $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', $today);
            })
            ->orderBy('starts_at')
            ->get();

        return response()->json([
            'offers' => $offers->map(fn (Coupon $coupon): array => [
                'title' => $coupon->offer_title,
                'message' => $coupon->offer_message,
                'coupon_code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (float) $coupon->discount_value,
                'starts_at' => $coupon->starts_at?->toDateString(),
                'ends_at' => $coupon->expires_at?->toDateString(),
                'eligible_plans' => $coupon->eligible_plans ?? [],
            ])->values(),
        ]);
    }
}