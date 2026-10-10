<?php

declare(strict_types=1);

use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Enums\LocaleDriver;
use Tipi\Localization\Exceptions\DefaultLocaleNotConfiguredException;
use Tipi\Localization\Exceptions\InvalidLocaleConfigurationException;
use Tipi\Localization\Exceptions\LocalesNotDefinedException;
use Tipi\Localization\Policies\LocalePolicy;
use Tipi\Localization\Repositories\ConfigLocaleRepository;
use Tipi\Support\Enums\TextDirection;

it('returns configured locales', function () {
    $config = new LocalizationConfig(
        localesDriver: LocaleDriver::Config,
        locales: [
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
        ],
        defaultLocale: 'ka',
        hideDefaultLocale: true,
        negotiateRootLocale: false,
        negotiatedRootRouteName: 'home',
        localeCookie: 'locale',
        localeCookieMinutes: 525600,
        localeSession: 'locale',
        localePolicy: LocalePolicy::class,
        localeModel: Locale::class,
        localesTable: 'locales',
    );

    $repository = new ConfigLocaleRepository($config);

    $locales = $repository->all();

    expect($locales)
        ->toHaveCount(2)
        ->toHaveKeys(['en', 'ka']);

    expect($locales->get('en'))
        ->code->toBe('en')
        ->name->toBe('English')
        ->nativeName->toBe('English')
        ->countryCode->toBe('GB')
        ->textDirection->toBe(TextDirection::Ltr)
        ->isActive()->toBeTrue()
        ->isDefault()->toBeFalse();

    expect($locales->get('ka'))
        ->code->toBe('ka')
        ->isDefault()->toBeTrue();
});

it('uses default locale values for optional configuration', function () {
    $config = new LocalizationConfig(
        localesDriver: LocaleDriver::Config,
        locales: [
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
            ],
        ],
        defaultLocale: 'en',
        hideDefaultLocale: true,
        negotiateRootLocale: false,
        negotiatedRootRouteName: 'home',
        localeCookie: 'locale',
        localeCookieMinutes: 525600,
        localeSession: 'locale',
        localePolicy: LocalePolicy::class,
        localeModel: Locale::class,
        localesTable: 'locales',
    );

    $locale = (new ConfigLocaleRepository($config))
        ->all()
        ->get('en');

    expect($locale)
        ->countryCode->toBeNull()
        ->textDirection->toBe(TextDirection::Ltr)
        ->isActive()->toBeTrue()
        ->isDefault()->toBeTrue();
});

it('throws when no locales are configured', function () {
    $config = new LocalizationConfig(
        localesDriver: LocaleDriver::Config,
        locales: [],
        defaultLocale: 'en',
        hideDefaultLocale: true,
        negotiateRootLocale: false,
        negotiatedRootRouteName: 'home',
        localeCookie: 'locale',
        localeCookieMinutes: 525600,
        localeSession: 'locale',
        localePolicy: LocalePolicy::class,
        localeModel: Locale::class,
        localesTable: 'locales',
    );

    (new ConfigLocaleRepository($config))->all();
})->throws(LocalesNotDefinedException::class);

it('throws when no default locale is configured', function () {
    $config = new LocalizationConfig(
        localesDriver: LocaleDriver::Config,
        locales: [
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
            ],
        ],
        defaultLocale: null,
        hideDefaultLocale: true,
        negotiateRootLocale: false,
        negotiatedRootRouteName: 'home',
        localeCookie: 'locale',
        localeCookieMinutes: 525600,
        localeSession: 'locale',
        localePolicy: LocalePolicy::class,
        localeModel: Locale::class,
        localesTable: 'locales',
    );

    (new ConfigLocaleRepository($config))->all();
})->throws(DefaultLocaleNotConfiguredException::class);

it('rejects invalid locale configuration', function (array $locale) {
    $config = new LocalizationConfig(
        localesDriver: LocaleDriver::Config,
        locales: [$locale],
        defaultLocale: 'en',
        hideDefaultLocale: true,
        negotiateRootLocale: false,
        negotiatedRootRouteName: 'home',
        localeCookie: 'locale',
        localeCookieMinutes: 525600,
        localeSession: 'locale',
        localePolicy: LocalePolicy::class,
        localeModel: Locale::class,
        localesTable: 'locales',
    );

    (new ConfigLocaleRepository($config))->all();
})->with([
    'missing code' => [[
        'name' => 'English',
        'native_name' => 'English',
    ]],

    'empty code' => [[
        'code' => '',
        'name' => 'English',
        'native_name' => 'English',
    ]],

    'invalid code' => [[
        'code' => 'EN',
        'name' => 'English',
        'native_name' => 'English',
    ]],

    'missing name' => [[
        'code' => 'en',
        'native_name' => 'English',
    ]],

    'missing native name' => [[
        'code' => 'en',
        'name' => 'English',
    ]],

    'invalid country code' => [[
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'gb',
    ]],

    'invalid text direction' => [[
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'text_direction' => 'invalid',
    ]],
])->throws(InvalidLocaleConfigurationException::class);
it('rejects duplicate locale codes', function () {
    $locale = [
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
    ];

    $config = new LocalizationConfig(
        localesDriver: LocaleDriver::Config,
        locales: [$locale, $locale],
        defaultLocale: 'en',
        hideDefaultLocale: true,
        negotiateRootLocale: false,
        negotiatedRootRouteName: 'home',
        localeCookie: 'locale',
        localeCookieMinutes: 525600,
        localeSession: 'locale',
        localePolicy: LocalePolicy::class,
        localeModel: Locale::class,
        localesTable: 'locales',
    );

    (new ConfigLocaleRepository($config))->all();
})->throws(InvalidLocaleConfigurationException::class);

it('rejects a default locale that is not defined', function () {
    $config = new LocalizationConfig(
        localesDriver: LocaleDriver::Config,
        locales: [
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
            ],
        ],
        defaultLocale: 'ka',
        hideDefaultLocale: true,
        negotiateRootLocale: false,
        negotiatedRootRouteName: 'home',
        localeCookie: 'locale',
        localeCookieMinutes: 525600,
        localeSession: 'locale',
        localePolicy: LocalePolicy::class,
        localeModel: Locale::class,
        localesTable: 'locales',
    );

    (new ConfigLocaleRepository($config))->all();
})->throws(InvalidLocaleConfigurationException::class);
