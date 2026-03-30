<?php

declare(strict_types=1);

namespace OpenRic\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Authentication middleware.
 * Validates API key from Authorization header.
 */
class ApiAuthenticate
{
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader) {
            return response()->json([
                'success' => false,
                'error' => 'UNAUTHORIZED',
                'message' => 'Authorization header required',
            ], 401);
        }

        // Extract API key from "Bearer <key>" format
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return response()->json([
                'success' => false,
                'error' => 'INVALID_TOKEN',
                'message' => 'Invalid authorization format. Use: Bearer <api_key>',
            ], 401);
        }

        $apiKey = $matches[1];
        
        // Validate API key (lookup in triplestore)
        $keyData = $this->validateApiKey($apiKey);
        
        if (!$keyData) {
            return response()->json([
                'success' => false,
                'error' => 'INVALID_KEY',
                'message' => 'Invalid or expired API key',
            ], 401);
        }

        // Check scope if required
        if ($scope && !$this->hasScope($keyData, $scope)) {
            return response()->json([
                'success' => false,
                'error' => 'FORBIDDEN',
                'message' => "Missing required scope: {$scope}",
            ], 403);
        }

        // Add API user data to request
        $request->attributes->set('api_key_id', $keyData['id'] ?? null);
        $request->attributes->set('api_user_id', $keyData['user_id'] ?? null);
        $request->attributes->set('api_scopes', $keyData['scopes'] ?? []);

        return $next($request);
    }

    private function validateApiKey(string $key): ?array
    {
        // In production, this would query the triplestore
        // For now, return null to indicate validation failure
        // The actual implementation would be:
        // $sparql = "SELECT ?id ?userId ?scopes WHERE { ?key rico:hasValue '{$key}' ... }";
        
        // Placeholder - implement with triplestore lookup
        return null;
    }

    private function hasScope(array $keyData, string $requiredScope): bool
    {
        $scopes = $keyData['scopes'] ?? [];
        return in_array($requiredScope, $scopes) || in_array('*', $scopes);
    }
}
