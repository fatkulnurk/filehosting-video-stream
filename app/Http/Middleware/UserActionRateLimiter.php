<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class UserActionRateLimiter
{
    protected array $limits = [
        'download' => ['max' => 50, 'minutes' => 60],
        'view' => ['max' => 200, 'minutes' => 60],
        'upload' => ['max' => 20, 'minutes' => 60],
        'share' => ['max' => 30, 'minutes' => 60],
    ];

    public function handle(Request $request, Closure $next, string $action = 'default'): Response
    {
        $userId = auth()->id();

        if (! $userId) {
            return $next($request);
        }

        $limit = $this->limits[$action] ?? ['max' => 100, 'minutes' => 60];
        $rateLimitKey = "user_action:{$userId}:{$action}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, $limit['max'])) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Rate limit exceeded',
                    'action' => $action,
                    'retry_after' => $retryAfter,
                ], 429)->withHeaders([
                    'Retry-After' => $retryAfter,
                    'X-RateLimit-Limit' => $limit['max'],
                    'X-RateLimit-Remaining' => 0,
                    'X-RateLimit-Action' => $action,
                ]);
            }

            return redirect()->back()->withErrors([
                'rate_limit' => "Too many {$action} requests. Try again in {$retryAfter} seconds.",
            ]);
        }

        RateLimiter::hit($rateLimitKey, $limit['minutes'] * 60);

        return $next($request);
    }
}
