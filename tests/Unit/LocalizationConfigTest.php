<?php

declare(strict_types=1);

use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Enums\StorageDriver;
use Tipi\Localization\Exceptions\InvalidLocaleConfigurationException;
use Tipi\Support\Enums\TextDirection;

function validConfig(array $overrides = []): array
{
    return array_replace([
        'locales_driver' => 'config',
        'locales' => [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English'],
            ['code' => 'ka', 'name' => 'Georgian', 'native_name' => 'ქართული', 'text_direction' => 'ltr'],
        ],
        'default_locale' => 'en',
    ], $overrides);
}

it('applies defaults for omitted options', function () {
    $config = LocalizationConfig::fromArray(validConfig());

    expect($config)
        ->localesDriver->toBe(StorageDriver::Config)
        ->contextsDriver->toBe(StorageDriver::Config)
        ->defaultContext->toBe('web')
        ->hideDefaultLocale->toBeTrue()
        ->negotiateRootLocale->toBeFalse()
        ->rootRoute->toBe('home')
        ->switchRouteEnabled->toBeTrue()
        ->preferenceCookie->toBe('locale')
        ->and($config->contexts)->toBe(['web' => ['locales' => null, 'default' => null]])
        ->and($config->locales[0])->toMatchArray([
            'code' => 'en',
            'native_abbreviation' => null,
            'country_code' => null,
            'text_direction' => TextDirection::Ltr,
        ]);
});

it('always defines the default context', function () {
    $config = LocalizationConfig::fromArray(validConfig([
        'default_context' => 'site',
        'contexts' => ['admin' => ['locales' => ['en'], 'default' => 'en']],
    ]));

    expect($config->contexts)->toBe([
        'admin' => ['locales' => ['en'], 'default' => 'en'],
        'site' => ['locales' => null, 'default' => null],
    ]);
});

it('does not validate configured locales when the database driver is used', function () {
    $config = LocalizationConfig::fromArray(['locales_driver' => 'database', 'locales' => 'leftover']);

    expect($config->locales)->toBe([])->and($config->defaultLocale)->toBeNull();
});

it('rejects invalid configuration with a clear message', function (array $config, string $message) {
    expect(fn () => LocalizationConfig::fromArray(validConfig($config)))
        ->toThrow(InvalidLocaleConfigurationException::class, $message);
})->with([
    'typo in driver' => [['locales_driver' => 'databse'], '[locales_driver] must be "config" or "database"'],
    'null driver' => [['contexts_driver' => 123], '[contexts_driver] must be "config" or "database"'],
    'no locales' => [['locales' => []], 'must list at least one locale'],
    'missing name' => [['locales' => [['code' => 'en', 'native_name' => 'x']]], 'non-empty [name]'],
    'invalid code' => [['locales' => [['code' => 'en_US', 'name' => 'x', 'native_name' => 'x']]], 'Locale code [en_US] is invalid'],
    'duplicate' => [['locales' => [['code' => 'en', 'name' => 'x', 'native_name' => 'x'], ['code' => 'en', 'name' => 'x', 'native_name' => 'x']]], 'defined more than once'],
    'long abbreviation' => [['locales' => [['code' => 'en', 'name' => 'x', 'native_name' => 'x', 'native_abbreviation' => 'ENGL']]], 'at most 3 characters'],
    'country' => [['locales' => [['code' => 'en', 'name' => 'x', 'native_name' => 'x', 'country_code' => 'gb']]], 'two-letter uppercase'],
    'direction' => [['locales' => [['code' => 'en', 'name' => 'x', 'native_name' => 'x', 'text_direction' => 'up']]], '[ltr] or [rtl]'],
    'missing default' => [['default_locale' => null], '[default_locale] must be set'],
    'unknown default' => [['default_locale' => 'fr'], 'is not one of the configured locales'],
    'context name' => [['contexts' => ['Admin' => []]], 'Context names must be'],
    'context locales' => [['contexts' => ['admin' => ['locales' => []]]], '"*" or a non-empty list'],
    'context default type' => [['contexts' => ['admin' => ['default' => 1]]], 'must be a locale code or null'],
    'default context name' => [['default_context' => 'Web'], '[default_context]'],
    'switch uri' => [['switch_route' => ['uri' => 'lang']], 'must contain the {locale} parameter'],
    'boolean' => [['hide_default_locale' => 'yes'], '[hide_default_locale] must be a boolean'],
    'minutes' => [['preference' => ['minutes' => 0]], '[preference.minutes]'],
    'model' => [['models' => ['locale' => stdClass::class]], '[models.locale] must be a class extending'],
    'policy' => [['policies' => ['locale' => 'Missing\\Policy']], '[policies.locale] must be an existing class'],
]);
