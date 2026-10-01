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

        foreach (['1year', '3year', '5year', 'yearly'] as $plan) {
            $this->withToken($token)
                ->postJson('/api/create-order', ['plan' => $plan])
                ->assertStatus(500)
                ->assertJsonPath('message', 'Payment gateway is not configured');
        }

        $this->withToken($token)
            ->postJson('/api/create-order', ['plan' => '2year'])
            ->assertStatus(422);
    }

    public function test_subscription_status_returns_content_guidance_for_inactive_user(): void
    {
        $user = User::factory()->create(['phone' => '9876543215']);
        $token = $this->tokenFor($user->id);

        $this->withToken($token)
            ->getJson('/api/subscription-status/'.$user->id)
            ->assertOk()
            ->assertJsonPath('subscription.active', false)
            ->assertJsonPath('content_access.emagazine.visible', true)
            ->assertJsonPath('content_access.emagazine.accessible', false)
            ->assertJsonPath('content_access.print_magazine.accessible', false)
            ->assertJsonPath('content_access.subscription_plans.visible', true)
            ->assertJsonPath('next_action', 'show_magazine_options')
            ->assertJsonCount(3, 'plans');
    }

    public function test_subscription_status_returns_content_guidance_for_active_user(): void
    {
        $user = User::factory()->create(['phone' => '9876543216']);
        Subscription::create([
            'user_id' => $user->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
            'plan_type' => '1year',
            'status' => 'active',
        ]);

        $this->withToken($this->tokenFor($user->id))
            ->getJson('/api/subscription-status/'.$user->id)
            ->assertOk()
            ->assertJsonPath('subscription.active', true)
            ->assertJsonPath('subscription.plan_id', '1')
            ->assertJsonPath('content_access.emagazine.accessible', true)
            ->assertJsonPath('content_access.print_magazine.accessible', true)
            ->assertJsonPath('content_access.subscription_plans.visible', false)
            ->assertJsonPath('next_action', 'open_emagazine_viewer');
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