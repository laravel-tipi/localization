<?php

declare(strict_types=1);

use Tipi\Localization\Models\Locale;
use Tipi\Localization\Models\LocaleContext;
use Tipi\Localization\Policies\LocaleContextPolicy;
use Tipi\Localization\Policies\LocalePolicy;

return [
    /*
    |--------------------------------------------------------------------------
    | Locales Driver
    |--------------------------------------------------------------------------
    |
    | Where the global locale registry is read from: "config" or "database".
    |
    | "config" reads the locales below; every configured locale is active.
    | "database" reads the locales table and supports managing locales at
    | runtime through the package actions.
    |
    */
    'locales_driver' => 'config',

    /*
    |--------------------------------------------------------------------------
    | Configured Locales ("config" driver only)
    |--------------------------------------------------------------------------
    |
    | "code" uses the package's locale-code format: "en", "en-US", "en-001",
    | "zh-Hans" or "zh-Hans-CN" (at most 12 characters). "native_abbreviation"
    | (max 3 characters) and "country_code" (two uppercase letters) are
    | optional. "text_direction" is "ltr" (default) or "rtl".
    |
    | The array order is the display order.
    |
    */
    'locales' => [
        [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'native_abbreviation' => 'EN',
            'country_code' => 'GB',
            'text_direction' => 'ltr',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Default Locale ("config" driver only)
    |--------------------------------------------------------------------------
    |
    | Must be one of the configured locale codes. With the "database" driver
    | the default is the row whose is_default column is true.
    |
    */
    'default_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Locale Contexts
    |--------------------------------------------------------------------------
    |
    | A context is the subset of active locales available to one interface
    | of the application (the public site, an admin panel, ...), with its
    | own default and its own remembered preference.
    |
    | "contexts_driver" is "config" or "database" and is independent from
    | "locales_driver".
    |
    | With the "config" driver, "contexts" below is the complete list.
    |
    | With the "database" driver, "contexts" declares the contexts your code
    | depends on. They always exist and cannot be deleted; until they are
    | edited they use the definition below. Their stored definition (and any
    | additional context) lives in the locale_contexts tables.
    |
    | "locales" is "*" (every active locale) or a list of codes. A list must
    | contain the global default locale. "default" is optional; when omitted
    | the context follows the global default locale.
    |
    | "default_context" names the context used by localized routes, console
    | commands and queued jobs. It always exists ("*" unless defined below).
    |
    */
    'contexts_driver' => 'config',

    'default_context' => 'web',

    'contexts' => [
        'web' => [
            'locales' => '*',
        ],

        // 'admin' => [
        //     'locales' => ['en', 'ka'],
        //     'default' => 'en',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localized Routing
    |--------------------------------------------------------------------------
    |
    | "hide_default_locale": when true, the default context's default locale
    | is served without a prefix (/wines, /ka/wines) and GET requests to the
    | prefixed form (/en/wines) are permanently redirected.
    |
    | "negotiate_root_locale": when true, a request to the "root_route" without
    | a locale prefix is redirected to the visitor's remembered or browser
    | locale. Only that route is negotiated.
    |
    */
    'hide_default_locale' => true,

    'negotiate_root_locale' => false,

    'root_route' => 'home',

    /*
    |--------------------------------------------------------------------------
    | Locale Switch Route
    |--------------------------------------------------------------------------
    |
    | GET /{uri}?redirect=/path&context=name records an explicit locale choice
    | and redirects to a same-site path. Used by Localization::switchUrl()
    | and Localization::switcher(). The "web" middleware group is required
    | for the preference cookie to be written.
    |
    */
    'switch_route' => [
        'enabled' => true,
        'uri' => 'locale/{locale}',
        'name' => 'localization.switch',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Remembered Preference
    |--------------------------------------------------------------------------
    |
    | Explicit choices are stored in one cookie per context, named
    | "{cookie}_{context}" (for example "locale_web"). Visiting a localized
    | URL never changes it. Lifetime is in minutes.
    |
    */
    'preference' => [
        'cookie' => 'locale',
        'minutes' => 60 * 24 * 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Models and Policies
    |--------------------------------------------------------------------------
    |
    | Custom models must extend the package models. Set a custom table or
    | connection on the model itself. The default policies deny everything
    | unless the "manage-localization" gate ability is granted.
    |
    */
    'models' => [
        'locale' => Locale::class,
        'context' => LocaleContext::class,
    ],

    'policies' => [
        'locale' => LocalePolicy::class,
        'context' => LocaleContextPolicy::class,
    ],
];
