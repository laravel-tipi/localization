<?php

declare(strict_types=1);

use Tipi\Localization\Data\CreateLocaleContextData;
use Tipi\Localization\Data\CreateLocaleData;
use Tipi\Localization\Data\UpdateLocaleContextData;
use Tipi\Localization\Data\UpdateLocaleData;
use Tipi\Localization\Exceptions\InvalidLocaleDataException;
use Tipi\Support\Enums\TextDirection;

it('creates locale data with defaults', function () {
    $data = new CreateLocaleData(code: 'fr', name: 'French', nativeName: 'Français');

    expect($data)
        ->nativeAbbreviation->toBeNull()
        ->countryCode->toBeNull()
        ->textDirection->toBe(TextDirection::Ltr)
        ->active->toBeFalse();
});

it('round-trips locale data through arrays', function () {
    $data = CreateLocaleData::fromArray([
        'code' => 'ar',
        'name' => 'Arabic',
        'native_name' => 'العربية',
        'native_abbreviation' => 'ع',
        'country_code' => 'EG',
        'text_direction' => 'rtl',
        'active' => true,
    ]);

    expect($data->toArray())->toBe([
        'code' => 'ar',
        'name' => 'Arabic',
        'native_name' => 'العربية',
        'native_abbreviation' => 'ع',
        'country_code' => 'EG',
        'text_direction' => 'rtl',
        'active' => true,
    ]);

    expect(UpdateLocaleData::fromArray(['name' => 'A', 'native_name' => 'B', 'country_code' => ''])->toArray())
        ->toBe(['name' => 'A', 'native_name' => 'B', 'native_abbreviation' => null, 'country_code' => null, 'text_direction' => 'ltr']);
});

it('rejects invalid locale data before it reaches the database', function (Closure $make) {
    expect($make)->toThrow(InvalidLocaleDataException::class);
})->with([
    'routing-incompatible code' => fn () => new CreateLocaleData('en_US', 'x', 'x'),
    'uppercase code' => fn () => new CreateLocaleData('EN', 'x', 'x'),
    'empty name' => fn () => new CreateLocaleData('en', ' ', 'x'),
    'long abbreviation' => fn () => new UpdateLocaleData('x', 'x', nativeAbbreviation: 'ENGL'),
    'country' => fn () => new UpdateLocaleData('x', 'x', countryCode: 'gb'),
    'direction' => fn () => UpdateLocaleData::fromArray(['name' => 'x', 'native_name' => 'x', 'text_direction' => 'up']),
    'missing key' => fn () => CreateLocaleData::fromArray(['name' => 'x', 'native_name' => 'x']),
    'context name' => fn () => new CreateLocaleContextData('Admin'),
    'empty context locales' => fn () => new UpdateLocaleContextData(locales: []),
    'duplicate context locales' => fn () => new UpdateLocaleContextData(locales: ['en', 'en']),
    'invalid context locale' => fn () => new UpdateLocaleContextData(locales: ['en_US']),
    'invalid context default' => fn () => new UpdateLocaleContextData(default: 'EN'),
]);

it('reads context data from arrays, treating "*" as all locales', function () {
    expect(CreateLocaleContextData::fromArray(['name' => 'admin', 'locales' => '*']))
        ->locales->toBeNull()
        ->and(UpdateLocaleContextData::fromArray(['locales' => ['en', 'ka'], 'default' => 'ka'])->toArray())
        ->toBe(['locales' => ['en', 'ka'], 'default' => 'ka']);
});
