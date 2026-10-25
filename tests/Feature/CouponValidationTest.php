<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_quote_returns_discount_and_rejects_reuse_by_same_user(): void
    {
        config(['services.razorpay.plans.1year.amount' => 75000]);
        $coupon = Coupon::create([
            'code' => 'SAVE500',
            'discount_type' => 'fixed',
            'discount_value' => 500,
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'discount_amount' => 50000,
            'used_at' => now(),
        ]);

        $this->withToken($this->tokenFor($otherUser->id))
            ->postJson('/api/coupon/validate', ['coupon_code' => 'save500', 'plan' => '1year'])
            ->assertOk()
            ->assertExactJson(['valid' => true, 'original_amount' => 750, 'discount' => 500, 'final_amount' => 250, 'currency' => 'INR']);

        $this->withToken($this->tokenFor($user->id))
            ->postJson('/api/coupon/validate', ['coupon_code' => 'SAVE500', 'plan' => '1year'])
            ->assertStatus(409)
            ->assertJsonPath('valid', false)
            ->assertJsonPath('message', 'You have already used this coupon code');
    }

    public function test_coupon_validation_rejects_expired_inactive_and_global_limit(): void
    {
        config(['services.razorpay.plans.1year.amount' => 75000]);
        $expired = Coupon::create([
            'code' => 'EXPIRED',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'expires_at' => now()->subDay()->toDateString(),
        ]);
        $inactive = Coupon::create([
            'code' => 'INACTIVE',
            'discount_type' => 'fixed',
            'discount_value' => 100,
            'is_active' => false,
        ]);
        $limited = Coupon::create([
            'code' => 'LIMITED',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'usage_limit' => 1,
        ]);
        $usedBy = User::factory()->create();
        CouponUsage::create([
            'coupon_id' => $limited->id,
            'user_id' => $usedBy->id,
            'discount_amount' => 7500,
            'used_at' => now(),
        ]);
        $user = User::factory()->create();
        $token = $this->tokenFor($user->id);

        foreach ([$expired, $inactive, $limited] as $coupon) {
            $this->withToken($token)
                ->postJson('/api/coupon/validate', ['coupon_code' => $coupon->code, 'plan' => '1year'])
                ->assertStatus(422)
                ->assertJsonPath('valid', false);
        }
    }

    public function test_coupon_validation_uses_selected_magazine_plan_price(): void
    {
        config(['services.razorpay.plans.emagazine_monthly.amount' => 9900]);
        $coupon = Coupon::create([
            'code' => 'EMAG10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);
        $user = User::factory()->create();

        $this->withToken($this->tokenFor($user->id))
            ->postJson('/api/coupon/validate', [
                'coupon_code' => $coupon->code,
                'plan' => 'emagazine_monthly',
            ])
            ->assertOk()
            ->assertExactJson([
                'valid' => true,
                'original_amount' => 99,
                'discount' => 9.9,
                'final_amount' => 89.1,
                'currency' => 'INR',
            ]);
    }

    private function tokenFor(int $userId): string
    {
        $secret = 'coupon-validation-test-secret';
        config(['app.jwt_secret' => $secret]);
        $header = rtrim(strtr(base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['id' => $userId, 'exp' => time() + 3600])), '+/', '-_'), '=');
        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $header.'.'.$payload, $secret, true)), '+/', '-_'), '=');

        return $header.'.'.$payload.'.'.$signature;
    }
}