<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetToken;
use App\Models\RevokedToken;
use App\Models\User;
use App\Services\Msg91SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'firstName' => ['required', 'string'],
            'lastName' => ['required', 'string'],
            'phone' => ['required', 'string'],
            'email' => ['nullable'],
            'password' => ['required', 'string'],
            'state' => ['required', 'string'],
            'district' => ['required', 'string'],
            'mandal' => ['required', 'string'],
            'pincode' => ['required', 'string'],
        ]);

        $phone = $this->normalizePhone($data['phone']);
        $email = isset($data['email']) ? trim($data['email']) : null;

        if (User::where('phone', $phone)->exists()) {
            return response()->json(['message' => 'Phone number already registered'], 409);
        }

        $user = User::create([
            'first_name' => trim($data['firstName']),
            'last_name' => trim($data['lastName']),
            'phone' => $phone,
            'email' => $email,
            'password_hash' => Hash::make($data['password']),
            'state' => trim($data['state']),
            'district' => trim($data['district']),
            'mandal' => trim($data['mandal']),
            'pincode' => trim($data['pincode']),
        ]);

        return response()->json(['message' => 'User registered successfully', 'user' => $this->publicUser($user)], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\d{10,15}$/', 'max:15'],
        ]);

        $phone = $this->normalizePhone($data['phone']);
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $otp = (string) random_int(100000, 999999);
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        if ($this->shouldUseTestOtp($user->phone)) {
            return response()->json(['message' => 'Test OTP generated', 'otp' => $otp]);
        }

        if (config('services.msg91.enabled')) {
            try {
                app(Msg91SmsService::class)->sendOtp($user->phone, $otp);
            } catch (\Throwable $exception) {
                report($exception);
                return response()->json(['message' => 'OTP delivery failed'], 502);
            }
        }

        return response()->json(['message' => 'OTP sent successfully']);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\d{10,15}$/', 'max:15'],
            'otp' => ['required', 'numeric', 'digits:6'],
        ]);

        $phone = $this->normalizePhone($data['phone']);
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!$user->otp_code || !$user->otp_expires_at || $user->otp_expires_at->lt(now()) || (string) $user->otp_code !== (string) $data['otp']) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        $payload = ['id' => (int) $user->id, 'phone' => $user->phone, 'role' => $user->role, 'jti' => Str::random(32), 'exp' => time() + 604800];
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$encodedPayload, (string) env('JWT_SECRET', 'supersecretkey'), true));

        return response()->json([
            'message' => 'OTP verified successfully',
            'token' => $header.'.'.$encodedPayload.'.'.$signature,
            'user' => $this->publicUser($user),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = User::find((int) $request->attributes->get('jwt_claims')['id']);
        return $user ? response()->json(['user' => $this->publicUser($user, true)]) : response()->json(['message' => 'User not found'], 404);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'firstName' => ['sometimes', 'required', 'string'],
            'lastName' => ['sometimes', 'required', 'string'],
            'email' => ['sometimes', 'nullable'],
            'state' => ['sometimes', 'required', 'string'],
            'district' => ['sometimes', 'required', 'string'],
            'mandal' => ['sometimes', 'required', 'string'],
            'pincode' => ['sometimes', 'required', 'string'],
            'crop_interests' => ['sometimes', 'array'],
            'crop_interests.*' => ['string'],
        ]);

        $user = User::find($this->userId($request));
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        $updates = [];
        foreach (['state', 'district', 'mandal', 'pincode'] as $field) {
            if (array_key_exists($field, $data)) $updates[$field] = trim($data[$field]);
        }
        if (array_key_exists('firstName', $data)) $updates['first_name'] = trim($data['firstName']);
        if (array_key_exists('lastName', $data)) $updates['last_name'] = trim($data['lastName']);
        if (array_key_exists('email', $data)) $updates['email'] = $data['email'] === null ? null : trim($data['email']);
        if (array_key_exists('crop_interests', $data)) $updates['crop_interests'] = array_values(array_map('trim', $data['crop_interests']));

        $user->update($updates);
        return response()->json(['message' => 'Profile updated successfully', 'user' => $this->publicUser($user->fresh(), true)]);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\d{10,15}$/', 'max:15'],
        ]);

        $user = User::where('phone', $this->normalizePhone($data['phone']))->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $otp = (string) random_int(100000, 999999);
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        if ($this->shouldUseTestOtp($user->phone)) {
            return response()->json(['message' => 'Test OTP generated', 'otp' => $otp]);
        }

        if (config('services.msg91.enabled')) {
            try {
                app(Msg91SmsService::class)->sendOtp($user->phone, $otp);
            } catch (\Throwable $exception) {
                report($exception);
                return response()->json(['message' => 'OTP delivery failed'], 502);
            }
        }

        return response()->json(['message' => 'OTP resent successfully']);
    }

    public function logout(Request $request): JsonResponse
    {
        $claims = $request->attributes->get('jwt_claims');
        if (!empty($claims['jti']) && !empty($claims['exp'])) RevokedToken::firstOrCreate(['token_id' => hash('sha256', $claims['jti'])], ['expires_at' => date('Y-m-d H:i:s', $claims['exp'])]);
        return response()->json(['message' => 'Logged out successfully']);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', trim($phone));
    }

    private function shouldUseTestOtp(string $phone): bool
    {
        $testMode = (bool) config('services.msg91.test_mode', false);
        $configuredPhones = config('services.msg91.test_phones', []);

        if (!$testMode) return false;
        if (!$configuredPhones) return true;

        return in_array($this->normalizePhone($phone), array_map([$this, 'normalizePhone'], $configuredPhones), true);
    }

    private function userId(Request $request): int { return (int) $request->attributes->get('jwt_claims')['id']; }
    private function publicUser(User $user, bool $includeCropInterests = false): array {
        $data = [
            'id' => (int) $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'state' => $user->state,
            'district' => $user->district,
            'mandal' => $user->mandal,
            'pincode' => $user->pincode,
            'role' => $user->role,
        ];

        if ($includeCropInterests) $data['crop_interests'] = $user->crop_interests ?? [];
        return $data;
    }
    private function base64UrlEncode(string $value): string { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }
}
