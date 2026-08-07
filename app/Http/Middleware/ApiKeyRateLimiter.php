<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyRateLimiter
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKeyValue = $request->header('X-API-Key');

        if (! $apiKeyValue) {
            return response()->json(['error' => 'Missing API key'], 401);
        }

        $apiKey = ApiKey::where('key', $apiKeyValue)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $apiKey) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        $rateLimitKey = 'api_key:'.$apiKey->id;
        $maxAttempts = $apiKey->rate_limit ?: 60;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        RateLimiter::hit($rateLimitKey, 60);

        $apiKey->update(['last_used_at' => now()]);

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_user', $apiKey->user);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($rateLimitKey, $maxAttempts),
        ]);
    }
}
