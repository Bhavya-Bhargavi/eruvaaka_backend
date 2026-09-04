<?php

namespace Tests\Feature;

use App\Models\User;
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
            ->assertJsonStructure(['message', 'token', 'user']);
    }
}
