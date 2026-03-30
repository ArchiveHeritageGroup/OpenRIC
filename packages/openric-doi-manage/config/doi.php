<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | DataCite API Settings
    |--------------------------------------------------------------------------
    |
    | These are fallback defaults. Runtime configuration is stored in the
    | 'settings' table (group = 'doi') and managed via the admin UI.
    |
    */

    'datacite_url'           => env('DATACITE_URL', 'https://api.test.datacite.org'),
    'datacite_repository_id' => env('DATACITE_REPOSITORY_ID', ''),
    'datacite_password'      => env('DATACITE_PASSWORD', ''),
    'datacite_prefix'        => env('DATACITE_PREFIX', ''),
    'datacite_environment'   => env('DATACITE_ENVIRONMENT', 'test'),

    /*
    |--------------------------------------------------------------------------
    | Minting Defaults
    |--------------------------------------------------------------------------
    */

    'auto_mint'              => false,
    'default_publisher'      => env('APP_NAME', 'OpenRiC'),
    'default_resource_type'  => 'Dataset',
    'suffix_pattern'         => '{year}/{entity_id}',

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    */

    'max_attempts'           => 3,
    'retry_delay_minutes'    => 15,
    'hits_per_page'          => 20,

];
