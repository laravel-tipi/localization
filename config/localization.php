<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Locales Driver
    |--------------------------------------------------------------------------
    |
    | 'database' locales are obtained from locales table.
    | 'config' locales are obtained from 'locales' array in this config file.
    |
    */
    'locales_driver' => 'config',

    'locales' => [
        ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'country_code' => 'GB', 'text_direction' => 'ltr'],
        ['code' => 'ka', 'name' => 'Georgian', 'native_name' => 'ქართული', 'country_code' => 'GE', 'text_direction' => 'ltr'],
        ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский', 'country_code' => 'RU', 'text_direction' => 'ltr'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | Set only when the 'locales_driver' is 'config'
    |
    */
    'default_locale' => 'ka',

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
