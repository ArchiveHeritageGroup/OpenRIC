<?php

declare(strict_types=1);

namespace OpenRic\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Rate Limiting middleware.
 * Limits requests per API key based on configuration.
 */
class ApiRateLimit
{
    public function handle(Request $request, Closure $next, int $maxRequests = 100, int $windowSeconds = 60): Response
    {
        $key = $this->getRateLimitKey($request);
        $current = (int) Cache::get($key, 0);

        if ($current >= $maxRequests) {
            $retryAfter = Cache::ttl($key);
            
            return response()->json([
                'success' => false,
                'error' => 'RATE_LIMIT_EXCEEDED',
                'message' => "Rate limit exceeded. Maximum {$maxRequests} requests per {$windowSeconds} seconds.",
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $maxRequests,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $retryAfter,
            ]);
        }

        Cache::put($key, $current + 1, $windowSeconds);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxRequests,
            'X-RateLimit-Remaining' => max(0, $maxRequests - $current - 1),
        ]);
    }

    private function getRateLimitKey(Request $request): string
    {
        $keyId = $request->attributes->get('api_key_id', $request->ip());
        return "api_rate_limit:{$keyId}";
    }
}
