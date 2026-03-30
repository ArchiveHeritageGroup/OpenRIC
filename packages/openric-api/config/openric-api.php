<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the OpenRiC API package.
    | This package provides REST API v1/v2 using the RiC-O data model.
    |
    */

    // API Version
    'version' => env('API_VERSION', 'v2'),
    'default_version' => env('API_DEFAULT_VERSION', 'v2'),

    // Authentication
    'auth_type' => env('API_AUTH_TYPE', 'key'), // 'key' or 'oauth'
    'require_auth' => env('API_REQUIRE_AUTH', true),

    // Rate limiting
    'rate_limit_enabled' => env('API_RATE_LIMIT_ENABLED', true),
    'rate_limit_requests' => env('API_RATE_LIMIT_REQUESTS', 100),
    'rate_limit_window' => env('API_RATE_LIMIT_WINDOW', 60), // seconds

    // API Keys
    'key_hash_algorithm' => 'sha256',
    'key_length' => 64,

    // Available scopes
    'scopes' => [
        'read' => 'Read access to public resources',
        'write' => 'Write access to resources',
        'admin' => 'Administrative access',
        'search' => 'Search functionality',
        'export' => 'Export capabilities',
    ],

    // Pagination defaults
    'pagination' => [
        'default_limit' => env('API_PAGINATION_LIMIT', 10),
        'max_limit' => env('API_PAGINATION_MAX_LIMIT', 100),
    ],

    // CORS settings
    'cors' => [
        'allowed_origins' => explode(',', env('API_CORS_ORIGINS', '*')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Key', 'Accept'],
        'max_age' => 86400,
    ],

    // Logging
    'log_requests' => env('API_LOG_REQUESTS', true),
    'log_channel' => 'api',

    // Webhooks
    'webhooks' => [
        'timeout' => env('API_WEBHOOK_TIMEOUT', 10),
        'retry_count' => env('API_WEBHOOK_RETRY_COUNT', 3),
    ],

    // Response format
    'response_format' => env('API_RESPONSE_FORMAT', 'json'),
];
