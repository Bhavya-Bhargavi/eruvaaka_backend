<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            'address' => '12 Green Street',
            'designation' => ['Farmer'],
            'nationality' => 'Indian',
            'crops_interested' => 'Rice, Cotton',
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
            'address' => '12 Green Street',
            'designation' => ['Farmer'],
            'nationality' => 'Indian',
            'email' => 'not-an-email',
            'crops_interested' => 'Rice, Cotton',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.phone', '9876543211')
            ->assertJsonPath('user.address', '12 Green Street')
            ->assertJsonPath('user.designation', ['Farmer'])
            ->assertJsonPath('user.nationality', 'Indian')
            ->assertJsonPath('user.crop_interests', ['Rice', 'Cotton'])
            ->assertJsonPath('user.crops_interested', 'Rice, Cotton')
            ->assertJsonPath('user.email', 'not-an-email');
    }

    public function test_user_can_register_and_verify_phone_otp(): void
    {
        config([
            'services.infinito_sms.test_mode' => true,
            'services.infinito_sms.test_phones' => [],
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
            'address' => '12 Green Street',
            'designation' => ['Farmer'],
            'nationality' => 'Indian',
            'crops_interested' => 'Rice, Cotton',
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
                'user' => ['id', 'first_name', 'designation', 'nationality'],
                'subscription' => ['active', 'plan_id', 'expiry_date'],
                'plans' => [['id', 'key', 'name', 'type', 'price', 'currency', 'products']],
            ])
            ->assertJsonPath('subscription.active', false)
            ->assertJsonPath('subscription.plan_id', null)
            ->assertJsonPath('subscription.expiry_date', null)
            ->assertJsonPath('plans.0.key', 'emagazine_monthly')
            ->assertJsonPath('plans.0.price', 99)
            ->assertJsonPath('plans.1.key', 'print_magazine_monthly')
            ->assertJsonPath('plans.1.price', 299)
            ->assertJsonPath('plans.2.key', 'combo_monthly')
            ->assertJsonPath('plans.2.price', 349);
    }

    public function test_registration_requires_valid_designation_and_saves_other_details(): void
    {
        $payload = [
            'firstName' => 'Test',
            'lastName' => 'User',
            'phone' => '9876543230',
            'password' => 'Password1',
            'state' => 'Karnataka',
            'district' => 'Bengaluru Urban',
            'mandal' => 'Yelahanka',
            'pincode' => '560064',
            'address' => '12 Green Street',
            'nationality' => 'Indian',
            'designation' => ['Farmer', 'Others'],
        ];

        $this->postJson('/api/auth/register', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('designation');

        $payload['designation'] = ['Others'];
        $payload['designation_other'] = 'Agricultural Consultant';
        $this->postJson('/api/auth/register', $payload)
            ->assertCreated()
            ->assertJsonPath('user.designation', ['Farmer', 'Others'])
            ->assertJsonPath('user.designation_other', 'Agricultural Consultant')
            ->assertJsonPath('user.nationality', 'Indian')
            ->assertJsonPath('user.crop_interests', []);

        $this->assertDatabaseHas('users', [
            'phone' => '9876543230',
            'designation' => '["Farmer","Others"]',
            'designation_other' => 'Agricultural Consultant',
            'nationality' => 'Indian',
        ]);
    }

    public function test_profile_update_can_change_phone_and_address(): void
    {
        config(['services.otp.test_mode' => true]);
        $this->postJson('/api/auth/register', [
            'firstName' => 'Test',
            'lastName' => 'User',
            'phone' => '9876543220',
            'password' => 'Password1',
            'state' => 'Karnataka',
            'district' => 'Bengaluru Urban',
            'mandal' => 'Yelahanka',
            'pincode' => '560064',
            'address' => '12 Green Street',
            'designation' => ['Farmer'],
            'nationality' => 'Indian',
        ])->assertCreated();

        $this->postJson('/api/auth/login', ['phone' => '9876543220'])
            ->assertOk()
            ->assertJsonPath('message', 'Test OTP generated');
        $user = User::where('phone', '9876543220')->firstOrFail();
        $tokenResponse = $this->postJson('/api/auth/verify-otp', [
            'phone' => '9876543220',
            'otp' => $user->otp_code,
        ])->assertOk();

        $this->withToken($tokenResponse->json('token'))
            ->postJson('/api/users/profile', [
                'phone' => '+1 (987) 654-3221',
                'address' => '45 New Road',
            ])
            ->assertOk()
            ->assertJsonPath('user.phone', '19876543221')
            ->assertJsonPath('user.address', '45 New Road');

        $this->assertSame('19876543221', $user->fresh()->phone);
        $this->assertSame('45 New Road', $user->fresh()->address);
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
            'services.infinito_sms.test_mode' => true,
            'services.infinito_sms.test_phones' => [],
        ]);

        User::factory()->create(['phone' => '9876543212']);

        $response = $this->postJson('/api/auth/login', ['phone' => '9876543212']);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Test OTP generated')
            ->assertJsonStructure(['otp']);
    }

    public function test_configured_otp_expiry_is_used_for_login_and_resend(): void
    {
        config([
            'services.otp.expiry_minutes' => 2,
            'services.otp.test_mode' => true,
            'services.otp.test_phone' => '9876543216',
        ]);
        $now = now();
        $this->travelTo($now);
        User::factory()->create(['phone' => '9876543216']);

        $this->postJson('/api/auth/login', ['phone' => '9876543216'])
            ->assertOk()
            ->assertJsonPath('message', 'Test OTP generated');

        $user = User::where('phone', '9876543216')->firstOrFail();
        $this->assertEquals(
            $now->copy()->addMinutes(2)->toDateTimeString(),
            $user->otp_expires_at->toDateTimeString()
        );

        $this->travelTo($now->copy()->addMinute());
        $this->postJson('/api/auth/resend-otp', ['phone' => '9876543216'])
            ->assertOk()
            ->assertJsonPath('message', 'Test OTP generated');

        $this->assertEquals(
            $now->copy()->addMinutes(3)->toDateTimeString(),
            $user->fresh()->otp_expires_at->toDateTimeString()
        );
    }

    public function test_login_sends_otp_through_infinito(): void
    {
        config([
            'services.infinito_sms.enabled' => true,
            'services.infinito_sms.test_mode' => false,
            'services.infinito_sms.endpoint' => 'https://sms.example.test/unified/v2/send',
            'services.infinito_sms.api_token' => 'test-api-token',
            'services.infinito_sms.from' => 'APPROVED',
            'services.infinito_sms.otp_dlt_template_id' => 'otp-template-id',
            'services.infinito_sms.otp_template' => 'Your Eruvaaka OTP is {otp}.',
        ]);
        Http::fake(['*' => Http::response([
            'status' => 'Success',
            'statuscode' => 200,
            'messageack' => ['guids' => [['guid' => 'test-guid', 'errors' => []]]],
        ], 200)]);
        $user = User::factory()->create(['phone' => '9876543215']);

        $this->postJson('/api/auth/login', ['phone' => '9876543215'])
            ->assertOk()
            ->assertJsonPath('message', 'OTP sent successfully');

        $firstOtp = $user->fresh()->otp_code;
        $this->postJson('/api/auth/login', ['phone' => '9876543215'])
            ->assertOk()
            ->assertJsonPath('message', 'OTP sent successfully');

        $this->assertSame($firstOtp, $user->fresh()->otp_code);
        Http::assertSentCount(1);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sms.example.test/unified/v2/send'
            && ($request->data()['sms']['messages'][0]['addresses'][0]['to'] ?? null) === '919876543215');
    }
}
