<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPhoneOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_number_must_be_unique(): void
    {
        $payload = [
            'firstName' => 'Test',
            'lastName' => 'User',
            'phone' => '9876543210',
            'password' => 'Password1',
            'state' => 'Karnataka',
            'district' => 'Bengaluru Urban',
            'mandal' => 'Yelahanka',
            'pincode' => '560064',
            'crop_interests' => ['Rice', 'Cotton'],
        ];

        $this->postJson('/api/auth/register', $payload)->assertStatus(201);

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Phone number already registered');
    }

    public function test_registration_accepts_email_without_format_validation(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'firstName' => 'Test',
            'lastName' => 'User',
            'phone' => '9876543211',
            'password' => 'Password1',
            'state' => 'Karnataka',
            'district' => 'Bengaluru Urban',
            'mandal' => 'Yelahanka',
            'pincode' => '560064',
            'email' => 'not-an-email',
            'crop_interests' => ['Rice', 'Cotton'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.phone', '9876543211')
            ->assertJsonPath('user.email', 'not-an-email');
    }

    public function test_user_can_register_and_verify_phone_otp(): void
    {
        config([
            'services.razorpay.plans.1year.amount' => 75000,
            'services.razorpay.plans.3year.amount' => 220000,
            'services.razorpay.plans.5year.amount' => 370000,
        ]);

        $registerResponse = $this->postJson('/api/auth/register', [
            'firstName' => 'Test',
            'lastName' => 'User',
            'phone' => '9876543210',
            'password' => 'Password1',
            'state' => 'Karnataka',
            'district' => 'Bengaluru Urban',
            'mandal' => 'Yelahanka',
            'pincode' => '560064',
            'crop_interests' => ['Rice', 'Cotton'],
        ]);

        $registerResponse->assertStatus(201)
            ->assertJsonPath('user.phone', '9876543210');

        $loginResponse = $this->postJson('/api/auth/login', [
            'phone' => '9876543210',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonPath('message', 'OTP sent successfully');

        $user = User::where('phone', '9876543210')->firstOrFail();

        $verifyResponse = $this->postJson('/api/auth/verify-otp', [
            'phone' => '9876543210',
            'otp' => $user->otp_code,
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'first_name'],
                'subscription' => ['active', 'plan_id', 'expiry_date'],
                'plans' => [['id', 'name', 'years', 'price', 'base_yearly_price']],
            ])
            ->assertJsonPath('subscription.active', false)
            ->assertJsonPath('subscription.plan_id', null)
            ->assertJsonPath('subscription.expiry_date', null)
            ->assertJsonPath('plans.0', ['id' => '1', 'name' => '1 Year', 'years' => 1, 'price' => 100, 'base_yearly_price' => 100])
            ->assertJsonPath('plans.1', ['id' => '3', 'name' => '3 Years', 'years' => 3, 'price' => 300, 'base_yearly_price' => 100])
            ->assertJsonPath('plans.2', ['id' => '5', 'name' => '5 Years', 'years' => 5, 'price' => 500, 'base_yearly_price' => 100]);
    }

    public function test_verify_otp_returns_the_users_active_subscribed_plans(): void
    {
        $user = User::factory()->create(['phone' => '9876543213']);
        $user->update(['otp_code' => '123456', 'otp_expires_at' => now()->addMinutes(5)]);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYears(3),
            'plan_type' => '3year',
            'status' => 'active',
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'start_date' => now()->subYears(2),
            'end_date' => now()->subDay(),
            'plan_type' => '1year',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/auth/verify-otp', ['phone' => $user->phone, 'otp' => '123456']);

        $response
            ->assertStatus(200)
            ->assertJsonPath('subscription.active', true)
            ->assertJsonPath('subscription.plan_id', '3')
            ->assertJsonPath('subscription.expiry_date', $subscription->fresh()->end_date->toJSON());
    }

    public function test_test_mode_returns_otp_when_no_phone_allowlist_is_configured(): void
    {
        config([
            'services.msg91.test_mode' => true,
            'services.msg91.test_phones' => [],
        ]);

        User::factory()->create(['phone' => '9876543212']);

        $response = $this->postJson('/api/auth/login', ['phone' => '9876543212']);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Test OTP generated')
            ->assertJsonStructure(['otp']);
    }
}
