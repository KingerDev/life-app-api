<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ClerkAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'No token provided',
            ], 401);
        }

        try {
            \Log::info('ClerkAuth: Verifying token', ['token_length' => strlen($token)]);
            $payload = $this->verifyToken($token);
            \Log::info('ClerkAuth: Token verified', ['sub' => $payload->sub ?? 'N/A']);

            $clerkId = $payload->sub;
            $email = $payload->email ?? null;
            $name = $payload->name ?? $payload->first_name ?? null;

            $user = User::firstOrCreate(
                ['clerk_id' => $clerkId],
                [
                    'email' => $email,
                    'name' => $name,
                ]
            );

            if ($user->wasRecentlyCreated === false) {
                $user->update(array_filter([
                    'email' => $email,
                    'name' => $name,
                ]));
            }

            $request->setUserResolver(fn () => $user);

        } catch (\Exception $e) {
            \Log::error('ClerkAuth: Token verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid token: ' . $e->getMessage(),
            ], 401);
        }

        return $next($request);
    }

    private function verifyToken(string $token): object
    {
        $jwks = $this->getJwks();

        return JWT::decode($token, JWK::parseKeySet($jwks));
    }

    private function getJwks(): array
    {
        return Cache::remember('clerk_jwks', 3600, function () {
            $clerkSecretKey = config('services.clerk.secret_key');
            $clerkDomain = $this->extractDomain($clerkSecretKey);

            \Log::info('ClerkAuth: Fetching JWKS', ['domain' => $clerkDomain]);
            $response = Http::get("https://{$clerkDomain}/.well-known/jwks.json");

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch JWKS');
            }

            return $response->json();
        });
    }

    private function extractDomain(string $secretKey): string
    {
        $frontendApi = config('services.clerk.frontend_api');
        if ($frontendApi) {
            // Remove https:// prefix if present
            return str_replace(['https://', 'http://'], '', $frontendApi);
        }

        // Try to decode from publishable key
        // pk_test_BASE64_ENCODED_DOMAIN$
        if (preg_match('/^pk_(test|live)_(.+)\$$/', $secretKey, $matches)) {
            $decoded = base64_decode($matches[2]);
            if ($decoded) {
                return $decoded;
            }
        }

        if (str_starts_with($secretKey, 'sk_live_')) {
            return 'clerk.prod.lclclerk.com';
        }

        return 'clerk.dev.lclclerk.com';
    }
}
