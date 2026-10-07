<?php

declare(strict_types=1);

use Tipi\Localization\Actions\CreateLocale;
use Tipi\Localization\Data\CreateLocaleData;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\Models\Locale;
use Tipi\Support\Enums\TextDirection;

beforeEach(function () {
    $this->artisan('migrate')->run();
});

function createLocaleData(
    string $code = 'en',
    string $name = 'English',
    string $nativeName = 'English',
    ?string $countryCode = 'GB',
    TextDirection $textDirection = TextDirection::Ltr,
): CreateLocaleData {
    return new CreateLocaleData(
        code: $code,
        name: $name,
        nativeName: $nativeName,
        countryCode: $countryCode,
        textDirection: $textDirection,
    );
}

it('creates a locale', function () {
    $locale = resolve(CreateLocale::class)->execute(
        createLocaleData(),
    );

    expect($locale)
        ->toBeInstanceOf(Locale::class)
        ->code->toBe('en')
        ->name->toBe('English')
        ->native_name->toBe('English')
        ->country_code->toBe('GB')
        ->text_direction->toBe(TextDirection::Ltr)
        ->is_active->toBeTrue()
        ->is_default->toBeTrue();

    $this->assertDatabaseHas('locales', [
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);
});

it('makes the first locale default', function () {
    resolve(CreateLocale::class)->execute(
        createLocaleData(),
    );

    $locale = Locale::query()->findOrFail('en');

    expect($locale)
        ->is_active->toBeTrue()
        ->is_default->toBeTrue();
});

it('does not make subsequent locales default', function () {
    $action = resolve(CreateLocale::class);

    $action->execute(
        createLocaleData(),
    );

    $action->execute(
        createLocaleData(
            code: 'ka',
            name: 'Georgian',
            nativeName: 'ქართული',
            countryCode: 'GE',
        ),
    );

    expect(Locale::query()->findOrFail('en')->is_default)
        ->toBeTrue();

    expect(Locale::query()->findOrFail('ka')->is_default)
        ->toBeFalse();
});

it('creates locales as active', function () {
    $locale = resolve(CreateLocale::class)->execute(
        createLocaleData(),
    );

    expect($locale->is_active)->toBeTrue();
});

it('flushes the locale registry after creating a locale', function () {
    $registry = resolve(LocaleRegistry::class);
    $action = resolve(CreateLocale::class);

    $action->execute(
        createLocaleData(),
    );

    // Populate the registry cache.
    expect($registry->all())->toHaveCount(1);

    $action->execute(
        createLocaleData(
            code: 'ka',
            name: 'Georgian',
            nativeName: 'ქართული',
            countryCode: 'GE',
        ),
    );

    expect($registry->all())
        ->toHaveCount(2)
        ->toHaveKeys(['en', 'ka']);
});
