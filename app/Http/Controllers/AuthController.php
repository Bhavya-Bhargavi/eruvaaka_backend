<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetToken;
use App\Models\RevokedToken;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InfinitoSmsService;
use App\Services\MagazinePlanCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
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
            'address' => ['required', 'string'],
            'designation' => ['required', 'array', 'size:1'],
            'designation.*' => ['required', 'string', Rule::in($this->designations())],
            'designation_other' => [Rule::requiredIf(fn (): bool => in_array('Others', (array) $request->input('designation'), true)), 'nullable', 'string', 'max:255'],
            'nationality' => ['required', 'string', 'max:100'],
            'crops_interested' => ['nullable', 'string', 'max:2000'],
            'crop_interests' => ['nullable', $this->cropInterestsRule()],
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
            'address' => trim($data['address']),
            'designation' => $data['designation'],
            'designation_other' => in_array('Others', $data['designation'], true) ? trim($data['designation_other']) : null,
            'nationality' => trim($data['nationality']),
            'crop_interests' => $this->normalizeCropInterests($data['crops_interested'] ?? $data['crop_interests'] ?? null),
        ]);

        return response()->json(['message' => 'User registered successfully', 'user' => $this->publicUser($user, true)], 201);
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

        $testOtp = $this->shouldUseTestOtp($user->phone);
        if (!$testOtp && !config('services.infinito_sms.enabled')) {
            return response()->json(['message' => 'OTP SMS provider is not configured'], 503);
        }

        $otp = $this->createOrReuseOtp($user);

        if ($testOtp) {
            return response()->json(['message' => 'Test OTP generated', 'otp' => $otp]);
        }

        $this->scheduleOtpSms($user);

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
            'subscription' => $this->subscriptionDetails($user),
            'plans' => $this->availablePlans(),
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
            'phone' => ['sometimes', 'required', 'string', 'regex:/^\+?[0-9\s().-]{7,20}$/'],
            'state' => ['sometimes', 'required', 'string'],
            'district' => ['sometimes', 'required', 'string'],
            'mandal' => ['sometimes', 'required', 'string'],
            'pincode' => ['sometimes', 'required', 'string'],
            'address' => ['sometimes', 'required', 'string'],
            'designation' => ['sometimes', 'required', 'array', 'size:1'],
            'designation.*' => ['required', 'string', Rule::in($this->designations())],
            'designation_other' => [Rule::requiredIf(fn (): bool => in_array('Others', (array) $request->input('designation'), true)), 'nullable', 'string', 'max:255'],
            'nationality' => ['sometimes', 'required', 'string', 'max:100'],
            'crops_interested' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'crop_interests' => ['sometimes', 'nullable', $this->cropInterestsRule()],
        ]);

        $user = User::find($this->userId($request));
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        if (array_key_exists('phone', $data)) {
            $phone = $this->normalizePhone($data['phone']);
            if (User::where('phone', $phone)->where('id', '!=', $user->id)->exists()) {
                return response()->json(['message' => 'Phone number already registered'], 409);
            }
            $data['phone'] = $phone;
        }

        $updates = [];
        foreach (['phone', 'state', 'district', 'mandal', 'pincode', 'address'] as $field) {
            if (array_key_exists($field, $data)) $updates[$field] = trim($data[$field]);
        }
        if (array_key_exists('firstName', $data)) $updates['first_name'] = trim($data['firstName']);
        if (array_key_exists('lastName', $data)) $updates['last_name'] = trim($data['lastName']);
        if (array_key_exists('email', $data)) $updates['email'] = $data['email'] === null ? null : trim($data['email']);
        if (array_key_exists('designation', $data)) {
            $updates['designation'] = $data['designation'];
            $updates['designation_other'] = in_array('Others', $data['designation'], true)
                ? trim($data['designation_other'])
                : null;
        }
        if (array_key_exists('designation_other', $data)) $updates['designation_other'] = trim($data['designation_other']);
        if (array_key_exists('nationality', $data)) $updates['nationality'] = trim($data['nationality']);
        if (array_key_exists('crops_interested', $data) || array_key_exists('crop_interests', $data)) {
            $updates['crop_interests'] = $this->normalizeCropInterests($data['crops_interested'] ?? $data['crop_interests'] ?? null);
        }

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

        $testOtp = $this->shouldUseTestOtp($user->phone);
        if (!$testOtp && !config('services.infinito_sms.enabled')) {
            return response()->json(['message' => 'OTP SMS provider is not configured'], 503);
        }

        $otp = $this->createOrReuseOtp($user);

        if ($testOtp) {
            return response()->json(['message' => 'Test OTP generated', 'otp' => $otp]);
        }

        $this->scheduleOtpSms($user);

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

    private function designations(): array
    {
        return ['Farmer', 'Student', 'Scientist', 'Teacher', 'Govt. Employee', 'Pvt. Employee', 'Company Owner', 'Others'];
    }

    private function normalizeCropInterests(array|string|null $cropInterests): array
    {
        if (is_string($cropInterests)) {
            $cropInterests = preg_split('/[,\r\n]+/', $cropInterests) ?: [];
        }

        return array_values(array_filter(array_map(static fn ($crop): string => trim((string) $crop), $cropInterests ?? []), static fn (string $crop): bool => $crop !== ''));
    }

    private function cropInterestsRule(): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value)) return;
            if (!is_array($value) || collect($value)->contains(static fn ($crop): bool => !is_string($crop))) {
                $fail('The '.$attribute.' field must be text or an array of text values.');
            }
        };
    }

    private function subscriptionDetails(User $user): array
    {
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->latest('end_date')
            ->first();

        return [
            'active' => $subscription !== null,
            'is_subscribed' => $subscription !== null,
            'plan_id' => $subscription
                ? (string) (config('services.razorpay.plans.'.$subscription->plan_type.'.years') ?? $subscription->plan_type)
                : null,
            'expiry_date' => $subscription?->end_date,
            'days_remaining' => $subscription
                ? (int) now()->startOfDay()->diffInDays($subscription->end_date->copy()->startOfDay())
                : null,
            'show_expiry_notification' => $subscription !== null
                && now()->startOfDay()->diffInDays($subscription->end_date->copy()->startOfDay()) <= 60,
        ];
    }

    private function availablePlans(): array
    {
        return app(MagazinePlanCatalog::class)->publicPlans();
    }

    private function shouldUseTestOtp(string $phone): bool
    {
        $testMode = (bool) config('services.otp.test_mode', false)
            || (bool) config('services.infinito_sms.test_mode', false);
        $testPhone = config('services.otp.test_phone');
        $configuredPhones = config('services.infinito_sms.test_phones', []);

        if (!$testMode) return false;
        if ($testPhone !== null && trim((string) $testPhone) !== '') {
            return $this->normalizePhone((string) $testPhone) === $this->normalizePhone($phone);
        }
        if (!$configuredPhones) return true;

        return in_array($this->normalizePhone($phone), array_map([$this, 'normalizePhone'], $configuredPhones), true);
    }

    private function createOrReuseOtp(User $user): string
    {
        $otp = $user->otp_code && $user->otp_expires_at?->isFuture()
            ? (string) $user->otp_code
            : (string) random_int(100000, 999999);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes((int) config('services.otp.expiry_minutes', 5)),
        ]);

        return $otp;
    }

    private function scheduleOtpSms(User $user): void
    {
        $cacheKey = 'otp-sms-cooldown:'.hash('sha256', $user->phone);
        if (!Cache::add($cacheKey, true, now()->addSeconds(30))) {
            return;
        }

        $userId = (int) $user->getKey();
        defer(function () use ($userId): void {
            $user = User::find($userId);
            if (!$user || !$user->otp_code || !$user->otp_expires_at?->isFuture()) {
                return;
            }

            try {
                app(InfinitoSmsService::class)->sendOtp($user->phone, (string) $user->otp_code);
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }

    private function userId(Request $request): int { return (int) $request->attributes->get('jwt_claims')['id']; }
    private function publicUser(User $user, bool $includeCropInterests = false): array {
        $data = [
            'id' => (int) $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'address' => $user->address,
            'designation' => $user->designation,
            'designation_other' => $user->designation_other,
            'nationality' => $user->nationality,
            'email' => $user->email,
            'state' => $user->state,
            'district' => $user->district,
            'mandal' => $user->mandal,
            'pincode' => $user->pincode,
            'role' => $user->role,
        ];

        if ($includeCropInterests) {
            $data['crop_interests'] = $user->crop_interests ?? [];
            $data['crops_interested'] = implode(', ', $user->crop_interests ?? []);
        }
        return $data;
    }
    private function base64UrlEncode(string $value): string { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }
}
