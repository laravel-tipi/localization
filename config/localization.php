<?php

declare(strict_types=1);

use Tipi\Localization\Models\Locale;
use Tipi\Localization\Policies\LocalePolicy;

return [
    /*
    |--------------------------------------------------------------------------
    | Locales Driver
    |--------------------------------------------------------------------------
    |
    | Choose where the package reads its locales from.
    |
    | Supported drivers: "config", "database"
    |
    | The "config" driver reads the locales declared below. Every configured
    | locale is considered active. The "database" driver reads locales from
    | the package's locales table and supports activating and deactivating
    | locales at runtime.
    |
    */
    'locales_driver' => 'config',

    /*
    |--------------------------------------------------------------------------
    | Configured Locales
    |--------------------------------------------------------------------------
    |
    | These locales are used only by the "config" driver.
    |
    | "code" follows the package's supported locale-code format, such as
    | "en", "en-US", "zh-Hans", or "zh-Hans-CN".
    |
    | "country_code" is optional and, when present, must be a two-letter
    | uppercase country code. "text_direction" may be "ltr" or "rtl" and
    | defaults to "ltr" when omitted.
    |
    */
    'locales' => [
        [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'country_code' => 'GB',
            'text_direction' => 'ltr',
        ],
        [
            'code' => 'ka',
            'name' => 'Georgian',
            'native_name' => 'ქართული',
            'country_code' => 'GE',
            'text_direction' => 'ltr',
        ],
        [
            'code' => 'ru',
            'name' => 'Russian',
            'native_name' => 'Русский',
            'country_code' => 'RU',
            'text_direction' => 'ltr',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale for the "config" driver. It must match one of the
    | locale codes declared above. With the "database" driver, the default
    | locale is determined by the locales table instead.
    |
    */
    'default_locale' => 'ka',

    /*
    |--------------------------------------------------------------------------
    | Hide Default Locale
    |--------------------------------------------------------------------------
    |
    | When enabled, the default locale is omitted from generated URLs and a
    | prefixed default-locale URL is redirected to its canonical unprefixed
    | URL.
    |
    | Example with "en" as the default locale:
    |
    | true:  /wines,    /ka/wines
    | false: /en/wines, /ka/wines
    |
    */
    'hide_default_locale' => true,

    /*
    |--------------------------------------------------------------------------
    | Root Locale Negotiation
    |--------------------------------------------------------------------------
    |
    | When enabled, requests to the configured root route may be redirected
    | to a supported non-default locale. Locale negotiation prefers the
    | remembered locale cookie, then the browser's Accept-Language header,
    | and finally the default locale.
    |
    | Negotiation applies only to the named route configured below, not to
    | every unprefixed localized route.
    |
    */
    'negotiate_root_locale' => false,

    'negotiated_root_route_name' => 'home',

    /*
    |--------------------------------------------------------------------------
    | Locale Cookie
    |--------------------------------------------------------------------------
    |
    | Localized requests remember the resolved locale in this cookie. The
    | lifetime is expressed in minutes.
    |
    */
    'cookie' => [
        'name' => 'locale',
        'minutes' => 60 * 24 * 365,
    ],

    'session' => [
        'key' => 'locale',
    ],

    'locale_policy' => LocalePolicy::class,

    'locale_model' => Locale::class,

    'locales_table' => 'locales',

    /**
     * TODO: let use configure the name of the locales table
     */
];
