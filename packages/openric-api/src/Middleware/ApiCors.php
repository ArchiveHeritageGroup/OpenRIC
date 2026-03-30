<?php

declare(strict_types=1);

namespace OpenRic\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API CORS middleware.
 * Handles Cross-Origin Resource Sharing for API endpoints.
 */
class ApiCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $this->getAllowedOrigin($request))
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-API-Key')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response->headers->set('Access-Control-Allow-Origin', $this->getAllowedOrigin($request));
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-API-Key');
        $response->headers->set('Access-Control-Expose-Headers', 'X-Total-Count, X-Page-Count');

        return $response;
    }

    private function getAllowedOrigin(Request $request): string
    {
        $configOrigin = config('api.cors_origin', '*');
        
        if ($configOrigin === '*') {
            return $request->header('Origin', '*');
        }

        $allowedOrigins = is_array($configOrigin) ? $configOrigin : explode(',', $configOrigin);
        $requestOrigin = $request->header('Origin');

        if (in_array($requestOrigin, $allowedOrigins)) {
            return $requestOrigin;
        }

        return $allowedOrigins[0] ?? '*';
    }
}
