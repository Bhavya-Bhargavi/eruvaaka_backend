<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LegacyJwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if ($token === null) {
            return response()->json(['message' => 'Access denied. Token missing.'], 401);
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return response()->json(['message' => 'Invalid or expired token.'], 403);
        }

        [$header, $encodedPayload, $signature] = $parts;
        $secret = (string) config('app.jwt_secret', env('JWT_SECRET', 'supersecretkey'));
        $expected = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$encodedPayload, $secret, true));
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        if (!hash_equals($expected, $signature) || !is_array($payload) || ($payload['exp'] ?? 0) < time()) {
            return response()->json(['message' => 'Invalid or expired token.'], 403);
        }

        if (!empty($payload['jti']) && DB::table('revoked_tokens')
            ->where('token_id', hash('sha256', $payload['jti']))
            ->where('expires_at', '>', now())
            ->exists()) {
            return response()->json(['message' => 'Invalid or expired token.'], 403);
        }

        $request->attributes->set('jwt_claims', $payload);
        return $next($request);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4));
    }
}
