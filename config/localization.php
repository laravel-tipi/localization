<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Hide Default Locale
    |--------------------------------------------------------------------------
    |
    | When enabled, the default locale will not be included in localized URLs.
    |
    | Example with "en" as the default locale:
    |
    | true:
    |   /wines
    |   /ka/wines
    |
    | false:
    |   /en/wines
    |   /ka/wines
    |
    */

    'hide_default_locale' => true,

    'negotiate_root_locale' => false,

    'negotiated_root_route_name' => 'home',

    'cookie' => [
        'name' => 'locale',
        'minutes' => 60 * 24 * 365,
    ],
];
