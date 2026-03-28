<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Fuseki Endpoint
    |--------------------------------------------------------------------------
    |
    | The base URL for the Apache Jena Fuseki dataset. Query and update
    | endpoints are derived from this: {endpoint}/query, {endpoint}/update.
    |
    */
    'endpoint' => env('FUSEKI_ENDPOINT', 'http://localhost:3030/openric'),

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    'username' => env('FUSEKI_USERNAME', 'admin'),
    'password' => env('FUSEKI_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Timeouts
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('FUSEKI_TIMEOUT', 15),
    'connect_timeout' => (int) env('FUSEKI_CONNECT_TIMEOUT', 3),

];
