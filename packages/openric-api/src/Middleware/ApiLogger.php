<?php

declare(strict_types=1);

namespace OpenRic\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Request/Response Logger middleware.
 * Logs API calls for audit and analytics.
 */
class ApiLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log incoming request
        Log::channel('api')->info('API Request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'api_key_id' => $request->attributes->get('api_key_id'),
            'user_id' => $request->attributes->get('api_user_id'),
        ]);

        $response = $next($request);

        $duration = (microtime(true) - $startTime) * 1000;

        // Log response
        Log::channel('api')->info('API Response', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'api_key_id' => $request->attributes->get('api_key_id'),
        ]);

        return $response;
    }
}
