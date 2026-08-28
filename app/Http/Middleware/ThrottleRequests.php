<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequests
{
    /**
     * Handle an incoming request with rate limiting.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $key = 'api', int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $rateLimitKey = $this->resolveRequestSignature($request, $key);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => $this->messageFor($key),
                'retry_after' => RateLimiter::availableIn($rateLimitKey),
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, $decayMinutes * 60);

        $response = $next($request);

        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($rateLimitKey, $maxAttempts),
        ]);

        return $response;
    }

    protected function resolveRequestSignature(Request $request, string $key): string
    {
        $tenantKey = tenant()?->getTenantKey();

        if ($key === 'public-booking') {
            return 'public-booking:' . $tenantKey . ':' . $request->ip();
        }

        $user = $request->user();

        if ($user) {
            return sha1($key . '|' . $user->id);
        }

        return sha1($key . '|' . $request->ip());
    }

    protected function messageFor(string $key): string
    {
        return $key === 'public-booking'
            ? 'Too many booking attempts. Please try again later.'
            : 'Too many requests. Please try again later.';
    }
}
