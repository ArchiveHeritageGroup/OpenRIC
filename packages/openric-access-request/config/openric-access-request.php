<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Access Request Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the OpenRiC Access Request package.
    | This package manages access requests using the RiC-O data model.
    |
    */

    // Request types available
    'request_types' => [
        'access' => 'General Access Request',
        'research' => 'Research Access',
        'digital' => 'Digital Reproduction',
        'physical' => 'Physical Access',
        'consultation' => 'On-site Consultation',
    ],

    // Request status values
    'statuses' => [
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'denied' => 'Denied',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
    ],

    // Default pagination
    'per_page' => env('ACCESS_REQUEST_PER_PAGE', 25),

    // Request expiry (in days)
    'expiry_days' => env('ACCESS_REQUEST_EXPIRY_DAYS', 90),

    // Email notifications
    'notify_approvers' => env('ACCESS_REQUEST_NOTIFY_APPROVERS', true),
    'notify_requester' => env('ACCESS_REQUEST_NOTIFY_REQUESTER', true),

    // Require justification for requests
    'require_justification' => env('ACCESS_REQUEST_REQUIRE_JUSTIFICATION', true),
];
