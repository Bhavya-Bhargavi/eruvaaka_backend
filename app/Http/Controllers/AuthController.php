<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\PasswordResetToken;
use App\Models\RevokedToken;
use App\Models\User;
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
            'crop_interests' => ['required', 'array'],
            'crop_interests.*' => ['string'],
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
            'crop_interests' => array_values(array_map('trim', $data['crop_interests'])),
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

        return response()->json([
            'message' => 'OTP sent successfully',
            'otp' => $otp,
        ]);
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
        return $user ? response()->json(['user' => $user]) : response()->json(['message' => 'User not found'], 404);
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

        return response()->json([
            'message' => 'OTP resent successfully',
            'otp' => $otp,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $claims = $request->attributes->get('jwt_claims');
        if (!empty($claims['jti']) && !empty($claims['exp'])) RevokedToken::firstOrCreate(['token_id' => hash('sha256', $claims['jti'])], ['expires_at' => date('Y-m-d H:i:s', $claims['exp'])]);
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function listBookmarks(Request $request): JsonResponse
    {
        $bookmarks = Bookmark::where('user_id', $this->userId($request))->orderByDesc('created_at')->get(['id', 'article_slug', 'title', 'url', 'created_at']);
        return response()->json(['bookmarks' => $bookmarks]);
    }

    public function addBookmark(Request $request): JsonResponse
    {
        $data = $request->validate(['article_slug' => ['required', 'string'], 'title' => ['nullable', 'string'], 'url' => ['nullable', 'url']]);
        $bookmark = Bookmark::updateOrCreate(['user_id' => $this->userId($request), 'article_slug' => trim($data['article_slug'])], ['title' => $data['title'] ?? null, 'url' => $data['url'] ?? null]);
        return response()->json(['message' => 'Bookmark added', 'bookmark' => $bookmark], 201);
    }

    public function removeBookmark(Request $request, string $article_slug): JsonResponse
    {
        $deleted = Bookmark::where('user_id', $this->userId($request))->where('article_slug', $article_slug)->delete();
        return $deleted ? response()->json(['message' => 'Bookmark removed']) : response()->json(['message' => 'Bookmark not found'], 404);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', trim($phone));
    }

    private function userId(Request $request): int { return (int) $request->attributes->get('jwt_claims')['id']; }
    private function publicUser(User $user): array {
        return [
            'id' => (int) $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'state' => $user->state,
            'district' => $user->district,
            'mandal' => $user->mandal,
            'pincode' => $user->pincode,
            'crop_interests' => $user->crop_interests ?? [],
            'role' => $user->role,
        ];
    }
    private function base64UrlEncode(string $value): string { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }
}
