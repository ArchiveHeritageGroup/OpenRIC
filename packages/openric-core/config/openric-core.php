<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Settings-to-Config Mapping
    |--------------------------------------------------------------------------
    |
    | Each entry maps a row in the 'settings' table (identified by group + key)
    | to a Laravel config key.  On boot the service provider reads these values
    | from PostgreSQL and pushes them into config() so the rest of the
    | application can access them via the standard config helper.
    |
    | Example:
    |   ['group' => 'ui', 'key' => 'label_repository', 'config_key' => 'app.ui_label_repository'],
    |
    */

    'settings_config_map' => [
        // Add mappings here as the application grows, e.g.:
        // ['group' => 'ui', 'key' => 'hits_per_page', 'config_key' => 'app.hits_per_page'],
    ],

];
