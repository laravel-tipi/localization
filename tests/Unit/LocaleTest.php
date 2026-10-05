<?php

declare(strict_types=1);

use Tipi\Localization\Enums\TextDirection;
use Tipi\Localization\Locale;

it('exposes locale data', function () {
    $locale = new Locale(
        code: 'en',
        name: 'English',
        nativeName: 'English',
        countryCode: 'GB',
        textDirection: TextDirection::Ltr,
        active: true,
        default: true,
    );

    expect($locale)
        ->code->toBe('en')
        ->name->toBe('English')
        ->nativeName->toBe('English')
        ->countryCode->toBe('GB')
        ->textDirection->toBe(TextDirection::Ltr);
});

it('knows when it is active', function () {
    $locale = new Locale(
        code: 'en',
        name: 'English',
        nativeName: 'English',
        countryCode: null,
        textDirection: TextDirection::Ltr,
        active: true,
        default: false,
    );

    expect($locale)
        ->isActive()->toBeTrue()
        ->isInactive()->toBeFalse();
});

it('knows when it is inactive', function () {
    $locale = new Locale(
        code: 'en',
        name: 'English',
        nativeName: 'English',
        countryCode: null,
        textDirection: TextDirection::Ltr,
        active: false,
        default: false,
    );

    expect($locale)
        ->isActive()->toBeFalse()
        ->isInactive()->toBeTrue();
});

it('knows when it is the default locale', function () {
    $locale = new Locale(
        code: 'en',
        name: 'English',
        nativeName: 'English',
        countryCode: null,
        textDirection: TextDirection::Ltr,
        active: true,
        default: true,
    );

    expect($locale->isDefault())->toBeTrue();
});
