<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_accepts_supported_plans_and_yearly_alias(): void
    {
        config([
            'services.razorpay.key_id' => '',
            'services.razorpay.key_secret' => '',
        ]);

        $user = User::factory()->create(['phone' => '9876543214']);
        $user->update(['otp_code' => '123456', 'otp_expires_at' => now()->addMinutes(5)]);
        $token = $this->postJson('/api/auth/verify-otp', ['phone' => $user->phone, 'otp' => '123456'])
            ->assertStatus(200)
            ->json('token');

        $plans = [
            '1year', '3year', '5year', 'yearly',
            'emagazine_monthly', 'print_magazine_monthly', 'combo_monthly',
            'emagazine_1year', 'emagazine_3year', 'emagazine_5year',
            'print_magazine_1year', 'print_magazine_3year', 'print_magazine_5year',
            'combo_1year', 'combo_3year', 'combo_5year',
        ];
        foreach ($plans as $plan) {
            $payload = ['plan' => $plan];
            if (str_starts_with($plan, 'print_magazine') || str_starts_with($plan, 'combo')) {
                $payload['shipping_address'] = '12 Green Street, Yelahanka, Bengaluru, Karnataka 560064, India';
            }
            $this->withToken($token)
                ->postJson('/api/create-order', $payload)
                ->assertStatus(500)
                ->assertJsonPath('message', 'Payment gateway is not configured');
        }

        $this->withToken($token)
            ->postJson('/api/create-order', ['plan' => '2year'])
            ->assertStatus(422);
    }

    public function test_shipping_address_is_required_for_print_and_combo_orders_only(): void
    {
        config([
            'services.razorpay.key_id' => '',
            'services.razorpay.key_secret' => '',
        ]);
        $user = User::factory()->create(['phone' => '9876543218']);
        $token = $this->tokenFor($user->id);

        $this->withToken($token)
            ->postJson('/api/create-order', ['plan' => 'print_magazine_monthly'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipping_address');

        $this->withToken($token)
            ->postJson('/api/create-order', ['plan' => 'combo_monthly'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shipping_address');

        $this->withToken($token)
            ->postJson('/api/create-order', ['plan' => 'emagazine_monthly'])
            ->assertStatus(500)
            ->assertJsonPath('message', 'Payment gateway is not configured');
    }

    public function test_subscription_status_returns_content_guidance_for_inactive_user(): void
    {
        $user = User::factory()->create(['phone' => '9876543215']);
        $token = $this->tokenFor($user->id);

        $this->withToken($token)
            ->getJson('/api/subscription-status/'.$user->id)
            ->assertOk()
            ->assertJsonPath('is_subscribed', false)
            ->assertJsonPath('expiry_date', null)
            ->assertJsonPath('days_remaining', null)
            ->assertJsonPath('show_expiry_notification', false)
            ->assertJsonPath('subscription.active', false)
            ->assertJsonPath('content_access.emagazine.visible', true)
            ->assertJsonPath('content_access.emagazine.accessible', false)
            ->assertJsonPath('content_access.print_magazine.accessible', false)
            ->assertJsonPath('content_access.subscription_plans.visible', true)
            ->assertJsonPath('next_action', 'show_magazine_options')
            ->assertJsonCount(3, 'plans');

        $this->withToken($token)
            ->getJson('/api/subscription-status/'.$user->id)
            ->assertJsonPath('plans.0.key', 'emagazine_monthly')
            ->assertJsonPath('plans.0.name', 'E-Magazine Monthly')
            ->assertJsonPath('plans.0.price', 99)
            ->assertJsonPath('plans.1.key', 'print_magazine_monthly')
            ->assertJsonPath('plans.1.price', 299)
            ->assertJsonPath('plans.2.key', 'combo_monthly')
            ->assertJsonPath('plans.2.price', 349);
    }

    public function test_subscription_status_returns_content_guidance_for_active_user(): void
    {
        $user = User::factory()->create(['phone' => '9876543216']);
        Subscription::create([
            'user_id' => $user->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(47),
            'plan_type' => '1year',
            'status' => 'active',
        ]);

        $this->withToken($this->tokenFor($user->id))
            ->getJson('/api/subscription-status/'.$user->id)
            ->assertOk()
            ->assertJsonPath('is_subscribed', true)
            ->assertJsonPath('expiry_date', now()->addDays(47)->toDateString())
            ->assertJsonPath('days_remaining', 47)
            ->assertJsonPath('show_expiry_notification', true)
            ->assertJsonPath('subscription.active', true)
            ->assertJsonPath('subscription.plan_id', '1')
            ->assertJsonPath('content_access.emagazine.accessible', true)
            ->assertJsonPath('content_access.print_magazine.accessible', true)
            ->assertJsonPath('content_access.subscription_plans.visible', false)
            ->assertJsonPath('next_action', 'open_emagazine_viewer');
    }

    public function test_print_magazine_plan_does_not_grant_emagazine_access(): void
    {
        $user = User::factory()->create(['phone' => '9876543217']);
        Subscription::create([
            'user_id' => $user->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'plan_type' => 'print_magazine_monthly',
            'status' => 'active',
        ]);

        $this->withToken($this->tokenFor($user->id))
            ->getJson('/api/subscription-status/'.$user->id)
            ->assertOk()
            ->assertJsonPath('content_access.emagazine.accessible', false)
            ->assertJsonPath('content_access.print_magazine.accessible', true);
    }

    private function tokenFor(int $userId): string
    {
        $secret = 'payment-plan-test-secret';
        config(['app.jwt_secret' => $secret]);
        $header = rtrim(strtr(base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['id' => $userId, 'exp' => time() + 3600])), '+/', '-_'), '=');
        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $header.'.'.$payload, $secret, true)), '+/', '-_'), '=');

        return $header.'.'.$payload.'.'.$signature;
    }
}