<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleAdminActions
{
    /**
     * Rate limit configuration for admin actions
     * Default: 60 requests per minute per admin user
     */
    protected int $maxAttempts = 60;

    protected int $decayMinutes = 1;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->hasRole('admin', 'staff')) {
            return $next($request);
        }

        $key = $this->resolveRateLimitKey($request);

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            return response()->json(
                [
                    'message' => 'Too many admin requests. Please try again later.',
                    'retry_after' => RateLimiter::availableIn($key),
                ],
                429
            );
        }

        RateLimiter::hit($key, $this->decayMinutes * 60);

        return $next($request);
    }

    protected function resolveRateLimitKey(Request $request): string
    {
        return sprintf(
            'admin_action:%s:%s',
            $request->user()->id,
            $request->method()
        );
    }
}
